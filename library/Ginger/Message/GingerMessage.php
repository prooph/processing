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

/**
 * Interface GingerMessage
 *
 * Forces all ginger message to provide target information.
 * With the target the workflow engine can determine the correct channel for message.
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface GingerMessage extends ServiceBusTranslatableMessage
{
    /**
     * @return string
     */
    public function messageName();
    
    /**
     * If target is null the workflow engine will use the local channel to send the message to the workflow processor.
     *
     * @return null|string
     */
    public function target();
}
 