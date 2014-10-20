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
use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\WorkflowProcessor;
use Prooph\ServiceBus\Message\MessageDispatcherInterface;
use Prooph\ServiceBus\Process\EventDispatch;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

/**
 * Class WorkflowEventRouter
 *
 * The WorkflowEventRouter routes all WorkflowMessage-Events and LogMessages to a single listener.
 * The listener should either be a WorkflowProcessor or a MessageDispatcher
 * which sends the message to the WorkflowProcessor.
 *
 * @package Ginger\Processor\ProophPlugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowEventRouter extends AbstractListenerAggregate
{
    /**
     * @var WorkflowProcessor|MessageDispatcherInterface|string
     */
    private $targetListener;

    /**
     * @param WorkflowProcessor|MessageDispatcherInterface|string $targetListener
     * @throws \InvalidArgumentException
     */
    public function __construct($targetListener)
    {
        if (is_object($targetListener)) {
            if (! $targetListener instanceof WorkflowProcessor && ! $targetListener instanceof MessageDispatcherInterface) {
                throw new \InvalidArgumentException(sprintf(
                    "Wrong TargetListener type given: %s. Allowed types are instances of Ginger\Processor\WorkflowProcessor or Prooph\ServiceBus\Message\MessageDispatcherInterface or a string",
                    get_class($targetListener)
                ));
            }
        } else {
            \Assert\that($targetListener)->notEmpty()->string();
        }

        $this->targetListener = $targetListener;
    }

    /**
     * Attach to EventBus route event
     *
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $events->attach('route', [$this, 'onRoute']);
    }

    /**
     * @param EventDispatch $dispatch
     */
    public function onRoute(EventDispatch $dispatch)
    {
        $message = $dispatch->getEvent();

        if (($message instanceof WorkflowMessage && MessageNameUtils::isGingerEvent($message->getMessageName()))
            || $message instanceof LogMessage) {
            $dispatch->setEventListeners([$this->targetListener]);
            return;
        }
    }
}
 