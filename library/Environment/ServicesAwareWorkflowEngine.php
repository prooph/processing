<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 23:42
 */

namespace Prooph\Processing\Environment;

use Codeliner\ArrayReader\ArrayReader;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Processing\Environment\Factory\AbstractChannelFactory;
use Prooph\Processing\Processor\AbstractWorkflowEngine;
use Prooph\Processing\Processor\Definition;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\WorkflowEngine;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Class ServicesAwareWorkflowEngine
 *
 * @package Prooph\Processing\Environment
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
     * @inheritdoc
     */
    public function getCommandChannelFor($target, $origin = null, $sender = null)
    {
        if (! is_string($target) || empty($target)) throw new \RuntimeException('Target must be a non empty string');

        $channelName = $this->generateChannelName($target, $origin, $sender);

        $channelName = 'processing.command_bus.' . $channelName;

        if (isset($this->cachedChannels[$channelName])) return $this->cachedChannels[$channelName];

        $commandBus = $this->services->get($channelName);

        if (! $commandBus instanceof CommandBus) throw new \RuntimeException(sprintf(
            "CommandBus for target %s must be of type Prooph\\ServiceBus\\CommandBus but type of %s given!",
            $target,
            (is_object($commandBus))? get_class($commandBus) : gettype($commandBus)
        ));

        foreach ($this->cachedPlugins as $plugin) {
            $commandBus->utilize($plugin);
        }

        $this->cachedChannels[$channelName] = $commandBus;

        return $commandBus;
    }

    /**
     * @inheritdoc
     */
    public function getEventChannelFor($target, $origin = null, $sender = null)
    {
        if (is_null($target)) $target = Definition::SERVICE_WORKFLOW_PROCESSOR;

        if (! is_string($target) || empty($target)) throw new \RuntimeException('Target must be a non empty string');

        $channelName = 'processing.event_bus.' . $target;

        if (isset($this->cachedChannels[$channelName])) return $this->cachedChannels[$channelName];

        $eventBus = $this->services->get($channelName);

        if (! $eventBus instanceof EventBus) throw new \RuntimeException(sprintf(
            "EventBus for target %s must be of type Prooph\\ServiceBus\\EventBus but type of %s given!",
            $target,
            (is_object($eventBus))? get_class($eventBus) : gettype($eventBus)
        ));

        foreach ($this->cachedPlugins as $plugin) {
            $eventBus->utilize($plugin);
        }

        $this->cachedChannels[$channelName] = $eventBus;

        return $eventBus;
    }

    /**
     * @param ActionEventListenerAggregate $plugin
     * @return void
     */
    public function attachPluginToAllChannels(ActionEventListenerAggregate $plugin)
    {
        /** @var $channel CommandBus|EventBus */
        foreach ($this->cachedChannels as $channel) {
            $channel->utilize($plugin);
        }

        $this->cachedPlugins[] = $plugin;
    }

    /**
     * @param string $target
     * @param null|string $origin
     * @param null|string $sender
     * @return string
     */
    private function generateChannelName($target, $origin = null, $sender = null)
    {
        $channelName = $target;

        if (!is_null($origin)) {
            $channelName.= AbstractChannelFactory::CHANNEL_NAME_DELIMITER . $origin;
        }

        if (!is_null($sender)) {
            $channelName.= AbstractChannelFactory::CHANNEL_NAME_DELIMITER . $sender;
        }

        return $channelName;
    }
}
 