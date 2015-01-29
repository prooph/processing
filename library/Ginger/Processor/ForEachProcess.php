<?php
/*
 * This file is part of Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12/6/14 - 12:41 AM
 */
namespace Ginger\Processor;

use Ginger\Message\LogMessage;
use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Task\Event\LogMessageReceived;
use Ginger\Processor\Task\Event\MultiPerformTaskFailed;
use Ginger\Processor\Task\Event\MultiPerformTaskSucceed;
use Ginger\Processor\Task\Event\MultiPerformTaskWasStarted;
use Ginger\Processor\Task\Event\TaskEntryMarkedAsDone;
use Ginger\Processor\Task\Event\TaskEntryMarkedAsFailed;
use Ginger\Processor\Task\Event\TaskEntryMarkedAsRunning;
use Ginger\Processor\Task\Event\TaskListWasRescheduled;
use Ginger\Processor\Task\MultiPerformTask;
use Ginger\Processor\Task\RunSubProcess;
use Ginger\Processor\Task\TaskList;
use Ginger\Processor\Task\TaskListEntry;
use Ginger\Processor\Task\TaskListId;
use Ginger\Type\AbstractCollection;
use Ginger\Type\CollectionType;
use Ginger\Type\Description\NativeType;
use Zend\Validator\Exception\BadMethodCallException;

/**
 * Class ForEachProcess
 *
 * This process can be used to run a configured sub process for each element of the NativeType::COLLECTION.
 * The payload of the workflow message must contain a collection
 * otherwise the process will fail.
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <alexander.miertsch.extern@sixt.com>
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
            if (! MessageNameUtils::isGingerEvent($message->messageName())) {

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


        if (count($this->taskList->getAllTaskListEntries()) > 1) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
            $this->receiveMessage(
                LogMessage::logErrorMsg(
                    'The ForEachProcess can only handle a single RunSubProcess task but there are more tasks configured',
                    $taskListEntry->taskListPosition()
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
                    $taskListEntry->taskListPosition()
                ),
                $workflowEngine
            );
            return;
        }

        if (is_null($workflowMessage)) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
            $this->receiveMessage(
                LogMessage::logNoMessageReceivedFor($taskListEntry->task(), $taskListEntry->taskListPosition()),
                $workflowEngine
            );
            return;
        }

        if (! MessageNameUtils::isGingerEvent($workflowMessage->getMessageName())) {
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
                        'The ForEachProcess requires a Ginger\Type\CollectionType as payload of the incoming message, but it is a %s type given',
                        $workflowMessage->payload()->getTypeClass()
                    ),
                    $taskListEntry->taskListPosition()
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

        foreach ($collection as $item) {

            $message = WorkflowMessage::newDataCollected($item);

            $message->connectToProcessTask($taskListEntry->taskListPosition());

            $this->recordThat(MultiPerformTaskWasStarted::at($taskListEntry->taskListPosition()));

            $this->performRunSubProcess(
                $taskListEntry->task(),
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