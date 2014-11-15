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

use Ginger\Processor\WorkflowEngine;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
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
}
 