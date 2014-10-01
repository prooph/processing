<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.07.14 - 22:07
 */

namespace Ginger\Message;

use Ginger\Processor\ProcessId;
use Ginger\Type\Exception\InvalidTypeException;
use Ginger\Type\Prototype;
use Ginger\Type\Type;
use Prooph\ServiceBus\Message\MessageInterface;
use Prooph\ServiceBus\Message\MessageNameProvider;
use Rhumsaa\Uuid\Uuid;

/**
 * Class WorkflowMessage
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowMessage implements MessageNameProvider
{
    /**
     * @var string
     */
    protected $messageName;

    /**
     * @var Uuid
     */
    protected $uuid;

    /**
     * @var ProcessId
     */
    protected $processId;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var \DateTime
     */
    protected $createdOn;

    /**
     * @var Payload
     */
    protected $payload;

    /**
     * @param Prototype $aPrototype
     * @return WorkflowMessage
     */
    public static function collectDataOf(Prototype $aPrototype)
    {
        $messageName = MessageNameUtils::getCollectDataCommandName($aPrototype->of());

        return new static(Payload::fromPrototype($aPrototype), $messageName);
    }

    /**
     * @param \Ginger\Type\Type $data
     * @return WorkflowMessage
     */
    public static function newDataCollected(Type $data)
    {
        $payload = Payload::fromType($data);

        $messageName = MessageNameUtils::getDataCollectedEventName($payload->getTypeClass());

        return new static($payload, $messageName);
    }

    /**
     * @param MessageInterface $aMessage
     * @return WorkflowMessage
     * @throws \RuntimeException
     */
    public static function fromServiceBusMessage(MessageInterface $aMessage)
    {
        $messagePayload = $aMessage->payload();

        \Assert\that($messagePayload)->keyExists('json');

        $processId = (isset($messagePayload['processId']))?
            ProcessId::reconstituteFromString($messagePayload['processId']) : null;

        $messagePayload = Payload::fromJsonDecodedData(json_decode($messagePayload['json'], true));

        return new static(
            $messagePayload,
            $aMessage->name(),
            $processId,
            $aMessage->header()->version(),
            $aMessage->header()->createdOn(),
            $aMessage->header()->uuid()
        );
    }

    /**
     * @param Payload $payload
     * @param string $messageName
     * @param ProcessId|null $processId
     * @param int $version
     * @param \DateTime|null $createdOn
     * @param Uuid|null $uuid
     */
    protected function __construct(
        Payload $payload,
        $messageName,
        ProcessId $processId = null,
        $version = 1,
        \DateTime $createdOn = null,
        Uuid $uuid = null
    ) {
        $this->payload = $payload;

        \Assert\that($messageName)->notEmpty()->string();

        $this->messageName = $messageName;

        $this->processId = $processId;

        if (is_null($uuid)) {
            $uuid = Uuid::uuid4();
        }

        $this->uuid = $uuid;

        $this->version = $version;

        if (is_null($createdOn)) {
            $createdOn = new \DateTime();
        }

        $this->createdOn = $createdOn;
    }

    /**
     * Transforms current message to a data collected event and replaces payload data with collected data
     *
     * @param Type $collectedData
     * @return WorkflowMessage
     * @throws \Ginger\Type\Exception\InvalidTypeException If answer type does not match with the previous requested type
     */
    public function answerWith(Type $collectedData)
    {
        $collectedPayload = Payload::fromType($collectedData);

        $collectedDataTypeClass = $collectedPayload->getTypeClass();

        if ($this->payload->getTypeClass() !== $collectedPayload->getTypeClass()) {
            throw InvalidTypeException::fromInvalidArgumentExceptionAndPrototype(
                new \InvalidArgumentException(sprintf(
                    "Type %s of collected data does not match the type of requested data %s",
                    $collectedPayload->getTypeClass(),
                    $this->payload->getTypeClass()
                )),
                $collectedDataTypeClass::prototype()
            );
        }

        $type = MessageNameUtils::getTypePartOfMessageName($this->messageName);

        return new self(
            $collectedPayload,
            MessageNameUtils::getDataCollectedEventName($type),
            $this->processId,
            $this->version + 1
        );
    }

    /**
     * Transforms current message to a process data command
     *
     * @return WorkflowMessage
     */
    public function prepareDataProcessing()
    {
        $type = MessageNameUtils::getTypePartOfMessageName($this->messageName);

        return new self(
            $this->payload,
            MessageNameUtils::getProcessDataCommandName($type),
            $this->processId,
            $this->version + 1
        );
    }

    /**
     * Transforms current message to a data processed event
     */
    public function answerWithDataProcessingCompleted()
    {
        $type = MessageNameUtils::getTypePartOfMessageName($this->messageName);

        return new self(
            $this->payload,
            MessageNameUtils::getDataProcessedEventName($type),
            $this->processId,
            $this->version + 1
        );
    }

    /**
     * @param ProcessId $processId
     * @throws \RuntimeException If message is already connected to process
     */
    public function connectToProcess(ProcessId $processId)
    {
        if (! is_null($this->processId)) {
            throw new \RuntimeException(sprintf(
                "Connecting WorkflowMessage %s (%s) to process %s is not possible cause it is already connected to process %s",
                $this->getMessageName(),
                $this->uuid->toString(),
                $processId->toString(),
                $this->processId->toString()
            ));
        }

        $this->processId = $processId;
    }

    /**
     * @return string Name of the message
     */
    public function getMessageName()
    {
        return $this->messageName;
    }

    /**
     * @return Payload
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return ProcessId|null
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * @return Uuid
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }
}
 