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

use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Messaging\RemoteMessage;
use Prooph\Processing\Message\ProcessingMessage;

/**
 * Interface WorkflowEngine
 *
 * Provides communication layer for the processing environment.
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface WorkflowEngine
{
    /**
     * The workflow engine automatically detects the channel for a message.
     *
     * It uses ProcessingMessage::origin(), ProcessingMessage::target() and optionally the $sender to detect the right channel.
     * If a service bus message is given the workflow engine translates it to a processing message first.
     * If translation is not possible it should throw a InvalidArgumentException
     *
     * @param RemoteMessage|ProcessingMessage $message
     * @param null|string $sender
     * @return void
     */
    public function dispatch($message, $sender = null);

    /**
     * @param ActionEventListenerAggregate $plugin
     * @return void
     */
    public function attachPluginToAllChannels(ActionEventListenerAggregate $plugin);
}
 