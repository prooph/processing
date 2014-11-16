<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 23:42
 */

namespace Ginger\Environment;

use Codeliner\ArrayReader\ArrayReader;
use Ginger\Processor\Definition;
use Ginger\Processor\WorkflowEngine;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Class ServicesAwareWorkflowEngine
 *
 * @package Ginger\Environment
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ServicesAwareWorkflowEngine implements WorkflowEngine
{
    /**
     * @var ServiceManager
     */
    private $services;

    /**
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        $this->services = $serviceManager;
    }

    /**
     * @param string $target
     * @return CommandBus
     * @throws \RuntimeException
     */
    public function getCommandBusFor($target)
    {
        $commandBus = $this->services->get('ginger.command_bus.' . (string)$target);

        if (! $commandBus instanceof CommandBus) throw new \RuntimeException(sprintf(
            "CommandBus for target %s must be of type Prooph\ServiceBus\CommandBus but type of %s given!",
            (string)$target,
            (is_object($commandBus))? get_class($commandBus) : gettype($commandBus)
        ));

        return $commandBus;
    }

    /**
     * @param $target
     * @return EventBus
     * @throws \RuntimeException
     */
    public function getEventBusFor($target)
    {
        $eventBus = $this->services->get('ginger.event_bus.' . (string)$target);

        if (! $eventBus instanceof EventBus) throw new \RuntimeException(sprintf(
            "CommandBus for target %s must be of type Prooph\ServiceBus\EventBus but type of %s given!",
            (string)$target,
            (is_object($eventBus))? get_class($eventBus) : gettype($eventBus)
        ));

        return $eventBus;
    }

    /**
     * @param ListenerAggregateInterface $plugin
     * @return void
     */
    public function attachPluginToAllCommandBuses(ListenerAggregateInterface $plugin)
    {
        /** @var $env Environment */
        $env = $this->services->get(Definition::SERVICE_ENVIRONMENT);

        foreach ($env->getConfig()->arrayValue('buses') as $busConfig) {
            $busConfig = new ArrayReader($busConfig);

            if ($busConfig->stringValue('type') === Definition::ENV_CONFIG_TYPE_COMMAND_BUS) {
                foreach ($busConfig->arrayValue('targets') as $target) {
                    $this->getCommandBusFor($target)->utilize($plugin);
                }
            }
        }
    }

    /**
     * @param ListenerAggregateInterface $plugin
     * @return void
     */
    public function attachPluginToAllEventBuses(ListenerAggregateInterface $plugin)
    {
        /** @var $env Environment */
        $env = $this->services->get(Definition::SERVICE_ENVIRONMENT);

        foreach ($env->getConfig()->arrayValue('buses') as $busConfig) {
            $busConfig = new ArrayReader($busConfig);

            if ($busConfig->stringValue('type') === Definition::ENV_CONFIG_TYPE_EVENT_BUS) {
                foreach ($busConfig->arrayValue('targets') as $target) {
                    $this->getEventBusFor($target)->utilize($plugin);
                }
            }
        }
    }
}
 