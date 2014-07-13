<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.07.14 - 18:43
 */

namespace Ginger\Message\Service;

use Prooph\ServiceBus\LifeCycleEvent\InitializeEvent;
use Prooph\ServiceBus\Service\Definition;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

/**
 * Class ServiceBusInvokeStrategyProvider
 *
 * @package Ginger\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ServiceBusInvokeStrategyProvider extends AbstractListenerAggregate
{
    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(InitializeEvent::NAME, array($this, "onInitialize"));
    }

    /**
     * @param InitializeEvent $event
     */
    public function onInitialize(InitializeEvent $event)
    {
        $sbm = $event->getServiceBusManager();

        $configuration = $sbm->get("configuration");

        $commandInvokeStrategies = array();
        $eventInvokeStrategies   = array();

        if (isset($configuration[Definition::COMMAND_HANDLER_INVOKE_STRATEGIES])) {
            $commandInvokeStrategies = $configuration[Definition::COMMAND_HANDLER_INVOKE_STRATEGIES];
        }

        if (isset($configuration[Definition::EVENT_HANDLER_INVOKE_STRATEGIES])) {
            $eventInvokeStrategies = $configuration[Definition::EVENT_HANDLER_INVOKE_STRATEGIES];
        }

        $commandInvokeStrategies[] = "ginger_workflow_message";
        $eventInvokeStrategies[] = "ginger_workflow_message";

        $configuration[Definition::COMMAND_HANDLER_INVOKE_STRATEGIES] = $commandInvokeStrategies;
        $configuration[Definition::EVENT_HANDLER_INVOKE_STRATEGIES] = $eventInvokeStrategies;

        $allowOverride = $sbm->getAllowOverride();

        $sbm->setAllowOverride(true);

        $sbm->setService("configuration", $configuration);

        $sbm->setAllowOverride($allowOverride);

        $sbm->getInvokeStrategyLoader()->setService("ginger_workflow_message", new HandleWorkflowMessageInvokeStrategy());
    }
}
 