<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 25.09.14 - 18:37
 */

namespace Ginger\Message\ProophPlugin;

use Ginger\Message\WorkflowMessage;
use Prooph\ServiceBus\Message\MessageInterface;
use Prooph\ServiceBus\Message\ToMessageTranslatorInterface;

/**
 * Class FromGingerMessageTranslator
 *
 * @package Ginger\Message\ProophPlugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class FromGingerMessageTranslator implements ToMessageTranslatorInterface
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
 