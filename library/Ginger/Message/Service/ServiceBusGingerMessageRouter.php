<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 21:31
 */

namespace Ginger\Message\Service;

use Ginger\Message\MessageNameUtils;
use Prooph\ServiceBus\Message\MessageNameProvider;
use Prooph\ServiceBus\Service\ServiceBusManager;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;

/**
 * Class ServiceBusGingerMessageRouter
 *
 * @package Ginger\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ServiceBusGingerMessageRouter extends AbstractListenerAggregate
{
    const BUS_NAME_PATTERN = 'ginger-%s-bus';

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach("route", array($this, "onRoute"));
    }

    public function onRoute(Event $event)
    {
        /** @var $serviceBusManager ServiceBusManager */
        $serviceBusManager = $event->getTarget();

        $message = $event->getParam('message');

        if (! $message instanceof MessageNameProvider) return;

        if (! MessageNameUtils::isGingerMessage($message->getMessageName())) return;

        $type = MessageNameUtils::getTypePartOfMessageName($message->getMessageName());

        $busName = sprintf(static::BUS_NAME_PATTERN, $type);

        if (MessageNameUtils::isGingerCommand($message->getMessageName())) {
            $bus = $serviceBusManager->getCommandBus($busName);

            $bus->send($message);
        } else {
            $bus = $serviceBusManager->getEventBus($busName);

            $bus->publish($message);
        }

        return true;
    }
}
 