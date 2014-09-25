<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.07.14 - 15:35
 */

namespace Ginger\Message;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;

/**
 * Interface WorkflowMessageHandler
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface WorkflowMessageHandler 
{
    /**
     * @param WorkflowMessage $aWorkflowMessage
     * @return void
     */
    public function handleWorkflowMessage(WorkflowMessage $aWorkflowMessage);

    /**
     * Register command bus that can be used to send new commands to the workflow processor
     *
     * @param CommandBus $commandBus
     * @return void
     */
    public function useCommandBus(CommandBus $commandBus);

    /**
     * Register event bus that can be used to send events to the workflow processor
     *
     * @param EventBus $eventBus
     * @return void
     */
    public function useEventBus(EventBus $eventBus);
}
 