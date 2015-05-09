<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 04.10.14 - 20:14
 */

namespace Prooph\Processing\Processor;

use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Processor\Task\CollectData;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsRunning;
use Prooph\Processing\Processor\Task\RunSubProcess;
use Prooph\Processing\Processor\Task\Task;

/**
 * Class LinearProcess
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class LinearProcess extends Process
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
        if (is_null($workflowMessage)) {
            $this->startWithoutMessage($workflowEngine);
            return;
        }

        $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();

        if ($taskListEntry) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));

            if (! $this->isCorrectMessageFor($taskListEntry->task(), $workflowMessage)) {
                $this->receiveMessage(
                    LogMessage::logWrongMessageReceivedFor(
                        $taskListEntry->task(),
                        $taskListEntry->taskListPosition(),
                        $workflowMessage
                    ),
                    $workflowEngine
                );

                return;
            }



            $this->performTask($taskListEntry->task(), $taskListEntry->taskListPosition(), $workflowEngine, $workflowMessage);
            return;
        }
    }

    /**
     * @param WorkflowEngine $workflowEngine
     */
    protected function startWithoutMessage(WorkflowEngine $workflowEngine)
    {
        $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();

        if ($taskListEntry) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));

            $task = $taskListEntry->task();

            if (! $task instanceof CollectData && ! $task instanceof RunSubProcess) {
                $this->receiveMessage(LogMessage::logNoMessageReceivedFor($task, $taskListEntry->taskListPosition()), $workflowEngine);
                return;
            }

            $this->performTask($task, $taskListEntry->taskListPosition(), $workflowEngine);
        }
    }

    /**
     * @param Task $task
     * @param WorkflowMessage $message
     * @return bool
     */
    private function isCorrectMessageFor(Task $task, WorkflowMessage $message)
    {
        if (MessageNameUtils::isProcessingCommand($message->messageName())) {

            if (! $task instanceof CollectData
                || $message->messageName() !== MessageNameUtils::getCollectDataCommandName($task->prototype()->of())) {

                return false;
            }
        }

        return true;
    }
}
 