<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 20.10.14 - 22:05
 */

namespace Ginger\Processor\ProophPlugin;

use Ginger\Message\LogMessage;
use Ginger\Message\WorkflowMessage;
use Ginger\Message\WorkflowMessageHandler;
use Ginger\Processor\Command\StartSubProcess;
use Ginger\Processor\WorkflowProcessor;
use Prooph\ServiceBus\Message\MessageDispatcherInterface;
use Prooph\ServiceBus\Process\CommandDispatch;
use Prooph\ServiceBus\Process\EventDispatch;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

/**
 * Class SingleTargetMessageRouter
 *
 * The SingleTargetMessageRouter routes all WorkflowMessages and LogMessages to a single listener.
 * The listener should either be a WorkflowProcessor or a MessageDispatcher.
 *
 * @package Ginger\Processor\ProophPlugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SingleTargetMessageRouter extends AbstractListenerAggregate
{
    /**
     * @var WorkflowProcessor|MessageDispatcherInterface|WorkflowMessageHandler|string
     */
    private $targetHandler;

    /**
     * @param WorkflowProcessor|MessageDispatcherInterface|WorkflowMessageHandler|string $targetHandler
     * @throws \InvalidArgumentException
     */
    public function __construct($targetHandler)
    {
        if (is_object($targetHandler)) {
            if (! $targetHandler instanceof WorkflowProcessor
                && ! $targetHandler instanceof MessageDispatcherInterface
                && ! $targetHandler instanceof WorkflowMessageHandler) {
                throw new \InvalidArgumentException(sprintf(
                    "Wrong TargetHandler given: %s. Allowed types are instances of Ginger\Processor\WorkflowProcessor or Prooph\ServiceBus\Message\MessageDispatcherInterface or Ginger\Message\WorkflowMessageHandler or a string",
                    get_class($targetHandler)
                ));
            }
        } else {
            \Assert\that($targetHandler)->notEmpty()->string();
        }

        $this->targetHandler = $targetHandler;
    }

    /**
     * Attach to CommandBus or EventBus route event
     *
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $identifiers = $events->getIdentifiers();

        if (in_array('command_bus', $identifiers)) {
            $this->listeners[] = $events->attach(CommandDispatch::ROUTE, array($this, 'onRouteCommand'), 100);
        }

        if (in_array('event_bus', $identifiers)) {
            $this->listeners[] = $events->attach(EventDispatch::ROUTE, array($this, 'onRouteEvent'), 100);
        }
    }

    /**
     * @param CommandDispatch $dispatch
     */
    public function onRouteCommand(CommandDispatch $dispatch)
    {
        $message = $dispatch->getCommand();

        if ($message instanceof WorkflowMessage || $message instanceof StartSubProcess) {
            $dispatch->setCommandHandler($this->targetHandler);
        }
    }

    /**
     * @param EventDispatch $dispatch
     */
    public function onRouteEvent(EventDispatch $dispatch)
    {
        $message = $dispatch->getEvent();

        if ($message instanceof WorkflowMessage || $message instanceof LogMessage) {
            $dispatch->setEventListeners([$this->targetHandler]);
        }
    }
}
 