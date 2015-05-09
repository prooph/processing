<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12/6/14 - 12:41 AM
 */
namespace Prooph\Processing\Processor;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Task\Event\LogMessageReceived;
use Prooph\Processing\Processor\Task\Event\MultiPerformTaskFailed;
use Prooph\Processing\Processor\Task\Event\MultiPerformTaskSucceed;
use Prooph\Processing\Processor\Task\Event\MultiPerformTaskWasStarted;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsDone;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsFailed;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsRunning;
use Prooph\Processing\Processor\Task\RunSubProcess;
use Prooph\Processing\Processor\Task\TaskListEntry;
use Prooph\Processing\Type\CollectionType;

/**
 * Class ForEachProcess
 *
 * This process can be used to run a configured sub process for each element of the NativeType::COLLECTION.
 * The payload of the workflow message must contain a collection
 * otherwise the process will fail.
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ForEachProcess extends Process
{
    /**
     * @var bool
     */
    private $processingCollection = false;

    private $performedTasks = 0;

    private $performedSuccessful = 0;

    private $performedWithError = 0;

    /**
     * @param LogMessage|WorkflowMessage $message
     * @param WorkflowEngine $workflowEngine
     */
    public function receiveMessage($message, WorkflowEngine $workflowEngine)
    {
        if (! $this->taskList->isStarted()) {
            parent::receiveMessage($message, $workflowEngine);
            return;
        }
        $this->assertTaskEntryExists($message->processTaskListPosition());

        $taskListEntry = $this->taskList->getTaskListEntryAtPosition($message->processTaskListPosition());

        if ($message instanceof WorkflowMessage) {
            if (! MessageNameUtils::isProcessingEvent($message->messageName())) {

                $this->receiveMessage(
                    LogMessage::logWrongMessageReceivedFor(
                        $taskListEntry->task(),
                        $taskListEntry->taskListPosition(),
                        $message
                    ),
                    $workflowEngine
                );

                return;
            }

            $this->recordThat(MultiPerformTaskSucceed::at($taskListEntry->taskListPosition()));
        }

        if ($message instanceof LogMessage) {

            $this->recordThat(LogMessageReceived::record($message));

            if ($message->isError()) {
                $this->recordThat(MultiPerformTaskFailed::at($taskListEntry->taskListPosition()));
            }
        }

        $this->checkFinished($taskListEntry);
    }

    /**
     * Start or continue the process with the help of given WorkflowEngine and optionally with given WorkflowMessage
     *
     * @param WorkflowEngine $workflowEngine
     * @param WorkflowMessage $workflowMessage
     * @throws \RuntimeException
     * @throws \BadMethodCallException
     * @return void
     */
    public function perform(WorkflowEngine $workflowEngine, WorkflowMessage $workflowMessage = null)
    {
        $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();

        if (is_null($taskListEntry)) {
            throw new \RuntimeException('ForEachProcess::perform was called but there are no tasks configured!');
        }

        if (is_null($workflowMessage)) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
            $this->receiveMessage(
                LogMessage::logNoMessageReceivedFor($taskListEntry->task(), $taskListEntry->taskListPosition()),
                $workflowEngine
            );
            return;
        }

        $workflowMessage = $workflowMessage->reconnectToProcessTask($taskListEntry->taskListPosition());

        if (count($this->taskList->getAllTaskListEntries()) > 1) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
            $this->receiveMessage(
                LogMessage::logErrorMsg(
                    'The ForEachProcess can only handle a single RunSubProcess task but there are more tasks configured',
                    $workflowMessage
                ),
                $workflowEngine
            );
            return;
        }

        if (! $taskListEntry->task() instanceof RunSubProcess) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
            $this->receiveMessage(
                LogMessage::logErrorMsg(
                    sprintf(
                        'The ForEachProcess can only handle a RunSubProcess task but there is a %s task configured',
                        get_class($taskListEntry->task())
                    ),
                    $workflowMessage
                ),
                $workflowEngine
            );
            return;
        }

        if (! MessageNameUtils::isProcessingEvent($workflowMessage->messageName())) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
            $this->receiveMessage(
                LogMessage::logWrongMessageReceivedFor($taskListEntry->task(), $taskListEntry->taskListPosition(), $workflowMessage),
                $workflowEngine
            );
            return;
        }

        $collection = $workflowMessage->payload()->toType();

        if (! $collection instanceof CollectionType) {
            $this->receiveMessage(
                LogMessage::logErrorMsg(
                    sprintf(
                        'The ForEachProcess requires a Prooph\ProcessingType\CollectionType as payload of the incoming message, but it is a %s type given',
                        $workflowMessage->payload()->getTypeClass()
                    ),
                    $workflowMessage
                ),
                $workflowEngine
            );
            return;
        }

        $this->startSubProcessForEachItem($collection, $workflowEngine);
    }

    /**
     * @param CollectionType $collection
     * @param WorkflowEngine $workflowEngine
     */
    private function startSubProcessForEachItem(CollectionType $collection, WorkflowEngine $workflowEngine)
    {
        $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();

        $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));

        $this->processingCollection = true;

        /** @var $task RunSubProcess */
        $task = $taskListEntry->task();

        foreach ($collection as $item) {
            $message = WorkflowMessage::newDataCollected(
                $item,
                $this->taskList->taskListId()->nodeName(),
                $task->targetNodeName()
            );

            $message->connectToProcessTask($taskListEntry->taskListPosition());

            $this->recordThat(MultiPerformTaskWasStarted::at($taskListEntry->taskListPosition()));

            $this->performRunSubProcess(
                $task,
                $taskListEntry->taskListPosition(),
                $workflowEngine,
                $message
            );
        }

        $this->processingCollection = false;
    }

    /**
     * @param MultiPerformTaskWasStarted $event
     */
    public function whenMultiPerformTaskWasStarted(MultiPerformTaskWasStarted $event)
    {
        $this->performedTasks++;
    }

    /**
     * @param MultiPerformTaskSucceed $event
     */
    public function whenMultiPerformTaskSucceed(MultiPerformTaskSucceed $event)
    {
        $this->performedSuccessful++;
    }

    /**
     * @param MultiPerformTaskFailed $event
     */
    public function whenMultiPerformTaskFailed(MultiPerformTaskFailed $event)
    {
        $this->performedWithError++;
    }

    /**
     * @param TaskListEntry $taskListEntry
     */
    private function checkFinished(TaskListEntry $taskListEntry)
    {
        if ($this->processingCollection) return;

        if ($this->performedTasks == ($this->performedSuccessful + $this->performedWithError)) {

            if ($this->performedWithError > 0) {
                $this->recordThat(
                    TaskEntryMarkedAsFailed::at(
                        $taskListEntry->taskListPosition()
                    )
                );
            } else {
                $this->recordThat(
                    TaskEntryMarkedAsDone::at(
                        $taskListEntry->taskListPosition()
                    )
                );
            }
        }
    }
}