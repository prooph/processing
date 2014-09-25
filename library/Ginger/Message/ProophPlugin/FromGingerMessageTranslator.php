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

use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Prooph\ServiceBus\Message\MessageHeader;
use Prooph\ServiceBus\Message\MessageInterface;
use Prooph\ServiceBus\Message\StandardMessage;
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
        return $aCommandOrEvent instanceof WorkflowMessage;
    }

    /**
     * @param WorkflowMessage $aCommandOrEvent
     * @throws \RuntimeException
     * @return MessageInterface
     */
    public function translateToMessage($aCommandOrEvent)
    {
        $messageType = null;

        if (MessageNameUtils::isGingerCommand($aCommandOrEvent->getMessageName()))
            $messageType = MessageHeader::TYPE_COMMAND;
        else if (MessageNameUtils::isGingerEvent($aCommandOrEvent->getMessageName()))
            $messageType = MessageHeader::TYPE_EVENT;
        else
            throw new \RuntimeException(sprintf(
                'Ginger message %s can not be converted to service bus message. Type of the message could not be detected',
                $aCommandOrEvent->getMessageName()
            ));

        $messageHeader = new MessageHeader(
            $aCommandOrEvent->getUuid(),
            $aCommandOrEvent->getCreatedOn(),
            $aCommandOrEvent->getVersion(),
            $messageType
        );

        return new StandardMessage(
            $aCommandOrEvent->getMessageName(),
            $messageHeader,
            array('json' => json_encode($aCommandOrEvent->getPayload()))
        );
    }
}
 