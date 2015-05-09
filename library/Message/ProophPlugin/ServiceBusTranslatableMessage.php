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

use Prooph\Common\Messaging\HasMessageName;
use Prooph\Common\Messaging\RemoteMessage;

/**
 * Interface ServiceBusTranslatableMessage
 *
 * This interface tells the FromProcessingMessageTranslator and ToProcessingMessageTranslator that the message
 * can itself translate from and to a service bus message.
 *
 * @package Prooph\Processing\Message\ProophPlugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface ServiceBusTranslatableMessage extends HasMessageName
{
    /**
     * @param RemoteMessage $aMessage
     * @return static
     */
    public static function fromServiceBusMessage(RemoteMessage $aMessage);

    /**
     * @return RemoteMessage
     */
    public function toServiceBusMessage();
}
 