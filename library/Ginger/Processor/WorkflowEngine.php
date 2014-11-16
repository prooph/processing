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
use Zend\EventManager\ListenerAggregateInterface;

/**
 * Class WorkflowEngine
 *
 * Provides necessary infrastructure for a Process to run accordingly.
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface WorkflowEngine
{
    /**
     * @param string $target
     * @return CommandBus
     * @throws \RuntimeException
     */
    public function getCommandBusFor($target);


    /**
     * @param $target
     * @return EventBus
     * @throws \RuntimeException
     */
    public function getEventBusFor($target);

    /**
     * @param ListenerAggregateInterface $plugin
     * @return void
     */
    public function attachPluginToAllCommandBuses(ListenerAggregateInterface $plugin);

    /**
     * @param ListenerAggregateInterface $plugin
     * @return void
     */
    public function attachPluginToAllEventBuses(ListenerAggregateInterface $plugin);
}
 