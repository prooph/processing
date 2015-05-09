<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.07.14 - 15:40
 */

namespace Prooph\Processing\Message\ProophPlugin;

use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\ProcessingMessage;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Message\WorkflowMessageHandler;
use Prooph\ServiceBus\InvokeStrategy\AbstractInvokeStrategy;

/**
 * Class HandleWorkflowMessageInvokeStrategy
 *
 * This invoke strategy calls the handleWorkflowMessage() method of a WorkflowMessageHandler aka a connector
 *
 * @package Prooph\Processing\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class HandleWorkflowMessageInvokeStrategy extends AbstractInvokeStrategy
{
    /**
     * @param mixed $aHandler
     * @param mixed $aCommandOrEvent
     * @return bool
     */
    public function canInvoke($aHandler, $aCommandOrEvent)
    {
        if (! $aCommandOrEvent instanceof ProcessingMessage) return false;

        if (! $aHandler instanceof WorkflowMessageHandler) return false;

        if (! MessageNameUtils::isWorkflowMessage($aCommandOrEvent->messageName())) return false;

        return true;
    }

    /**
     * @param WorkflowMessageHandler $aHandler
     * @param WorkflowMessage $aCommandOrEvent
     */
    public function invoke($aHandler, $aCommandOrEvent)
    {
        $aHandler->handleWorkflowMessage($aCommandOrEvent);
    }
}
 