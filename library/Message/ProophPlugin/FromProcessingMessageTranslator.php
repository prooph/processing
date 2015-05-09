<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 25.09.14 - 18:37
 */

namespace Prooph\Processing\Message\ProophPlugin;

use Prooph\Common\Messaging\RemoteMessage;
use Prooph\ServiceBus\Message\ToRemoteMessageTranslator;

/**
 * Class FromProcessingMessageTranslator
 *
 * @package Prooph\Processing\Message\ProophPlugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class FromProcessingMessageTranslator implements ToRemoteMessageTranslator
{
    /**
     * @param $domainMessage
     * @return bool
     */
    public function canTranslateToRemoteMessage($domainMessage)
    {
        return $domainMessage instanceof ServiceBusTranslatableMessage;
    }

    /**
     * @param mixed $domainMessage
     * @return RemoteMessage
     */
    public function translateToRemoteMessage($domainMessage)
    {
        return $domainMessage->toServiceBusMessage();
    }
}
 