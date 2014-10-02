<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.07.14 - 18:57
 */

namespace GingerTest\Mock;

use Ginger\Message\WorkflowMessage;
use Ginger\Message\WorkflowMessageHandler;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;

/**
 * Class TestWorkflowMessageHandler
 *
 * @package GingerTest\Type\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TestWorkflowMessageHandler implements WorkflowMessageHandler
{
    /**
     * @var WorkflowMessage
     */
    protected $lastWorkflowMessage;

    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var EventBus
     */
    protected $eventBus;

    /**
     * @param WorkflowMessage $aWorkflowMessage
     * @return void
     */
    public function handleWorkflowMessage(WorkflowMessage $aWorkflowMessage)
    {
        $this->lastWorkflowMessage = $aWorkflowMessage;
    }

    /**
     * @return WorkflowMessage
     */
    public function lastWorkflowMessage()
    {
        return $this->lastWorkflowMessage;
    }

    /**
     * Register command bus that can be used to send new commands to the workflow processor
     *
     * @param CommandBus $commandBus
     * @return void
     */
    public function useCommandBus(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * Register event bus that can be used to send events to the workflow processor
     *
     * @param EventBus $eventBus
     * @return void
     */
    public function useEventBus(EventBus $eventBus)
    {
        $this->eventBus = $eventBus;
    }
}
 