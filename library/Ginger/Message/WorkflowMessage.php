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

use Ginger\Type\Exception\InvalidTypeException;
use Ginger\Type\Prototype;
use Ginger\Type\Type;
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
    protected $messageName;

    /**
     * @var Uuid
     */
    protected $uuid;

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
     * @param Payload $payload
     * @param string $messageName
     * @param Uuid|null $uuid
     * @param int $version
     * @param \DateTime|null $createdOn
     */
    protected function __construct(
        Payload $payload,
        $messageName,
        Uuid $uuid = null,
        $version = 1,
        \DateTime $createdOn = null
    ) {
        $this->payload = $payload;

        \Assert\that($messageName)->notEmpty()->string();

        $this->messageName = $messageName;

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

        $this->messageName = MessageNameUtils::getDataCollectedEventName($type);

        $this->version++;

        $this->payload->replaceData($collectedPayload->getData());
    }

    /**
     * Transforms current message to a process data command
     */
    public function prepareDataProcessing()
    {
        $type = MessageNameUtils::getTypePartOfMessageName($this->messageName);

        $this->messageName = MessageNameUtils::getProcessDataCommandName($type);

        $this->version++;
    }

    /**
     * Transforms current message to a data processed event
     */
    public function answerWithDataProcessingCompleted()
    {
        $type = MessageNameUtils::getTypePartOfMessageName($this->messageName);

        $this->messageName = MessageNameUtils::getDataProcessedEventName($type);

        $this->version++;
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
 