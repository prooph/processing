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
use Ginger\Processor\AbstractWorkflowEngine;
use Ginger\Processor\Definition;
use Ginger\Processor\NodeName;
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
class ServicesAwareWorkflowEngine extends AbstractWorkflowEngine
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
     * @param null|string $target
     * @throws \RuntimeException
     * @return CommandBus
     */
    public function getCommandChannelFor($target)
    {
        if (is_null($target)) $target = Definition::SERVICE_WORKFLOW_PROCESSOR;

        if (! is_string($target) || empty($target)) throw new \RuntimeException('Target must be a non empty string');

        $commandBus = $this->services->get('ginger.command_bus.' . $target);

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
    public function getEventChannelFor($target)
    {
        if (is_null($target)) $target = Definition::SERVICE_WORKFLOW_PROCESSOR;

        if (! is_string($target) || empty($target)) throw new \RuntimeException('Target must be a non empty string');

        $eventBus = $this->services->get('ginger.event_bus.' . $target);

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
    public function attachPluginToAllChannels(ListenerAggregateInterface $plugin)
    {
        /** @var $env Environment */
        $env = $this->services->get(Definition::SERVICE_ENVIRONMENT);

        foreach ($env->getConfig()->arrayValue('channels') as $channelConfig) {
            $channelConfig = new ArrayReader($channelConfig);

            foreach ($channelConfig->arrayValue('targets') as $target) {
                $this->getCommandChannelFor($target)->utilize($plugin);
                $this->getEventChannelFor($target)->utilize($plugin);
            }

        }
    }
}
 