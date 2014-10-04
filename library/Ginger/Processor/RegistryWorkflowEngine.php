<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.09.14 - 21:23
 */

namespace Ginger\Processor;

use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;

/**
 * Class RegistryWorkflowEngine
 *
 * Register all required infrastructure via register* methods at runtime
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class RegistryWorkflowEngine implements WorkflowEngine
{
    private $commandBusMap = array();

    private $eventBusMap = array();

    /**
     * @param CommandBus $commandBus
     * @param array $targets
     * @throws \RuntimeException
     */
    public function registerCommandBus(CommandBus $commandBus, array $targets)
    {
        \Assert\that($targets)->all()->notEmpty()->string();

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
        \Assert\that($targets)->all()->notEmpty()->string();

        foreach ($targets as $target) {
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
     * @param string $target
     * @return CommandBus
     * @throws \RuntimeException
     */
    public function getCommandBusFor($target)
    {
        \Assert\that($target)->string();

        if (! isset($this->commandBusMap[$target])) {
            throw new \RuntimeException(sprintf(
                "Target %s is not connected with a command bus",
                $target
            ));
        }

        return $this->commandBusMap[$target];
    }

    /**
     * @param $target
     * @return EventBus
     * @throws \RuntimeException
     */
    public function getEventBusFor($target)
    {
        \Assert\that($target)->string();

        if (! isset($this->eventBusMap[$target])) {
            throw new \RuntimeException(sprintf(
                "Target %s is not connected with an event bus",
                $target
            ));
        }

        return $this->eventBusMap[$target];
    }
}
 