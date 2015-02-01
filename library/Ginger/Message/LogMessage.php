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

use Assert\Assertion;
use Ginger\Message\ProophPlugin\ServiceBusTranslatableMessage;
use Ginger\Processor\Task\Task;
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
final class LogMessage implements MessageNameProvider, GingerMessage
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
        return new self($taskListPosition, 'Data processing was started', 202, array('started_on' => date(\DateTime::ISO8601)));
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
     * @param array $msgParams
     * @return LogMessage
     */
    public static function logErrorMsg($msg, TaskListPosition $taskListPosition, array $msgParams = [])
    {
        return new self($taskListPosition, (string)$msg, 500, $msgParams);
    }

    /**
     * @param Task $task
     * @param TaskListPosition $taskListPosition
     * @return LogMessage
     */
    public static function logNoMessageReceivedFor(Task $task, TaskListPosition $taskListPosition)
    {
        return new self(
            $taskListPosition,
            sprintf(
                "Process %s received no message for task %s at position %d",
                $taskListPosition->taskListId()->processId()->toString(),
                get_class($task),
                $taskListPosition->position()
            ),
            412,
            array(
                'process_id' => $taskListPosition->taskListId()->processId()->toString(),
                'task_list_position' => $taskListPosition->position(),
                'task_class' => get_class($task),
                'task_as_json' => json_encode($task->getArrayCopy())
            ));
    }

    /**
     * @param Task $task
     * @param TaskListPosition $taskListPosition
     * @param WorkflowMessage $workflowMessage
     * @return LogMessage
     */
    public static function logWrongMessageReceivedFor(Task $task, TaskListPosition $taskListPosition, WorkflowMessage $workflowMessage)
    {
        return new self(
            $taskListPosition,
            sprintf(
                "Process %s received wrong message with name %s for task %s at position %d",
                $taskListPosition->taskListId()->processId()->toString(),
                $workflowMessage->getMessageName(),
                get_class($task),
                $taskListPosition->position()
            ),
            415,
            array(
                'process_id' => $taskListPosition->taskListId()->processId()->toString(),
                'task_list_position' => $taskListPosition->position(),
                'task_class' => get_class($task),
                'task_as_json' => json_encode($task->getArrayCopy()),
                'message_name' => $workflowMessage->getMessageName(),
            ));
    }

    /**
     * @param WorkflowMessage $workflowMessage
     * @param string $workflowMessageHandlerName
     * @return LogMessage
     */
    public static function logUnsupportedMessageReceived(WorkflowMessage $workflowMessage, $workflowMessageHandlerName)
    {
        return new self(
            $workflowMessage->processTaskListPosition(),
            sprintf(
                "Workflow message handler %s received wrong message with name %s for task %s",
                (string)$workflowMessageHandlerName,
                $workflowMessage->getMessageName(),
                $workflowMessage->processTaskListPosition()->toString()
            ),
            416,
            array(
                'workflow_message_handler' => (string)$workflowMessageHandlerName,
                'message_name' => $workflowMessage->getMessageName(),
            )
        );
    }

    /**
     * @param string $msg
     * @param TaskListPosition $taskListPosition
     * @param array $msgParams
     * @return LogMessage
     */
    public static function logDebugMsg($msg, TaskListPosition $taskListPosition, array $msgParams = [])
    {
        return new self($taskListPosition, $msg, 0, $msgParams);
    }

    /**
     * @param string $warning
     * @param TaskListPosition $taskListPosition
     * @param array $msgParams
     * @return LogMessage
     */
    public static function logWarningMsg($warning, TaskListPosition $taskListPosition, array $msgParams = [])
    {
        return new self($taskListPosition, $warning, 100, $msgParams);
    }

    /**
     * @param MessageInterface $aMessage
     * @return LogMessage
     * @throws \RuntimeException
     */
    public static function fromServiceBusMessage(MessageInterface $aMessage)
    {
        $payload = $aMessage->payload();

        Assertion::keyExists($payload, 'processTaskListPosition');
        Assertion::keyExists($payload, 'technicalMsg');
        Assertion::keyExists($payload, 'msgParams');
        Assertion::keyExists($payload, 'msgCode');

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
        Assertion::string($technicalMsg);
        Assertion::integer($msgCode);

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
        return MessageNameUtils::LOG_MESSAGE_NAME;
    }

    /**
     * Target of the log message is always the workflow processor of the referenced task that should receive the message
     *
     * @return null|string
     */
    public function target()
    {
        return $this->processTaskListPosition()->taskListId()->nodeName()->toString();
    }

    /**
     * @return MessageInterface
     */
    public function toServiceBusMessage()
    {
        $header = new MessageHeader(
            $this->uuid(),
            $this->createdOn(),
            1,
            MessageHeader::TYPE_EVENT
        );

        return new StandardMessage(
            $this->getMessageName(),
            $header,
            [
                'processTaskListPosition' => $this->processTaskListPosition()->toString(),
                'technicalMsg' => $this->technicalMsg(),
                'msgParams' => $this->msgParams(),
                 'msgCode' => $this->msgCode()
            ]
        );
    }

    /**
     * @param TaskListPosition $taskListPosition
     * @return \Ginger\Message\LogMessage
     */
    public function reconnectToProcessTask(TaskListPosition $taskListPosition)
    {
        return new self(
            $taskListPosition,
            $this->technicalMsg(),
            $this->msgCode(),
            $this->msgParams(),
            null,
            $this->createdOn()
        );
    }

    /**
     * @return int
     */
    public function msgCode()
    {
        return $this->msgCode;
    }

    /**
     * @return array
     */
    public function msgParams()
    {
        return $this->msgParams;
    }

    /**
     * @return TaskListPosition
     */
    public function processTaskListPosition()
    {
        return $this->processTaskListPosition;
    }

    /**
     * @return string
     */
    public function technicalMsg()
    {
        return $this->technicalMsg;
    }

    /**
     * @return Uuid
     */
    public function uuid()
    {
        return $this->uuid;
    }

    /**
     * @return \DateTime
     */
    public function createdOn()
    {
        return $this->createdOn;
    }

    /**
     * @return string
     */
    public function logLevel()
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
        return $this->logLevel() === self::LOG_LEVEL_DEBUG;
    }

    /**
     * @return bool
     */
    public function isWarning()
    {
        return $this->logLevel() === self::LOG_LEVEL_WARNING;
    }

    /**
     * @return bool
     */
    public function isInfo()
    {
        return $this->logLevel() === self::LOG_LEVEL_INFO;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->logLevel() === self::LOG_LEVEL_ERROR;
    }
}
 