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

use Ginger\Message\GingerMessage;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\MessageInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * Class WorkflowEngine
 *
 * Provides communication layer for the ginger environment.
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface WorkflowEngine
{
    /**
     * The workflow engine automatically detects the change channel for the message.
     *
     * It uses GingerMessage::target() to detect the right channel.
     * If GingerMessage::target() returns null the "local" channel is used to dispatch the message.
     * If a service bus message is given the workflow engine translates it to a ginger message first.
     * If translation is not possible it should throw a InvalidArgumentException
     *
     * @param MessageInterface|GingerMessage $message
     * @return void
     * @throws \InvalidArgumentException
     */
    public function dispatch($message);

    /**
     * If target is null, the local channel is returned
     *
     * @param null|string $target
     * @return CommandBus
     * @throws \RuntimeException
     */
    public function getCommandChannelFor($target);


    /**
     * If target is null, the local channel is returned
     *
     * @param null|string $target
     * @return EventBus
     * @throws \RuntimeException
     */
    public function getEventChannelFor($target);

    /**
     * @param ListenerAggregateInterface $plugin
     * @return void
     */
    public function attachPluginToAllChannels(ListenerAggregateInterface $plugin);
}
 