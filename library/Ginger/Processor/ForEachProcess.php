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
use Ginger\Processor\Task\RunSubProcess;
use Ginger\Processor\Task\TaskList;
use Ginger\Processor\Task\TaskListId;
use Ginger\Type\AbstractCollection;
use Ginger\Type\Description\NativeType;

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
     * @return void
     */
    public function perform(WorkflowEngine $workflowEngine, WorkflowMessage $workflowMessage = null)
    {
        $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();

        if (is_null($taskListEntry)) {
            return;
        }

        if (count($this->taskList->getAllTaskListEntries()) > 1) {
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
            $this->receiveMessage(
                LogMessage::logNoMessageReceivedFor($taskListEntry->task(), $taskListEntry->taskListPosition()),
                $workflowEngine
            );
            return;
        }

        if (! MessageNameUtils::isGingerEvent($workflowMessage->getMessageName())) {
            $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();
            $this->receiveMessage(
                LogMessage::logWrongMessageReceivedFor($taskListEntry->task(), $taskListEntry->taskListPosition(), $workflowMessage),
                $workflowEngine
            );
            return;
        }

        $collection = $workflowMessage->getPayload()->toType();

        if ($collection->description()->nativeType() !== NativeType::COLLECTION) {
            $this->receiveMessage(
                LogMessage::logErrorMsg(
                    sprintf(
                        'The ForEachProcess requires a Ginger\Type\NativeType::COLLECTION as payload of the incoming message, but it is a %s type given',
                        $workflowMessage->getPayload()->getTypeClass()
                    ),
                    $taskListEntry->taskListPosition()
                ),
                $workflowEngine
            );
            return;
        }

        $this->rescheduleTasksBasedOnCollection(
            $collection->value(),
            $taskListEntry->task(),
            $taskListEntry->taskListPosition()->taskListId()->nodeName()
        );
    }

    /**
     * @param array $collection
     * @param RunSubProcess $task
     * @param NodeName $nodeName
     */
    private function rescheduleTasksBasedOnCollection(array &$collection, RunSubProcess $task, NodeName $nodeName)
    {
        $taskCollection = [];

        $elementsCount = count($collection);

        for($i=1;$i<=$elementsCount;$i++) {
            $taskCollection[] = RunSubProcess::reconstituteFromArray($task->getArrayCopy());
        }

        $taskList = TaskList::scheduleTasks(TaskListId::linkWith($nodeName, $this->processId()), $taskCollection);

        //@TODO record TaskListWasRescheduled event
    }
}