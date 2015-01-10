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
use Ginger\Processor\Task\Event\TaskEntryMarkedAsRunning;
use Ginger\Processor\Task\Event\TaskListWasRescheduled;
use Ginger\Processor\Task\RunSubProcess;
use Ginger\Processor\Task\TaskList;
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
        if ($this->taskList->isStarted()) {
            $this->checkFinished();
            return;
        }

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

        //Pre conditions are met so we can prepare the perform
        $this->rescheduleTasksBasedOnCollection(
            $collection,
            $taskListEntry->task(),
            $taskListEntry->taskListPosition()->taskListId()->nodeName()
        );

        $this->startSubProcessForEachItem($collection, $workflowEngine);
    }

    /**
     * @param CollectionType $collection
     * @param RunSubProcess $task
     * @param NodeName $nodeName
     */
    private function rescheduleTasksBasedOnCollection(CollectionType $collection, RunSubProcess $task, NodeName $nodeName)
    {
        $taskCollection = [];

        $elementsCount = count($collection);

        for($i=1;$i<=$elementsCount;$i++) {
            $taskCollection[] = RunSubProcess::reconstituteFromArray($task->getArrayCopy());
        }

        $taskList = TaskList::scheduleTasks(TaskListId::linkWith($nodeName, $this->processId()), $taskCollection);

        $this->recordThat(TaskListWasRescheduled::with($taskList, $this->processId()));
    }

    /**
     * @param CollectionType $collection
     * @param WorkflowEngine $workflowEngine
     */
    private function startSubProcessForEachItem(CollectionType $collection, WorkflowEngine $workflowEngine)
    {
        foreach ($collection as $item) {
            $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();

            $task = $taskListEntry->task();

            $message = WorkflowMessage::newDataCollected($item);

            $message->connectToProcessTask($taskListEntry->taskListPosition());

            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));

            $this->performRunSubProcess($task, $taskListEntry->taskListPosition(), $workflowEngine, $message);
        }
    }

    /**
     * @param TaskListWasRescheduled $taskListWasRescheduled
     */
    protected function whenTaskListWasRescheduled(TaskListWasRescheduled $taskListWasRescheduled)
    {
        $this->taskList = $taskListWasRescheduled->newTaskList();
    }

    private function checkFinished()
    {
        //@TODO record a ProcessFinished event
    }
}