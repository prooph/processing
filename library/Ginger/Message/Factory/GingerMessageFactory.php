<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 20:16
 */

namespace Ginger\Message\Factory;

use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Prooph\ServiceBus\Message\MessageFactoryInterface;
use Prooph\ServiceBus\Message\MessageHeader;
use Prooph\ServiceBus\Message\MessageInterface;
use Prooph\ServiceBus\Message\StandardMessage;

/**
 * Class GingerMessageFactory
 *
 * @package Ginger\Message\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class GingerMessageFactory implements MessageFactoryInterface
{
    /**
     * @param mixed $aCommand
     * @param string $aSenderName
     * @return MessageInterface
     */
    public function fromCommand($aCommand, $aSenderName)
    {
        return $this->fromWorkflowMessage($aCommand, $aSenderName);
    }

    /**
     * @param mixed $anEvent
     * @param string $aSenderName
     * @return MessageInterface
     */
    public function fromEvent($anEvent, $aSenderName)
    {
        return $this->fromWorkflowMessage($anEvent, $aSenderName);
    }

    /**
     * @param WorkflowMessage $aWorkflowMessage
     * @param string $aSenderName
     * @return \Prooph\ServiceBus\Message\StandardMessage
     * @throws \RuntimeException If message type can not be detected
     */
    protected function fromWorkflowMessage(WorkflowMessage $aWorkflowMessage, $aSenderName)
    {
        $messageType = null;

        if (MessageNameUtils::isGingerCommand($aWorkflowMessage->getMessageName()))
            $messageType = MessageHeader::TYPE_COMMAND;
        else if (MessageNameUtils::isGingerEvent($aWorkflowMessage->getMessageName()))
            $messageType = MessageHeader::TYPE_EVENT;
        else
            throw new \RuntimeException(sprintf(
                'Ginger message %s can not be converted to service bus message. Type of the message could not be detected',
                $aWorkflowMessage->getMessageName()
            ));

        $messageHeader = new MessageHeader(
            $aWorkflowMessage->getUuid(),
            $aWorkflowMessage->getCreatedOn(),
            $aWorkflowMessage->getVersion(),
            $aSenderName,
            $messageType
        );

        return new StandardMessage(
            $aWorkflowMessage->getMessageName(),
            $messageHeader,
            array('json' => json_encode($aWorkflowMessage->getPayload()))
        );
    }
}
 