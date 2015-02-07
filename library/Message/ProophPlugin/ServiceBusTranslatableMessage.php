<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 02.10.14 - 22:09
 */

namespace Prooph\Processing\Message\ProophPlugin;

use Prooph\ServiceBus\Message\MessageInterface;

/**
 * Interface ServiceBusTranslatableMessage
 *
 * This interface tells the FromProcessingMessageTranslator and ToProcessingMessageTranslator that the message
 * can itself translate from and to a service bus message.
 *
 * @package Prooph\Processing\Message\ProophPlugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface ServiceBusTranslatableMessage
{
    /**
     * @param MessageInterface $aMessage
     * @return static
     */
    public static function fromServiceBusMessage(MessageInterface $aMessage);

    /**
     * @return MessageInterface
     */
    public function toServiceBusMessage();
}
 