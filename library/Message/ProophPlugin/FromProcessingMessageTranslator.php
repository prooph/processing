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

use Prooph\Processing\Message\WorkflowMessage;
use Prooph\ServiceBus\Message\MessageInterface;
use Prooph\ServiceBus\Message\ToMessageTranslatorInterface;

/**
 * Class FromProcessingMessageTranslator
 *
 * @package Prooph\Processing\Message\ProophPlugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class FromProcessingMessageTranslator implements ToMessageTranslatorInterface
{
    /**
     * @param $aCommandOrEvent
     * @return bool
     */
    public function canTranslateToMessage($aCommandOrEvent)
    {
        return $aCommandOrEvent instanceof ServiceBusTranslatableMessage;
    }

    /**
     * @param WorkflowMessage $aCommandOrEvent
     * @throws \RuntimeException
     * @return MessageInterface
     */
    public function translateToMessage($aCommandOrEvent)
    {
        return $aCommandOrEvent->toServiceBusMessage();
    }
}
 