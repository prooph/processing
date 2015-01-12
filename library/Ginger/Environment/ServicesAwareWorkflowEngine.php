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
     * @var array
     */
    private $cachedChannels = [];

    /**
     * @var array
     */
    private $cachedPlugins = [];

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

        $channelName = 'ginger.command_bus.' . $target;

        if (isset($this->cachedChannels[$channelName])) return $this->cachedChannels[$channelName];

        $commandBus = $this->services->get($channelName);

        if (! $commandBus instanceof CommandBus) throw new \RuntimeException(sprintf(
            "CommandBus for target %s must be of type Prooph\ServiceBus\CommandBus but type of %s given!",
            (string)$target,
            (is_object($commandBus))? get_class($commandBus) : gettype($commandBus)
        ));

        foreach ($this->cachedPlugins as $plugin) {
            $commandBus->utilize($plugin);
        }

        $this->cachedChannels[$channelName] = $commandBus;

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

        $channelName = 'ginger.event_bus.' . $target;

        if (isset($this->cachedChannels[$channelName])) return $this->cachedChannels[$channelName];

        $eventBus = $this->services->get($channelName);

        if (! $eventBus instanceof EventBus) throw new \RuntimeException(sprintf(
            "EventBu for target %s must be of type Prooph\ServiceBus\EventBus but type of %s given!",
            (string)$target,
            (is_object($eventBus))? get_class($eventBus) : gettype($eventBus)
        ));

        foreach ($this->cachedPlugins as $plugin) {
            $eventBus->utilize($plugin);
        }

        $this->cachedChannels[$channelName] = $eventBus;

        return $eventBus;
    }

    /**
     * @param ListenerAggregateInterface $plugin
     * @return void
     */
    public function attachPluginToAllChannels(ListenerAggregateInterface $plugin)
    {
        /** @var $channel CommandBus|EventBus */
        foreach ($this->cachedChannels as $channel) {
            $channel->utilize($plugin);
        }

        $this->cachedPlugins[] = $plugin;
    }
}
 