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

use Assert\Assertion;
use Ginger\Message\ProophPlugin\ServiceBusTranslatableMessage;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\TaskListPosition;
use Ginger\Type\Exception\InvalidTypeException;
use Ginger\Type\Prototype;
use Ginger\Type\Type;
use Prooph\ServiceBus\Message\MessageHeader;
use Prooph\ServiceBus\Message\MessageInterface;
use Prooph\ServiceBus\Message\MessageNameProvider;
use Prooph\ServiceBus\Message\StandardMessage;
use Rhumsaa\Uuid\Uuid;
use Zend\Stdlib\ArrayUtils;

/**
 * Class WorkflowMessage
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowMessage implements MessageNameProvider, GingerMessage
{
    /**
     * @var string
     */
    protected $messageName;

    /**
     * The target is either a workflow engine channel target or null
     *
     * By default target is null. In this case the message is addressed to the local ginger node.
     * If the message is connected to a process task and target is null then the property getter target()
     * returns the processor node name from the TaskListPosition as target.
     *
     * @var null|string
     */
    protected $target;

    /**
     * @var Uuid
     */
    protected $uuid;

    /**
     * @var TaskListPosition
     */
    protected $processTaskListPosition;

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
     * @var array
     */
    protected $metadata;

    /**
     * @param Prototype $aPrototype
     * @param array $metadata
     * @param null|string $target
     * @return WorkflowMessage
     */
    public static function collectDataOf(Prototype $aPrototype, array $metadata = [], $target = null)
    {
        $messageName = MessageNameUtils::getCollectDataCommandName($aPrototype->of());

        return new static(Payload::fromPrototype($aPrototype), $messageName, $metadata, $target);
    }

    /**
     * @param \Ginger\Type\Type $data
     * @param array $metadata
     * @param null|string $target
     * @return WorkflowMessage
     */
    public static function newDataCollected(Type $data, array $metadata = [], $target = null)
    {
        $payload = Payload::fromType($data);

        $messageName = MessageNameUtils::getDataCollectedEventName($payload->getTypeClass());

        return new static($payload, $messageName, $metadata, $target);
    }

    /**
     * @param MessageInterface $aMessage
     * @return WorkflowMessage
     * @throws \RuntimeException
     */
    public static function fromServiceBusMessage(MessageInterface $aMessage)
    {
        $payload = $aMessage->payload();

        Assertion::keyExists($payload, 'json');

        $target = (isset($payload['target']))? $payload['target'] : null;

        $taskListPosition = (isset($payload['processTaskListPosition']))?
            TaskListPosition::fromString($payload['processTaskListPosition']) : null;

        $messagePayload = Payload::fromJsonDecodedData(json_decode($payload['json'], true));

        $metadata = isset($payload['metadata'])? $payload['metadata'] : [];

        return new static(
            $messagePayload,
            $aMessage->name(),
            $metadata,
            $target,
            $taskListPosition,
            $aMessage->header()->version(),
            $aMessage->header()->createdOn(),
            $aMessage->header()->uuid()
        );
    }

    /**
     * @param Payload $payload
     * @param string $messageName
     * @param array|null $metadata
     * @param string|null $target
     * @param TaskListPosition|null $taskListPosition
     * @param int $version
     * @param \DateTime|null $createdOn
     * @param Uuid|null $uuid
     */
    protected function __construct(
        Payload $payload,
        $messageName,
        array $metadata,
        $target = null,
        TaskListPosition $taskListPosition = null,
        $version = 1,
        \DateTime $createdOn = null,
        Uuid $uuid = null
    ) {
        $this->payload = $payload;

        Assertion::notEmpty($messageName);
        Assertion::string($messageName);

        if (!is_null($target)) {
            Assertion::notEmpty($target);
            Assertion::string($target);
        }

        $this->messageName = $messageName;

        $this->target = $target;

        $this->assertMetadata($metadata);

        $this->metadata = $metadata;

        $this->processTaskListPosition = $taskListPosition;

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
     * @param array $metadata
     * @throws \Ginger\Type\Exception\InvalidTypeException If answer type does not match with the previous requested type
     * @return WorkflowMessage
     */
    public function answerWith(Type $collectedData, array $metadata = [])
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

        $metadata = ArrayUtils::merge($this->metadata, $metadata);

        return new self(
            $collectedPayload,
            MessageNameUtils::getDataCollectedEventName($type),
            $metadata,
            null, //Target is not set, so it defaults to node name of TaskListPosition->TaskListId
            $this->processTaskListPosition,
            $this->version + 1
        );
    }

    /**
     * Transforms current message to a process data command
     *
     * @param \Ginger\Processor\Task\TaskListPosition $newTaskListPosition
     * @param array $metadata
     * @param null|string $target
     * @return WorkflowMessage
     */
    public function prepareDataProcessing(TaskListPosition $newTaskListPosition, array $metadata = [], $target = null)
    {
        $type = MessageNameUtils::getTypePartOfMessageName($this->messageName);

        $metadata = ArrayUtils::merge($this->metadata, $metadata);

        return new self(
            $this->payload,
            MessageNameUtils::getProcessDataCommandName($type),
            $metadata,
            $target,
            $newTaskListPosition,
            $this->version + 1
        );
    }

    /**
     * Transforms current message to a data processed event
     *
     * @param array $metadata
     * @return WorkflowMessage
     */
    public function answerWithDataProcessingCompleted(array $metadata = [])
    {
        $type = MessageNameUtils::getTypePartOfMessageName($this->messageName);

        $metadata = ArrayUtils::merge($this->metadata, $metadata);

        return new self(
            $this->payload,
            MessageNameUtils::getDataProcessedEventName($type),
            $metadata,
            null, //Target is not set, so it defaults to node name of TaskListPosition->TaskListId
            $this->processTaskListPosition,
            $this->version + 1
        );
    }

    /**
     * @param TaskListPosition $taskListPosition
     * @throws \RuntimeException If message is already connected to process
     */
    public function connectToProcessTask(TaskListPosition $taskListPosition)
    {
        if (! is_null($this->processTaskListPosition)) {
            throw new \RuntimeException(
                sprintf(
                    "WorkflowMessage %s (%s) is already connected to a process task",
                    $this->getMessageName(),
                    $this->uuid()->toString()
                )
            );
        }

        $this->processTaskListPosition = $taskListPosition;
    }

    /**
     * @param TaskListPosition $taskListPosition
     * @return \Ginger\Message\LogMessage
     */
    public function reconnectToProcessTask(TaskListPosition $taskListPosition)
    {
        return new self(
            $this->payload,
            $this->getMessageName(),
            $this->metadata,
            $this->target,
            $taskListPosition,
            $this->version,
            $this->createdOn()
        );
    }

    /**
     * Alias for messageName() method to fulfill the MessageNameProvider interface
     *
     * @return string Name of the message
     */
    public function getMessageName()
    {
        return $this->messageName();
    }

    /**
     * @return string Name of the message
     */
    public function messageName()
    {
        return $this->messageName;
    }

    /**
     * @return string Type fof the message
     */
    public function messageType()
    {
        return MessageNameUtils::getMessageSuffix($this->getMessageName());
    }

    /**
     * @return null|string
     */
    public function target()
    {
        if (is_null($this->target) && ! is_null($this->processTaskListPosition)) {
            return $this->processTaskListPosition()->taskListId()->nodeName()->toString();
        }

        return $this->target;
    }

    /**
     * @return Payload
     */
    public function payload()
    {
        return $this->payload;
    }

    /**
     * @return TaskListPosition|null
     */
    public function processTaskListPosition()
    {
        return $this->processTaskListPosition;
    }

    /**
     * @return Uuid
     */
    public function uuid()
    {
        return $this->uuid;
    }

    /**
     * @return int
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @return \DateTime
     */
    public function createdOn()
    {
        return $this->createdOn;
    }

    /**
     * @param string $newGingerType
     */
    public function changeGingerType($newGingerType)
    {
        Assertion::string($newGingerType);

        $oldGingerType = MessageNameUtils::getTypePartOfMessageName($this->messageName);

        $this->messageName = str_replace($oldGingerType, MessageNameUtils::normalize($newGingerType), $this->messageName);

        $this->payload()->changeTypeClass($newGingerType);
    }

    /**
     * @return array|null
     */
    public function metadata()
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata
     */
    public function addMetadata(array $metadata)
    {
        $this->assertMetadata($metadata);

        $this->metadata = ArrayUtils::merge($this->metadata, $metadata);
    }

    /**
     * @throws \RuntimeException
     * @return MessageInterface
     */
    public function toServiceBusMessage()
    {
        $messageType = null;

        if (MessageNameUtils::isGingerCommand($this->getMessageName()))
            $messageType = MessageHeader::TYPE_COMMAND;
        else if (MessageNameUtils::isGingerEvent($this->getMessageName()))
            $messageType = MessageHeader::TYPE_EVENT;
        else
            throw new \RuntimeException(sprintf(
                'Ginger message %s can not be converted to service bus message. Type of the message could not be detected',
                $this->getMessageName()
            ));

        $messageHeader = new MessageHeader(
            $this->uuid(),
            $this->createdOn(),
            $this->version(),
            $messageType
        );

        $msgPayload = array('json' => json_encode($this->payload()), 'metadata' => $this->metadata);

        if ($this->processTaskListPosition()) {
            $msgPayload['processTaskListPosition'] = $this->processTaskListPosition()->toString();
        }

        return new StandardMessage(
            $this->getMessageName(),
            $messageHeader,
            $msgPayload
        );
    }

    /**
     * @param array $metadata
     * @throws \InvalidArgumentException
     */
    protected function assertMetadata(array $metadata)
    {
        foreach ($metadata as $entry) {
            if (is_array($entry)) $this->assertMetadata($entry);
            elseif (! is_scalar($entry)) throw new \InvalidArgumentException('Metadata must only contain arrays or scalar values');
        }
    }
}
 