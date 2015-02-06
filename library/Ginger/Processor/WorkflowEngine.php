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
     * The workflow engine automatically detects the channel for a message.
     *
     * It uses GingerMessage::origin(), GingerMessage::target() and optionally the $sender to detect the right channel.
     * If a service bus message is given the workflow engine translates it to a ginger message first.
     * If translation is not possible it should throw a InvalidArgumentException
     *
     * @param MessageInterface|GingerMessage $message
     * @param null|string $sender
     * @return void
     */
    public function dispatch($message, $sender = null);

    /**
     * @param ListenerAggregateInterface $plugin
     * @return void
     */
    public function attachPluginToAllChannels(ListenerAggregateInterface $plugin);
}
 