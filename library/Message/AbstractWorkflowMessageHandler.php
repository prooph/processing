<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.01.15 - 19:07
 */

namespace Prooph\Processing\Message;
use Prooph\Processing\Processor\WorkflowEngine;

/**
 * Class AbstractWorkflowMessageHandler
 *
 * @package Prooph\Processing\Message
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
     * method and uses the returned ProcessingMessage as response
     *
     * @param WorkflowMessage $workflowMessage
     * @return ProcessingMessage
     */
    abstract protected function handleCollectData(WorkflowMessage $workflowMessage);

    /**
     * If workflow message handler receives a process-data message it forwards the message to this
     * method and uses the returned ProcessingMessage as response
     *
     * @param WorkflowMessage $workflowMessage
     * @return ProcessingMessage
     */
    abstract protected function handleProcessData(WorkflowMessage $workflowMessage);

    /**
     * @param WorkflowMessage $workflowMessage
     * @return void
     */
    public function handleWorkflowMessage(WorkflowMessage $workflowMessage)
    {
        if (! MessageNameUtils::isProcessingCommand($workflowMessage->messageName())) {
            $this->workflowEngine->dispatch(LogMessage::logUnsupportedMessageReceived($workflowMessage));
        }

        try {
            if ($workflowMessage->messageType() === MessageNameUtils::COLLECT_DATA) {
                $processingMessage = $this->handleCollectData($workflowMessage);

                if (! $processingMessage instanceof ProcessingMessage) {
                    throw new \RuntimeException(sprintf("%s::handleCollectData method returned %s instead of a ProcessingMessage", get_called_class(), ((is_object($processingMessage)? get_class($processingMessage) : gettype($processingMessage)))));
                }
            }else if ($workflowMessage->messageType() === MessageNameUtils::PROCESS_DATA) {
                $processingMessage = $this->handleProcessData($workflowMessage);

                if (! $processingMessage instanceof ProcessingMessage) {
                    throw new \RuntimeException(sprintf("%s::handleProcessData method returned %s instead of a ProcessingMessage", get_called_class(), ((is_object($processingMessage)? get_class($processingMessage) : gettype($processingMessage)))));
                }
            } else {
                $this->workflowEngine->dispatch(LogMessage::logUnsupportedMessageReceived($workflowMessage));
                return;
            }

            $this->workflowEngine->dispatch($processingMessage);
            return;
        } catch (\Exception $ex) {
            $this->workflowEngine->dispatch(LogMessage::logException($ex, $workflowMessage));
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
 