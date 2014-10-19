<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 04.10.14 - 21:22
 */

namespace Ginger\Processor;

use Ginger\Message\LogMessage;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Task\CollectData;
use Ginger\Processor\Task\NotifyListeners;
use Ginger\Processor\Task\ProcessData;
use Ginger\Processor\Task\RunChildProcess;
use Ginger\Processor\Task\Task;
use Ginger\Processor\Task\TaskListPosition;
use Prooph\ServiceBus\Exception\CommandDispatchException;

/**
 * Class AbstractMessagingProcess
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
abstract class AbstractMessagingProcess extends Process
{
    protected function performTask(Task $task, TaskListPosition $taskListPosition, WorkflowEngine $workflowEngine, WorkflowMessage $previousMessage = null)
    {
        if ($task instanceof CollectData) {
            $this->performCollectData($task, $taskListPosition, $workflowEngine);
            return;
        }

        if ($task instanceof ProcessData) {
            $this->performProcessData($task, $taskListPosition, $previousMessage, $workflowEngine);
        }

        if ($task instanceof RunChildProcess) {
            throw new \RuntimeException("RunChildProcess is not yet supported");
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
        $workflowMessage = WorkflowMessage::collectDataOf($collectData->prototype());

        $workflowMessage->connectToProcessTask($taskListPosition);

        try {
            $workflowEngine->getCommandBusFor($collectData->source())->dispatch($workflowMessage);
        } catch (CommandDispatchException $ex) {
            $this->receiveMessage(LogMessage::logException($ex->getPrevious(), $workflowMessage->getProcessTaskListPosition()), $workflowEngine);
        } catch (\Exception $ex) {
            $this->receiveMessage(LogMessage::logException($ex, $workflowMessage->getProcessTaskListPosition()), $workflowEngine);
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
        $workflowMessage = $previousMessage->prepareDataProcessing();

        $workflowMessage->connectToProcessTask($taskListPosition);

        if (! in_array($workflowMessage->getPayload()->getTypeClass(), $processData->allowedTypes())) {
            $workflowMessage->changeGingerType($processData->preferredType());
        }

        try {
            $workflowEngine->getCommandBusFor($processData->target())->dispatch($workflowMessage);
        } catch (CommandDispatchException $ex) {
            $this->receiveMessage(LogMessage::logException($ex->getPrevious(), $workflowMessage->getProcessTaskListPosition()), $workflowEngine);
        } catch (\Exception $ex) {
            $this->receiveMessage(LogMessage::logException($ex, $workflowMessage->getProcessTaskListPosition()), $workflowEngine);
        }
    }
}
 