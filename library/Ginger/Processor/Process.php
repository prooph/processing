<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.09.14 - 21:22
 */

namespace Ginger\Processor;

use Codeliner\ArrayReader\ArrayReader;
use Ginger\Message\LogMessage;
use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Event\ProcessWasSetUp;
use Ginger\Processor\Task\CollectData;
use Ginger\Processor\Task\Event\LogMessageReceived;
use Ginger\Processor\Task\Event\TaskEntryMarkedAsDone;
use Ginger\Processor\Task\Event\TaskEntryMarkedAsFailed;
use Ginger\Processor\Task\Event\TaskEntryMarkedAsRunning;
use Ginger\Processor\Task\ManipulatePayload;
use Ginger\Processor\Task\NotifyListeners;
use Ginger\Processor\Task\ProcessData;
use Ginger\Processor\Task\RunSubProcess;
use Ginger\Processor\Task\Task;
use Ginger\Processor\Task\TaskList;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use Prooph\EventSourcing\AggregateRoot;
use Prooph\ServiceBus\Exception\CommandDispatchException;
use Prooph\ServiceBus\Exception\EventDispatchException;
use Prooph\ServiceBus\Message\StandardMessage;

/**
 * Abstract Class Process
 *
 * Provides basic methods for each process
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
abstract class Process extends AggregateRoot
{
    /**
     * @var ProcessId
     */
    protected $processId;

    /**
     * @var TaskListPosition
     */
    protected $parentTaskListPosition;

    /**
     * @var bool
     */
    protected $syncLogMessages;

    /**
     * @var ArrayReader
     */
    protected $config;

    /**
     * @var TaskList
     */
    protected $taskList;

    /**
     * Start or continue the process with the help of given WorkflowEngine and optionally with given WorkflowMessage
     *
     * @param WorkflowEngine $workflowEngine
     * @param WorkflowMessage $workflowMessage
     * @return void
     */
    abstract public function perform(WorkflowEngine $workflowEngine, WorkflowMessage $workflowMessage = null);

    /**
     * Implementers can override this method to check config and throw an exception if something is missing
     *
     * @param array $config
     * @throws \InvalidArgumentException
     */
    protected function assertConfig(array $config)
    {
        return;
    }

    /**
     * Creates new process from given tasks and config
     *
     * @param NodeName $nodeName
     * @param Task[] $tasks
     * @param array $config
     * @return static
     */
    public static function setUp(NodeName $nodeName, array $tasks, array $config = array())
    {
        /** @var $instance Process */
        $instance = new static();

        $instance->assertConfig($config);

        $processId = ProcessId::generate();

        $taskList = TaskList::scheduleTasks(TaskListId::linkWith($nodeName, $processId), $tasks);

        $instance->recordThat(ProcessWasSetUp::with($processId, $taskList, $config));

        return $instance;
    }

    /**
     * @param TaskListPosition $parentTaskListPosition
     * @param NodeName $nodeName
     * @param Task[] $tasks
     * @param array $config
     * @param bool $syncLogMessages
     * @throws \InvalidArgumentException
     * @return static
     */
    public static function setUpAsSubProcess(
        TaskListPosition $parentTaskListPosition,
        NodeName $nodeName,
        array $tasks,
        array $config = array(),
        $syncLogMessages = true
    ) {
        /** @var $instance Process */
        $instance = new static();

        $instance->assertConfig($config);

        $processId = ProcessId::generate();

        $taskList = TaskList::scheduleTasks(TaskListId::linkWith($nodeName, $processId), $tasks);

        if (! is_bool($syncLogMessages)) {
            throw new \InvalidArgumentException("Argument syncLogMessages must be of type boolean");
        }

        $instance->recordThat(ProcessWasSetUp::asSubProcess($processId, $parentTaskListPosition, $taskList, $config, $syncLogMessages));

        return $instance;
    }

    /**
     * @return ProcessId
     */
    public function processId()
    {
        return $this->processId;
    }

    /**
     * A Process can start or continue with the next step after it has received a message
     *
     * @param WorkflowMessage|LogMessage $message
     * @param WorkflowEngine $workflowEngine
     * @throws \RuntimeException
     * @return void
     */
    public function receiveMessage($message, WorkflowEngine $workflowEngine)
    {
        if ($message instanceof WorkflowMessage) {
            if (MessageNameUtils::isGingerCommand($message->getMessageName())) {
                $this->perform($workflowEngine, $message);
                return;
            }

            $this->assertTaskEntryExists($message->processTaskListPosition());

            $this->recordThat(TaskEntryMarkedAsDone::at($message->processTaskListPosition()));

            $this->perform($workflowEngine, $message);
            return;
        }

        if ($message instanceof LogMessage) {
            $this->assertTaskEntryExists($message->processTaskListPosition());

            $this->recordThat(LogMessageReceived::record($message));

            if ($message->isError()) {
                $this->recordThat(TaskEntryMarkedAsFailed::at($message->processTaskListPosition()));
            } elseif ($this->isSubProcess() && $this->syncLogMessages) {
                //We only sync non error messages, because errors are always synced and then they would be received twice
                $messageForParent = $message->reconnectToProcessTask($this->parentTaskListPosition);
                $workflowEngine->getEventChannelFor($this->parentTaskListPosition->taskListId()->nodeName()->toString())
                    ->dispatch($messageForParent);
            }
        }
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->taskList->isCompleted();
    }

    /**
     * @return bool
     */
    public function isSuccessfulDone()
    {
        return $this->taskList->isSuccessfulDone();
    }

    /**
     * @return bool
     */
    public function isSubProcess()
    {
        return ! is_null($this->parentTaskListPosition);
    }

    /**
     * @return null|TaskListPosition
     */
    public function parentTaskListPosition()
    {
        return $this->parentTaskListPosition;
    }

    /**
     * @return ArrayReader
     */
    public function config()
    {
        return $this->config;
    }

    /**
     * @param Task $task
     * @param TaskListPosition $taskListPosition
     * @param WorkflowEngine $workflowEngine
     * @param WorkflowMessage $previousMessage
     * @throws \RuntimeException
     */
    protected function performTask(Task $task, TaskListPosition $taskListPosition, WorkflowEngine $workflowEngine, WorkflowMessage $previousMessage = null)
    {
        if ($task instanceof CollectData) {
            $this->performCollectData($task, $taskListPosition, $workflowEngine);
            return;
        }

        if ($task instanceof ProcessData) {
            $this->performProcessData($task, $taskListPosition, $previousMessage, $workflowEngine);
        }

        if ($task instanceof RunSubProcess) {
            $this->performRunSubProcess($task, $taskListPosition, $workflowEngine, $previousMessage);
        }

        if ($task instanceof ManipulatePayload) {
            $this->performManipulatePayload($task, $taskListPosition, $workflowEngine, $previousMessage);
        }

        if ($task instanceof NotifyListeners) {
            throw new \RuntimeException("NotifyListeners is not yet supported");
        }
    }

    /**
     * @param CollectData $collectData
     * @param TaskListPosition $taskListPosition
     * @param WorkflowEngine $workflowEngine
     */
    protected function performCollectData(CollectData $collectData, TaskListPosition $taskListPosition, WorkflowEngine $workflowEngine)
    {
        $workflowMessage = WorkflowMessage::collectDataOf($collectData->prototype(), $collectData->metadata(), $collectData->source());

        $workflowMessage->connectToProcessTask($taskListPosition);

        try {
            $workflowEngine->getCommandChannelFor($collectData->source())->dispatch($workflowMessage);
        } catch (CommandDispatchException $ex) {
            $this->receiveMessage(LogMessage::logException($ex->getPrevious(), $workflowMessage->processTaskListPosition()), $workflowEngine);
        } catch (\Exception $ex) {
            $this->receiveMessage(LogMessage::logException($ex, $workflowMessage->processTaskListPosition()), $workflowEngine);
        }
    }

    /**
     * @param ProcessData $processData
     * @param TaskListPosition $taskListPosition
     * @param WorkflowMessage $previousMessage
     * @param WorkflowEngine $workflowEngine
     */
    protected function performProcessData(ProcessData $processData, TaskListPosition $taskListPosition, WorkflowMessage $previousMessage, WorkflowEngine $workflowEngine)
    {
        $workflowMessage = $previousMessage->prepareDataProcessing($taskListPosition, $processData->metadata(), $processData->target());

        if (! in_array($workflowMessage->payload()->getTypeClass(), $processData->allowedTypes())) {
            $workflowMessage->changeGingerType($processData->preferredType());
        }

        try {
            $workflowEngine->getCommandChannelFor($processData->target())->dispatch($workflowMessage);
        } catch (CommandDispatchException $ex) {
            $this->receiveMessage(LogMessage::logException($ex->getPrevious(), $workflowMessage->processTaskListPosition()), $workflowEngine);
        } catch (\Exception $ex) {
            $this->receiveMessage(LogMessage::logException($ex, $workflowMessage->processTaskListPosition()), $workflowEngine);
        }
    }

    /**
     * @param RunSubProcess $task
     * @param TaskListPosition $taskListPosition
     * @param WorkflowEngine $workflowEngine
     * @param WorkflowMessage $previousMessage
     */
    protected function performRunSubProcess(
        RunSubProcess $task,
        TaskListPosition $taskListPosition,
        WorkflowEngine $workflowEngine,
        WorkflowMessage $previousMessage = null)
    {
        try {
            $startSubProcessCommand = $task->generateStartCommandForSubProcess($taskListPosition, $previousMessage);

            $workflowEngine->getCommandChannelFor($task->targetNodeName()->toString())->dispatch($startSubProcessCommand);

        } catch (CommandDispatchException $ex) {
            $this->receiveMessage(LogMessage::logException($ex->getPrevious(), $taskListPosition), $workflowEngine);
        } catch (\Exception $ex) {
            $this->receiveMessage(LogMessage::logException($ex, $taskListPosition), $workflowEngine);
        }
    }

    /**
     * @param ManipulatePayload $task
     * @param TaskListPosition $taskListPosition
     * @param WorkflowEngine $workflowEngine
     * @param WorkflowMessage $previousMessage
     */
    protected function performManipulatePayload(
        ManipulatePayload $task,
        TaskListPosition $taskListPosition,
        WorkflowEngine $workflowEngine,
        WorkflowMessage $previousMessage)
    {
        if (! MessageNameUtils::isGingerEvent($previousMessage->getMessageName())) {
            $this->receiveMessage(
                LogMessage::logWrongMessageReceivedFor($task, $taskListPosition, $previousMessage),
                $workflowEngine
            );

            return;
        }

        $payload = $previousMessage->payload();

        try {
            $task->performManipulationOn($payload);
        } catch (\Exception $ex) {
            $this->receiveMessage(LogMessage::logException($ex, $taskListPosition), $workflowEngine);
            return;
        }

        $newEvent = $previousMessage->prepareDataProcessing($taskListPosition)->answerWithDataProcessingCompleted();

        $this->receiveMessage($newEvent, $workflowEngine);
    }

    /**
     * @param TaskListPosition $taskListPosition
     * @throws \RuntimeException
     */
    protected function assertTaskEntryExists(TaskListPosition $taskListPosition)
    {
        $taskEntry = $this->taskList->getTaskListEntryAtPosition($taskListPosition);

        if (is_null($taskEntry)) {
            throw new \RuntimeException(sprintf(
                "No task entry found at position: %s",
                $taskListPosition->toString()
            ));
        }
    }

    /**
     * @param ProcessWasSetUp $event
     */
    protected function whenProcessWasSetUp(ProcessWasSetUp $event)
    {
        $this->processId = $event->processId();
        $this->parentTaskListPosition = $event->parentTaskListPosition();
        $this->config = new ArrayReader($event->config());
        $this->taskList = TaskList::fromArray($event->taskList());
        $this->syncLogMessages = $event->syncLogMessages();
    }

    /**
     * @param TaskEntryMarkedAsRunning $event
     */
    protected function whenTaskEntryMarkedAsRunning(TaskEntryMarkedAsRunning $event)
    {
        $taskListEntry = $this->taskList->getTaskListEntryAtPosition($event->taskListPosition());

        $taskListEntry->markAsRunning($event->occurredOn());
    }

    /**
     * @param TaskEntryMarkedAsDone $event
     */
    protected function whenTaskEntryMarkedAsDone(TaskEntryMarkedAsDone $event)
    {
        $taskListEntry = $this->taskList->getTaskListEntryAtPosition($event->taskListPosition());

        $taskListEntry->markAsSuccessfulDone($event->occurredOn());
    }

    /**
     * @param TaskEntryMarkedAsFailed $event
     */
    protected function whenTaskEntryMarkedAsFailed(TaskEntryMarkedAsFailed $event)
    {
        $taskListEntry = $this->taskList->getTaskListEntryAtPosition($event->taskListPosition());

        $taskListEntry->markAsFailed($event->occurredOn());
    }

    /**
     * @param LogMessageReceived $event
     */
    protected function whenLogMessageReceived(LogMessageReceived $event)
    {
        $taskListEntry = $this->taskList->getTaskListEntryAtPosition($event->taskListPosition());

        $sbMessage = StandardMessage::fromArray($event->payload()['message']);

        $taskListEntry->logMessage(LogMessage::fromServiceBusMessage($sbMessage));
    }

    /**
     * @return string representation of the unique identifier of the aggregate root
     */
    protected function aggregateId()
    {
        return $this->processId()->toString();
    }
}
 