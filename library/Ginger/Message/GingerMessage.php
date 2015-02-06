<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.01.15 - 17:00
 */

namespace Ginger\Message;

use Ginger\Message\ProophPlugin\ServiceBusTranslatableMessage;
use Rhumsaa\Uuid\Uuid;

/**
 * Interface GingerMessage
 *
 * Forces all ginger messages to provide some meta information used for routing and logging.
 * With the origin and target the workflow engine can determine the correct channel for a message.
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface GingerMessage extends ServiceBusTranslatableMessage
{
    /**
     * @return Uuid
     */
    public function uuid();

    /**
     * @return string
     */
    public function messageName();

    /**
     * The target defines the receiver of the message.
     * This can either be a workflow message handler
     * or the workflow processor of a ginger node.
     *
     * @return string
     */
    public function target();

    /**
     * The origin defines the component which sent the message.
     * Like the target this can either be a workflow message handler
     * or a workflow processor.
     *
     * @return string
     */
    public function origin();
}
 