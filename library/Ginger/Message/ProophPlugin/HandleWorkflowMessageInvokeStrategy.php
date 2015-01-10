<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.07.14 - 15:40
 */

namespace Ginger\Message\ProophPlugin;

use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Ginger\Message\WorkflowMessageHandler;
use Prooph\ServiceBus\InvokeStrategy\AbstractInvokeStrategy;
use Prooph\ServiceBus\Message\MessageNameProvider;

/**
 * Class HandleWorkflowMessageInvokeStrategy
 *
 * This invoke strategy calls the handleWorkflowMessage() method of a WorkflowMessageHandler aka a connector
 *
 * @package Ginger\Message\Service
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
        if (! $aCommandOrEvent instanceof MessageNameProvider) return false;

        if (! $aHandler instanceof WorkflowMessageHandler) return false;

        if (! MessageNameUtils::isWorkflowMessage($aCommandOrEvent->getMessageName())) return false;

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
 