<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 2/8/15 - 10:43 PM
 */
namespace Prooph\Processing\Processor;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsRunning;
use Prooph\Processing\Processor\Task\RunSubProcess;

/**
 * Class WhileProcess
 *
 * This process performs only one sub process but it repeats the perform until it receives a error log message.
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WhileProcess extends Process
{
    /**
     * @param LogMessage|WorkflowMessage $message
     * @param WorkflowEngine $workflowEngine
     */
    public function receiveMessage($message, WorkflowEngine $workflowEngine)
    {
        if (! $this->taskList->isStarted()) {
            $this->perform($workflowEngine, $message);
        }
    }

    /**
     * Start or continue the process with the help of given WorkflowEngine and optionally with given WorkflowMessage
     *
     * @param WorkflowEngine $workflowEngine
     * @param WorkflowMessage $workflowMessage
     * @throws \RuntimeException
     * @return void
     */
    public function perform(WorkflowEngine $workflowEngine, WorkflowMessage $workflowMessage = null)
    {
        if (! $this->taskList->isStarted()) {
            $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();

            if (is_null($taskListEntry)) {
                throw new \RuntimeException('WhileProcess::perform was called but there are no tasks configured!');
            }

            try {
                $this->assertTaskList();
            } catch (\Exception $ex) {
                $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
                $this->receiveMessage(
                    LogMessage::logException(
                        $ex,
                        $taskListEntry->taskListPosition()
                    ),
                    $workflowEngine
                );
                return;
            }

            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
        } //End of assert task list


    }

    private function assertTaskList()
    {
        if (count($this->taskList->getAllTaskListEntries()) != 1) {
            throw new \RuntimeException("The WhileProcess can only handle a single RunSubProcess task but there are more tasks configured");
        }

        $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();

        if (! $taskListEntry->task() instanceof RunSubProcess) {
            throw new \RuntimeException(
                sprintf(
                    'The WhileProcess can only handle a RunSubProcess task but there is a %s task configured',
                    get_class($taskListEntry->task())
                )
            );
        }
    }
}