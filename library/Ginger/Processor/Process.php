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
use Ginger\Processor\Event\ProcessSetUp;
use Ginger\Processor\Task\Event\LogMessageReceived;
use Ginger\Processor\Task\Event\TaskEntryMarkedAsDone;
use Ginger\Processor\Task\Event\TaskEntryMarkedAsFailed;
use Ginger\Processor\Task\Event\TaskEntryMarkedAsRunning;
use Ginger\Processor\Task\RunSubProcess;
use Ginger\Processor\Task\Task;
use Ginger\Processor\Task\TaskList;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use Prooph\EventSourcing\AggregateRoot;
use Prooph\ServiceBus\Exception\CommandDispatchException;
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
     * @param Task[] $tasks
     * @param array $config
     * @return static
     */
    public static function setUp(array $tasks, array $config = array())
    {
        /** @var $instance Process */
        $instance = new static();

        $instance->assertConfig($config);

        $processId = ProcessId::generate();

        $taskList = TaskList::scheduleTasks(TaskListId::linkWith($processId), $tasks);

        $instance->recordThat(ProcessSetUp::with($processId, $taskList, $config));

        return $instance;
    }

    /**
     * @param TaskListPosition $parentTaskListPosition
     * @param Task[] $tasks
     * @param array $config
     *
     * @return static
     */
    public static function setUpAsSubProcess(TaskListPosition $parentTaskListPosition, array $tasks, array $config = array())
    {
        /** @var $instance Process */
        $instance = new static();

        $instance->assertConfig($config);

        $processId = ProcessId::generate();

        $taskList = TaskList::scheduleTasks(TaskListId::linkWith($processId), $tasks);

        $instance->recordThat(ProcessSetUp::asSubProcess($processId, $parentTaskListPosition, $taskList, $config));

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

            $this->assertTaskEntryExists($message->getProcessTaskListPosition());

            $this->recordThat(TaskEntryMarkedAsDone::at($message->getProcessTaskListPosition()));

            $this->perform($workflowEngine, $message);
            return;
        }

        if ($message instanceof LogMessage) {
            $this->assertTaskEntryExists($message->getProcessTaskListPosition());

            $this->recordThat(LogMessageReceived::record($message));

            if ($message->isError()) {
                $this->recordThat(TaskEntryMarkedAsFailed::at($message->getProcessTaskListPosition()));
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

            $workflowEngine->getCommandBusFor(Definition::SERVICE_WORKFLOW_PROCESSOR)->dispatch($startSubProcessCommand);

        } catch (CommandDispatchException $ex) {
            $this->receiveMessage(LogMessage::logException($ex->getPrevious(), $taskListPosition), $workflowEngine);
        } catch (\Exception $ex) {
            $this->receiveMessage(LogMessage::logException($ex, $taskListPosition), $workflowEngine);
        }
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
     * @param ProcessSetUp $event
     */
    protected function whenProcessSetUp(ProcessSetUp $event)
    {
        $this->processId = $event->processId();
        $this->parentTaskListPosition = $event->parentTaskListPosition();
        $this->config = new ArrayReader($event->config());
        $this->taskList = TaskList::fromArray($event->taskList());
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
 