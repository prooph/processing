<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 20:56
 */

namespace Ginger\Message\Service;

use Ginger\Message\Factory\GingerCommandFactoryFactory;
use Ginger\Message\Factory\GingerEventFactoryFactory;
use Ginger\Message\Factory\GingerMessageFactoryFactory;
use Prooph\ServiceBus\LifeCycleEvent\InitializeEvent;
use Prooph\ServiceBus\Service\ServiceBusManager;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;

/**
 * Class ServiceBusGingerFactoriesProvider
 *
 * @package Ginger\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ServiceBusGingerFactoriesProvider extends AbstractListenerAggregate
{
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
        $this->listeners[] = $events->attach(InitializeEvent::NAME, array($this, "onInitialize"));
    }

    public function onInitialize(InitializeEvent $event)
    {
        $serviceBusManager = $event->getServiceBusManager();

        $serviceBusManager->getCommandFactoryLoader()->addAbstractFactory(new GingerCommandFactoryFactory());
        $serviceBusManager->getEventFactoryLoader()->addAbstractFactory(new GingerEventFactoryFactory());
        $serviceBusManager->getMessageFactoryLoader()->addAbstractFactory(new GingerMessageFactoryFactory());
    }
}
 