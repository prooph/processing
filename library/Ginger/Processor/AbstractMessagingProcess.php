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
    /**
     * @param Task\CollectData $collectData
     * @param Task\TaskListPosition $taskListPosition
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
            $this->receiveMessage(LogMessage::logException($ex->getPrevious(), $workflowMessage->getProcessTaskListPosition()), $workflowEngine);
        }
    }
}
 