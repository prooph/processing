<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.09.14 - 21:22
 */

namespace Prooph\Processing\Processor;

use Codeliner\ArrayReader\ArrayReader;
use Prooph\Common\Messaging\RemoteMessage;
use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Event\ProcessWasSetUp;
use Prooph\Processing\Processor\Task\CollectData;
use Prooph\Processing\Processor\Task\Event\LogMessageReceived;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsDone;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsFailed;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsRunning;
use Prooph\Processing\Processor\Task\ManipulatePayload;
use Prooph\Processing\Processor\Task\NotifyListeners;
use Prooph\Processing\Processor\Task\ProcessData;
use Prooph\Processing\Processor\Task\RunSubProcess;
use Prooph\Processing\Processor\Task\Task;
use Prooph\Processing\Processor\Task\TaskList;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\EventSourcing\AggregateRoot;
use Prooph\ServiceBus\Exception\CommandDispatchException;
use Prooph\ServiceBus\Exception\EventDispatchException;
use Prooph\ServiceBus\Message\StandardMessage;
use Zend\Stdlib\ArrayUtils;

/**
 * Abstract Class Process
 *
 * Provides basic methods for each process
 *
 * @package Prooph\Processing\Processor
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
            if (MessageNameUtils::isProcessingCommand($message->messageName())) {
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
                $workflowEngine->dispatch($messageForParent);
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
            $this->performCollectData($task, $taskListPosition, $workflowEngine, $previousMessage);
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
     * @param WorkflowMessage $previousMessage
     */
    protected function performCollectData(CollectData $collectData, TaskListPosition $taskListPosition, WorkflowEngine $workflowEngine, WorkflowMessage $previousMessage = null)
    {
        $metadata = $collectData->metadata();

        if (! is_null($previousMessage)) {
            $metadata = ArrayUtils::merge($previousMessage->metadata(), $collectData->metadata());
        }

        $workflowMessage = WorkflowMessage::collectDataOf(
            $collectData->prototype(),
            $this->taskList->taskListId()->nodeName(),
            $collectData->source(),
            $metadata
        );

        $workflowMessage->connectToProcessTask($taskListPosition);

        try {
            $workflowEngine->dispatch($workflowMessage);
        } catch (CommandDispatchException $ex) {
            $this->receiveMessage(LogMessage::logException($ex->getPrevious(), $workflowMessage), $workflowEngine);
        } catch (\Exception $ex) {
            $this->receiveMessage(LogMessage::logException($ex, $workflowMessage), $workflowEngine);
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
        $workflowMessage = $previousMessage->prepareDataProcessing(
            $taskListPosition,
            $processData->target(),
            $processData->metadata()
        );

        if (! in_array($workflowMessage->payload()->getTypeClass(), $processData->allowedTypes())) {
            $workflowMessage->changeProcessingType($processData->preferredType());
        }

        try {
            $workflowEngine->dispatch($workflowMessage);
        } catch (CommandDispatchException $ex) {
            $this->receiveMessage(LogMessage::logException($ex->getPrevious(), $workflowMessage), $workflowEngine);
        } catch (\Exception $ex) {
            $this->receiveMessage(LogMessage::logException($ex, $workflowMessage), $workflowEngine);
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

            $workflowEngine->dispatch($startSubProcessCommand);

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
        if (! MessageNameUtils::isProcessingEvent($previousMessage->messageName())) {
            $this->receiveMessage(
                LogMessage::logWrongMessageReceivedFor(
                    $task,
                    $taskListPosition,
                    $previousMessage
                ),
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

        $newEvent = $previousMessage->prepareDataProcessing(
            $taskListPosition,
            $this->taskList->taskListId()->nodeName()
        )->answerWithDataProcessingCompleted();

        $this->receiveMessage($newEvent, $workflowEngine);
    }

    /**
     * @param string $msg
     * @param WorkflowMessage $workflowMessage
     * @param WorkflowEngine $workflowEngine
     */
    protected function logErrorMsg($msg, WorkflowMessage $workflowMessage, WorkflowEngine $workflowEngine)
    {
        $this->receiveMessage(
            LogMessage::logErrorMsg(
                $msg,
                $workflowMessage
            ),
            $workflowEngine
        );
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

        $taskListEntry->markAsRunning($event->createdAt());
    }

    /**
     * @param TaskEntryMarkedAsDone $event
     */
    protected function whenTaskEntryMarkedAsDone(TaskEntryMarkedAsDone $event)
    {
        $taskListEntry = $this->taskList->getTaskListEntryAtPosition($event->taskListPosition());

        $taskListEntry->markAsSuccessfulDone($event->createdAt());
    }

    /**
     * @param TaskEntryMarkedAsFailed $event
     */
    protected function whenTaskEntryMarkedAsFailed(TaskEntryMarkedAsFailed $event)
    {
        $taskListEntry = $this->taskList->getTaskListEntryAtPosition($event->taskListPosition());

        $taskListEntry->markAsFailed($event->createdAt());
    }

    /**
     * @param LogMessageReceived $event
     */
    protected function whenLogMessageReceived(LogMessageReceived $event)
    {
        $taskListEntry = $this->taskList->getTaskListEntryAtPosition($event->taskListPosition());

        $sbMessage = RemoteMessage::fromArray($event->payload()['message']);

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
 