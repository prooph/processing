<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 20.10.14 - 00:00
 */

namespace Prooph\Processing\Processor\ProophPlugin;

use Prooph\Processing\Processor\WorkflowProcessor;
use Prooph\ServiceBus\InvokeStrategy\AbstractInvokeStrategy;

/**
 * Class WorkflowProcessorInvokeStrategy
 *
 * @package Prooph\Processing\Processor\ProophPlugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowProcessorInvokeStrategy extends AbstractInvokeStrategy
{
    /**
     * @param mixed $aHandler
     * @param mixed $aCommandOrEvent
     * @return bool
     */
    protected function canInvoke($aHandler, $aCommandOrEvent)
    {
        return $aHandler instanceof WorkflowProcessor;
    }

    /**
     * @param mixed $aHandler
     * @param mixed $aCommandOrEvent
     */
    protected function invoke($aHandler, $aCommandOrEvent)
    {
        $aHandler->receiveMessage($aCommandOrEvent);
    }
}
 