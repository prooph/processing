<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.01.15 - 20:18
 */

namespace Prooph\ProcessingTest\Mock;

use Prooph\Processing\Message\AbstractWorkflowMessageHandler;
use Prooph\Processing\Message\ProcessingMessage;
use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\WorkflowMessage;

/**
 * Class AbstractWorkflowMessageHandlerMock
 *
 * @package Prooph\ProcessingTest\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class AbstractWorkflowMessageHandlerMock extends AbstractWorkflowMessageHandler
{
    /**
     * @var WorkflowMessage
     */
    private $lastCollectDataMessage;

    /**
     * @var WorkflowMessage
     */
    private $lastProcessDataMessage;

    /**
     * If workflow message handler receives a collect-data message it forwards the message to this
     * method and uses the returned ProcessingMessage as response
     *
     * @param WorkflowMessage $workflowMessage
     * @return ProcessingMessage
     */
    protected function handleCollectData(WorkflowMessage $workflowMessage)
    {
        $this->lastCollectDataMessage = $workflowMessage;

        return LogMessage::logDebugMsg("Collect data message received", $workflowMessage->processTaskListPosition());
    }

    /**
     * If workflow message handler receives a process-data message it forwards the message to this
     * method and uses the returned ProcessingMessage as response
     *
     * @param WorkflowMessage $workflowMessage
     * @return ProcessingMessage
     */
    protected function handleProcessData(WorkflowMessage $workflowMessage)
    {
        $this->lastProcessDataMessage = $workflowMessage;

        return LogMessage::logDebugMsg("Process data message received", $workflowMessage->processTaskListPosition());
    }

    /**
     * @return \Prooph\Processing\Message\WorkflowMessage
     */
    public function lastCollectDataMessage()
    {
        return $this->lastCollectDataMessage;
    }

    /**
     * @return \Prooph\Processing\Message\WorkflowMessage
     */
    public function lastProcessDataMessage()
    {
        return $this->lastProcessDataMessage;
    }

    public function reset()
    {
        $this->lastProcessDataMessage = null;
        $this->lastCollectDataMessage = null;
    }
}
 