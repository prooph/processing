<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.01.15 - 19:07
 */

namespace Ginger\Message;
use Ginger\Processor\WorkflowEngine;

/**
 * Class AbstractWorkflowMessageHandler
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
abstract class AbstractWorkflowMessageHandler implements WorkflowMessageHandler
{
    /**
     * @var WorkflowEngine
     */
    protected $workflowEngine;

    /**
     * If workflow message handler receives a collect-data message it forwards the message to this
     * method and uses the returned GingerMessage as response
     *
     * @param WorkflowMessage $workflowMessage
     * @return GingerMessage
     */
    abstract protected function handleCollectData(WorkflowMessage $workflowMessage);

    /**
     * If workflow message handler receives a process-data message it forwards the message to this
     * method and uses the returned GingerMessage as response
     *
     * @param WorkflowMessage $workflowMessage
     * @return GingerMessage
     */
    abstract protected function handleProcessData(WorkflowMessage $workflowMessage);

    /**
     * @param WorkflowMessage $workflowMessage
     * @return void
     */
    public function handleWorkflowMessage(WorkflowMessage $workflowMessage)
    {
        if (! MessageNameUtils::isGingerCommand($workflowMessage->messageName())) {
            $this->workflowEngine->dispatch(LogMessage::logUnsupportedMessageReceived($workflowMessage, get_called_class()));
        }

        try {
            if ($workflowMessage->messageType() === MessageNameUtils::COLLECT_DATA) {
                $gingerMessage = $this->handleCollectData($workflowMessage);

                if (! $gingerMessage instanceof GingerMessage) {
                    throw new \RuntimeException(sprintf("%s::handleCollectData method returned %s instead of a GingerMessage", get_called_class(), ((is_object($gingerMessage)? get_class($gingerMessage) : gettype($gingerMessage)))));
                }
            }else if ($workflowMessage->messageType() === MessageNameUtils::PROCESS_DATA) {
                $gingerMessage = $this->handleProcessData($workflowMessage);

                if (! $gingerMessage instanceof GingerMessage) {
                    throw new \RuntimeException(sprintf("%s::handleProcessData method returned %s instead of a GingerMessage", get_called_class(), ((is_object($gingerMessage)? get_class($gingerMessage) : gettype($gingerMessage)))));
                }
            } else {
                $this->workflowEngine->dispatch(LogMessage::logUnsupportedMessageReceived($workflowMessage, get_called_class()));
                return;
            }

            $this->workflowEngine->dispatch($gingerMessage);
            return;
        } catch (\Exception $ex) {
            $this->workflowEngine->dispatch(LogMessage::logException($ex, $workflowMessage->processTaskListPosition()));
            return;
        }
    }

    /**
     * @param WorkflowEngine $workflowEngine
     * @return void
     */
    public function useWorkflowEngine(WorkflowEngine $workflowEngine)
    {
        $this->workflowEngine = $workflowEngine;
    }
}
 