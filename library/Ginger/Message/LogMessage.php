<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 02.10.14 - 21:21
 */

namespace Ginger\Message;

use Ginger\Message\ProophPlugin\ServiceBusTranslatableMessage;
use Ginger\Processor\ProcessId;
use Prooph\ServiceBus\Message\MessageHeader;
use Prooph\ServiceBus\Message\MessageInterface;
use Prooph\ServiceBus\Message\MessageNameProvider;
use Prooph\ServiceBus\Message\StandardMessage;
use Rhumsaa\Uuid\Uuid;

/**
 * Class LogMessage
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class LogMessage implements MessageNameProvider, ServiceBusTranslatableMessage
{
    /**
     * @var Uuid
     */
    private $uuid;

    /**
     * @var ProcessId
     */
    private $processId;

    /**
     * @var int
     */
    private $msgCode;

    /**
     * @var array[placeholderKey => scalarValue]
     */
    private $msgParams;

    /**
     * @var string
     */
    private $technicalMsg;

    /**
     * @var \DateTime
     */
    private $createdOn;

    /**
     * @param MessageInterface $aMessage
     * @return WorkflowMessage
     * @throws \RuntimeException
     */
    public static function fromServiceBusMessage(MessageInterface $aMessage)
    {
        $payload = $aMessage->payload();

        \Assert\that($payload)->keyExists('processId')
            ->keyExists('technicalMsg')
            ->keyExists('msgParams')
            ->keyExists('msgCode');

        $processId = ProcessId::fromString($payload['processId']);

        return new self(
            $processId,
            $payload['technicalMsg'],
            $payload['msgCode'],
            $payload['msgParams'],
            $aMessage->header()->uuid(),
            $aMessage->header()->createdOn()
        );
    }

    /**
     * @param ProcessId $processId
     * @param string $technicalMsg
     * @param int $msgCode
     * @param array $msgParams
     * @param Uuid $uuid
     * @param \DateTime $createdOn
     * @throws \InvalidArgumentException
     */
    private function __construct(ProcessId $processId, $technicalMsg, $msgCode = 0, array $msgParams = array(), Uuid $uuid = null, \DateTime $createdOn = null)
    {
        \Assert\that($technicalMsg)->string();
        \Assert\that($msgCode)->integer();

        foreach ($msgParams as $key => $param) {
            if (! is_scalar($param)) {
                throw new \InvalidArgumentException(sprintf(
                    'Msg param %s needs to be a scalar type but type of %s given',
                    $key,
                    (is_object($param))? get_class($param) : gettype($param)
                ));
            }
        }

        $this->technicalMsg = $technicalMsg;
        $this->msgCode = $msgCode;
        $this->msgParams = $msgParams;
        $this->processId = $processId;

        if (is_null($uuid)) {
            $uuid = Uuid::uuid4();
        }

        $this->uuid = $uuid;

        if (is_null($createdOn)) {
            $createdOn = new \DateTime();
        }

        $this->createdOn = $createdOn;
    }

    /**
     * @return string Name of the message
     */
    public function getMessageName()
    {
        return MessageNameUtils::LOG_MESSAGE_NAME;
    }

    /**
     * @return MessageInterface
     */
    public function toServiceBusMessage()
    {
        $header = MessageHeader::fromArray(array(
            $this->getUuid(),
            $this->getCreatedOn(),
            1,
            MessageHeader::TYPE_EVENT
        ));

        return new StandardMessage(
            $this->getMessageName(),
            $header,
            [
                'processId' => $this->getProcessId()->toString(),
                'technicalMsg' => $this->getTechnicalMsg(),
                'msgParams' => $this->getMsgParams(),
                 'msgCode' => $this->getMsgCode()
            ]
        );
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return $this->msgCode;
    }

    /**
     * @return array
     */
    public function getMsgParams()
    {
        return $this->msgParams;
    }

    /**
     * @return ProcessId
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * @return string
     */
    public function getTechnicalMsg()
    {
        return $this->technicalMsg;
    }

    /**
     * @return Uuid
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }
}
 