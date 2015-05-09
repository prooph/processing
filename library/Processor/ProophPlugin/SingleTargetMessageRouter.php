<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 20.10.14 - 22:05
 */

namespace Prooph\Processing\Processor\ProophPlugin;

use Assert\Assertion;
use Prooph\Common\Event\ActionEventDispatcher;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Event\DetachAggregateHandlers;
use Prooph\Processing\Message\WorkflowMessageHandler;
use Prooph\Processing\Processor\WorkflowProcessor;
use Prooph\ServiceBus\Message\RemoteMessageDispatcher;
use Prooph\ServiceBus\Process\CommandDispatch;
use Prooph\ServiceBus\Process\EventDispatch;
use Prooph\ServiceBus\Process\MessageDispatch;

/**
 * Class SingleTargetMessageRouter
 *
 * The SingleTargetMessageRouter routes all WorkflowMessages and LogMessages to a single listener.
 * The listener should either be a WorkflowProcessor or a MessageDispatcher.
 *
 * @package Prooph\Processing\Processor\ProophPlugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SingleTargetMessageRouter implements ActionEventListenerAggregate
{
    use DetachAggregateHandlers;

    /**
     * @var WorkflowProcessor|RemoteMessageDispatcher|WorkflowMessageHandler|string
     */
    private $targetHandler;

    /**
     * @param WorkflowProcessor|RemoteMessageDispatcher|WorkflowMessageHandler|string $targetHandler
     * @throws \InvalidArgumentException
     */
    public function __construct($targetHandler)
    {
        if (is_object($targetHandler)) {
            if (! $targetHandler instanceof WorkflowProcessor
                && ! $targetHandler instanceof RemoteMessageDispatcher
                && ! $targetHandler instanceof WorkflowMessageHandler) {
                throw new \InvalidArgumentException(sprintf(
                    "Wrong TargetHandler given: %s. Allowed types are instances of Prooph\\Processing\\Processor\\WorkflowProcessor or Prooph\\ServiceBus\\Message\\RemoteMessageDispatcher or Prooph\\Processing\\Message\\WorkflowMessageHandler or a string",
                    get_class($targetHandler)
                ));
            }
        } else {
            Assertion::notEmpty($targetHandler);
            Assertion::string($targetHandler);
        }

        $this->targetHandler = $targetHandler;
    }

    /**
     * Attach to CommandBus or EventBus route event
     *
     * @param ActionEventDispatcher $events
     *
     * @return void
     */
    public function attach(ActionEventDispatcher $events)
    {
        $this->trackHandler($events->attachListener(MessageDispatch::ROUTE, array($this, 'onRouteMessage'), 100));
    }

    /**
     * @param MessageDispatch $dispatch
     */
    public function onRouteMessage(MessageDispatch $dispatch)
    {
        if ($dispatch instanceof CommandDispatch) {
            $this->onRouteCommand($dispatch);
        } else {
            $this->onRouteEvent($dispatch);
        }
    }

    /**
     * @param CommandDispatch $dispatch
     */
    public function onRouteCommand(CommandDispatch $dispatch)
    {
        $dispatch->setCommandHandler($this->targetHandler);
    }

    /**
     * @param EventDispatch $dispatch
     */
    public function onRouteEvent(EventDispatch $dispatch)
    {
        $dispatch->setEventListeners([$this->targetHandler]);
    }
}
 