<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.09.14 - 21:23
 */

namespace Prooph\Processing\Processor;

use Assert\Assertion;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;

/**
 * Class RegistryWorkflowEngine
 *
 * Register all required infrastructure via register* methods at runtime
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class RegistryWorkflowEngine extends AbstractWorkflowEngine
{
    private $commandBusMap = array();

    private $eventBusMap = array();

    private $commandBusList = array();

    private $eventBusList = array();

    /**
     * @param CommandBus $commandBus
     * @param array $targets
     * @throws \RuntimeException
     */
    public function registerCommandBus(CommandBus $commandBus, array $targets)
    {
        foreach($targets as $target) {
            Assertion::notEmpty($target);
            Assertion::string($target);
        }

        $registered = false;

        foreach($this->commandBusList as $registeredCommandBus) {
            if ($registeredCommandBus === $commandBus) {
                $registered = true;
                break;
            }
        }

        if (!$registered)
            $this->commandBusList[] = $commandBus;

        foreach ($targets as $target) {
            if (isset($this->commandBusMap[$target])) {
                throw new \RuntimeException(sprintf(
                    "Target %s is already connected with a command bus",
                    $target
                ));
            }

            $this->commandBusMap[$target] = $commandBus;
        }
    }

    /**
     * @param EventBus $eventBus
     * @param array $targets
     * @throws \RuntimeException
     */
    public function registerEventBus(EventBus $eventBus, array $targets)
    {
        $registered = false;

        foreach($this->eventBusList as $registeredEventBus) {
            if ($registeredEventBus === $eventBus) {
                $registered = true;
                break;
            }
        }

        if (!$registered)
            $this->eventBusList[] = $eventBus;

        foreach ($targets as $target) {
            Assertion::notEmpty($target);
            Assertion::string($target);

            if (isset($this->eventBusMap[$target])) {
                throw new \RuntimeException(sprintf(
                    "Target %s is already connected with an event bus",
                    $target
                ));
            }

            $this->eventBusMap[$target] = $eventBus;
        }
    }

    /**
     * @inheritdoc
     */
    public function getCommandChannelFor($target, $origin = null, $sender = null)
    {
        Assertion::string($target);

        if (! isset($this->commandBusMap[$target])) {
            throw new \RuntimeException(sprintf(
                "Target %s is not connected with a command channel",
                $target
            ));
        }

        return $this->commandBusMap[$target];
    }

    /**
     * @inheritdoc
     */
    public function getEventChannelFor($target, $origin = null, $sender = null)
    {
        if (is_null($target)) $target = self::LOCAL_CHANNEL;

        Assertion::string($target);

        if (! isset($this->eventBusMap[$target])) {
            throw new \RuntimeException(sprintf(
                "Target %s is not connected with an event channel",
                $target
            ));
        }

        return $this->eventBusMap[$target];
    }

    /**
     * @param ActionEventListenerAggregate $plugin
     * @return void
     */
    public function attachPluginToAllChannels(ActionEventListenerAggregate $plugin)
    {
        foreach ($this->commandBusList as $commandBus) $commandBus->utilize($plugin);
        foreach ($this->eventBusList as $eventBus) $eventBus->utilize($plugin);
    }
}
 