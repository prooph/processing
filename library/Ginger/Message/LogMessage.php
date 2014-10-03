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
use Ginger\Processor\Task\TaskListPosition;
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
    const LOG_LEVEL_DEBUG = "debug";
    const LOG_LEVEL_WARNING = "warning";
    const LOG_LEVEL_INFO = "info";
    const LOG_LEVEL_ERROR = "error";

    /**
     * @var Uuid
     */
    private $uuid;

    /**
     * @var TaskListPosition
     */
    private $processTaskListPosition;

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
     * @param TaskListPosition $taskListPosition
     * @return LogMessage
     */
    public static function logInfoDataProcessingStarted(TaskListPosition $taskListPosition)
    {
        return new self($taskListPosition, 'Data processing was started', 202, array('startedOn' => date(\DateTime::ISO8601)));
    }

    /**
     * @param \Exception $exception
     * @param TaskListPosition $taskListPosition
     * @return LogMessage
     */
    public static function logException(\Exception $exception, TaskListPosition $taskListPosition)
    {
        $errorCode = $exception->getCode()? : 500;

        if ($errorCode < 400) {
            $errorCode = 500;
        }

        return new self($taskListPosition, $exception->getMessage(), $errorCode, array('trace' => $exception->getTraceAsString()));
    }

    /**
     * @param string $msg
     * @param TaskListPosition $taskListPosition
     * @return LogMessage
     */
    public static function logDebugMsg($msg, TaskListPosition $taskListPosition)
    {
        return new self($taskListPosition, $msg);
    }

    /**
     * @param string $warning
     * @param TaskListPosition $taskListPosition
     * @return LogMessage
     */
    public static function logWarningMsg($warning, TaskListPosition $taskListPosition)
    {
        return new self($taskListPosition, $warning, 100);
    }

    /**
     * @param MessageInterface $aMessage
     * @return WorkflowMessage
     * @throws \RuntimeException
     */
    public static function fromServiceBusMessage(MessageInterface $aMessage)
    {
        $payload = $aMessage->payload();

        \Assert\that($payload)->keyExists('processTaskListPosition')
            ->keyExists('technicalMsg')
            ->keyExists('msgParams')
            ->keyExists('msgCode');

        $taskListPosition = TaskListPosition::fromString($payload['processTaskListPosition']);

        return new self(
            $taskListPosition,
            $payload['technicalMsg'],
            $payload['msgCode'],
            $payload['msgParams'],
            $aMessage->header()->uuid(),
            $aMessage->header()->createdOn()
        );
    }

    /**
     * @param TaskListPosition $taskListPosition
     * @param string $technicalMsg
     * @param int $msgCode
     * @param array $msgParams
     * @param Uuid $uuid
     * @param \DateTime $createdOn
     * @throws \InvalidArgumentException
     */
    private function __construct(TaskListPosition $taskListPosition, $technicalMsg, $msgCode = 0, array $msgParams = array(), Uuid $uuid = null, \DateTime $createdOn = null)
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
        $this->processTaskListPosition = $taskListPosition;

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
        $header = new MessageHeader(
            $this->getUuid(),
            $this->getCreatedOn(),
            1,
            MessageHeader::TYPE_EVENT
        );

        return new StandardMessage(
            $this->getMessageName(),
            $header,
            [
                'processTaskListPosition' => $this->getProcessTaskListPosition()->toString(),
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
     * @return TaskListPosition
     */
    public function getProcessTaskListPosition()
    {
        return $this->processTaskListPosition;
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

    /**
     * @return string
     */
    public function getLogLevel()
    {
        switch (true) {
            case $this->msgCode < 100:
                return self::LOG_LEVEL_DEBUG;
            case $this->msgCode < 200:
                return self::LOG_LEVEL_WARNING;
            case $this->msgCode < 400:
                return self::LOG_LEVEL_INFO;
            default:
                return self::LOG_LEVEL_ERROR;
        }
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->getLogLevel() === self::LOG_LEVEL_DEBUG;
    }

    /**
     * @return bool
     */
    public function isWarning()
    {
        return $this->getLogLevel() === self::LOG_LEVEL_WARNING;
    }

    /**
     * @return bool
     */
    public function isInfo()
    {
        return $this->getLogLevel() === self::LOG_LEVEL_INFO;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->getLogLevel() === self::LOG_LEVEL_ERROR;
    }
}
 