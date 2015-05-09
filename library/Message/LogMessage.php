<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 02.10.14 - 21:21
 */

namespace Prooph\Processing\Message;

use Assert\Assertion;
use Prooph\Common\Messaging\MessageHeader;
use Prooph\Common\Messaging\RemoteMessage;
use Prooph\Processing\Processor\Task\Task;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Rhumsaa\Uuid\Uuid;

/**
 * Class LogMessage
 *
 * @package Prooph\Processing\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class LogMessage implements ProcessingMessage
{
    const LOG_LEVEL_DEBUG = "debug";
    const LOG_LEVEL_WARNING = "warning";
    const LOG_LEVEL_INFO = "info";
    const LOG_LEVEL_ERROR = "error";

    const ERROR_SYSTEM_ERROR = 500;
    const ERROR_ITEMS_PROCESSING_FAILED = 501;
    const ERROR_NO_MESSAGE_RECEIVED = 412;
    const ERROR_WRONG_MESSAGE_RECEIVED = 415;
    const ERROR_UNSUPPORTED_MESSAGE_RECEIVED = 416;

    const INFO_PROCESSING_STARTED = 202;

    const WARNING_MSG = 100;

    const DEBUG_MSG = 0;

    const MSG_PARAM_TRACE = 'trace';
    const MSG_PARAM_SUCCESSFUL_ITEMS = 'successful_items';
    const MSG_PARAM_FAILED_ITEMS = 'failed_items';
    const MSG_PARAM_FAILED_MESSAGES = 'failed_messages';

    /**
     * @var Uuid
     */
    private $uuid;

    /**
     * @var string
     */
    private $origin;

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
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /**
     * @param WorkflowMessage $originMessage
     * @return LogMessage
     */
    public static function logInfoDataProcessingStarted(WorkflowMessage $originMessage)
    {
        return new self(
            $originMessage->target(),
            $originMessage->processTaskListPosition(),
            'Data processing was started',
            self::INFO_PROCESSING_STARTED,
            array('started_on' => date(\DateTime::ISO8601))
        );
    }

    /**
     * @param \Exception $exception
     * @param WorkflowMessage|TaskListPosition $originMessageOrTaskListPosition
     * @throws \InvalidArgumentException
     * @return LogMessage
     */
    public static function logException(\Exception $exception, $originMessageOrTaskListPosition)
    {
        $errorCode = $exception->getCode()? : self::ERROR_SYSTEM_ERROR;

        if ($errorCode < 400) {
            $errorCode = self::ERROR_SYSTEM_ERROR;
        }

        $origin = null;
        $taskListPosition = null;

        if ($originMessageOrTaskListPosition instanceof WorkflowMessage) {
            $origin = $originMessageOrTaskListPosition->target();
            $taskListPosition = $originMessageOrTaskListPosition->processTaskListPosition();
        } elseif ($originMessageOrTaskListPosition instanceof TaskListPosition) {
            $origin = $originMessageOrTaskListPosition->taskListId()->nodeName()->toString();
            $taskListPosition = $originMessageOrTaskListPosition;
        } else {
            throw new \InvalidArgumentException(
                "Second param of LogMessage::logException should be a workflow message or task list position. Got:  " . gettype($originMessageOrTaskListPosition)
            );
        }

        return new self(
            $origin,
            $taskListPosition,
            $exception->getMessage(),
            $errorCode,
            array(self::MSG_PARAM_TRACE => $exception->getTraceAsString())
        );
    }

    /**
     * @param string $msg
     * @param WorkflowMessage $originMessage
     * @param array $msgParams
     * @return LogMessage
     */
    public static function logErrorMsg($msg, WorkflowMessage $originMessage, array $msgParams = [])
    {
        return new self(
            $originMessage->target(),
            $originMessage->processTaskListPosition(),
            (string)$msg,
            self::ERROR_SYSTEM_ERROR,
            $msgParams
        );
    }

    /**
     * @param int $successfulItems
     * @param int $failedItems
     * @param array $failedMessages
     * @param WorkflowMessage $originMessage
     * @return LogMessage
     */
    public static function logItemsProcessingFailed($successfulItems, $failedItems, array $failedMessages, WorkflowMessage $originMessage)
    {
        Assertion::integer($successfulItems);
        Assertion::integer($failedItems);

        foreach ($failedMessages as $failedMsg) {
            Assertion::string($failedMsg);
        }

        Assertion::count($failedMessages, $failedItems, "Number of failed messages should be the same as number of failed items");

        return new self(
            $originMessage->target(),
            $originMessage->processTaskListPosition(),
            sprintf('Processing for %d of %d items failed', $failedItems, $successfulItems + $failedItems),
            self::ERROR_ITEMS_PROCESSING_FAILED,
            [
                self::MSG_PARAM_SUCCESSFUL_ITEMS => $successfulItems,
                self::MSG_PARAM_FAILED_ITEMS     => $failedItems,
                self::MSG_PARAM_FAILED_MESSAGES  => $failedMessages
            ]
        );
    }

    /**
     * @param Task $task
     * @param TaskListPosition $taskListPosition
     * @return LogMessage
     */
    public static function logNoMessageReceivedFor(Task $task, TaskListPosition $taskListPosition)
    {
        return new self(
            $taskListPosition->taskListId()->nodeName()->toString(),
            $taskListPosition,
            sprintf(
                "Process %s received no message for task %s at position %d",
                $taskListPosition->taskListId()->processId()->toString(),
                get_class($task),
                $taskListPosition->position()
            ),
            self::ERROR_NO_MESSAGE_RECEIVED,
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
            $taskListPosition->taskListId()->nodeName()->toString(),
            $taskListPosition,
            sprintf(
                "Process %s received wrong message with name %s for task %s at position %d",
                $taskListPosition->taskListId()->processId()->toString(),
                $workflowMessage->messageName(),
                get_class($task),
                $taskListPosition->position()
            ),
            self::ERROR_WRONG_MESSAGE_RECEIVED,
            array(
                'process_id' => $taskListPosition->taskListId()->processId()->toString(),
                'task_list_position' => $taskListPosition->position(),
                'task_class' => get_class($task),
                'task_as_json' => json_encode($task->getArrayCopy()),
                'message_name' => $workflowMessage->messageName(),
            ));
    }

    /**
     * @param WorkflowMessage $workflowMessage
     * @return LogMessage
     */
    public static function logUnsupportedMessageReceived(WorkflowMessage $workflowMessage)
    {
        return new self(
            $workflowMessage->target(),
            $workflowMessage->processTaskListPosition(),
            sprintf(
                "Workflow message handler %s received wrong message with name %s for task %s",
                $workflowMessage->target(),
                $workflowMessage->messageName(),
                $workflowMessage->processTaskListPosition()->toString()
            ),
            self::ERROR_UNSUPPORTED_MESSAGE_RECEIVED,
            array(
                'workflow_message_handler' => $workflowMessage->target(),
                'message_name' => $workflowMessage->messageName(),
            )
        );
    }

    /**
     * @param string $msg
     * @param WorkflowMessage $originMessage
     * @param array $msgParams
     * @return LogMessage
     */
    public static function logDebugMsg($msg, WorkflowMessage $originMessage, array $msgParams = [])
    {
        return new self(
            $originMessage->target(),
            $originMessage->processTaskListPosition(),
            $msg,
            self::DEBUG_MSG, $msgParams
        );
    }

    /**
     * @param string $warning
     * @param WorkflowMessage $originMessage
     * @param array $msgParams
     * @return LogMessage
     */
    public static function logWarningMsg($warning, WorkflowMessage $originMessage, array $msgParams = [])
    {
        return new self(
            $originMessage->target(),
            $originMessage->processTaskListPosition(),
            $warning,
            self::WARNING_MSG,
            $msgParams
        );
    }

    /**
     * @param RemoteMessage $aMessage
     * @return LogMessage
     * @throws \RuntimeException
     */
    public static function fromServiceBusMessage(RemoteMessage $aMessage)
    {
        $payload = $aMessage->payload();

        Assertion::keyExists($payload, 'processTaskListPosition');
        Assertion::keyExists($payload, 'origin');
        Assertion::keyExists($payload, 'technicalMsg');
        Assertion::keyExists($payload, 'msgParams');
        Assertion::keyExists($payload, 'msgCode');

        $taskListPosition = TaskListPosition::fromString($payload['processTaskListPosition']);

        return new self(
            $payload['origin'],
            $taskListPosition,
            $payload['technicalMsg'],
            $payload['msgCode'],
            $payload['msgParams'],
            $aMessage->header()->uuid(),
            $aMessage->header()->createdAt()
        );
    }

    /**
     * @param string $origin
     * @param TaskListPosition $taskListPosition
     * @param string $technicalMsg
     * @param int $msgCode
     * @param array $msgParams
     * @param Uuid $uuid
     * @param \DateTimeImmutable $createdAt
     */
    private function __construct($origin, TaskListPosition $taskListPosition, $technicalMsg, $msgCode = 0, array $msgParams = array(), Uuid $uuid = null, \DateTimeImmutable $createdAt = null)
    {
        Assertion::string($origin);
        Assertion::notEmpty($origin);
        Assertion::string($technicalMsg);
        Assertion::integer($msgCode);

        $this->assertMsgParams($msgParams);

        $this->technicalMsg = $technicalMsg;
        $this->msgCode = $msgCode;
        $this->msgParams = $msgParams;
        $this->processTaskListPosition = $taskListPosition;
        $this->origin = $origin;

        if (is_null($uuid)) {
            $uuid = Uuid::uuid4();
        }

        $this->uuid = $uuid;

        if (is_null($createdAt)) {
            $createdAt = new \DateTimeImmutable();
        }

        $this->createdAt = $createdAt;
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
     * @return string
     */
    public function origin()
    {
        return $this->origin;
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
     * @return RemoteMessage
     */
    public function toServiceBusMessage()
    {
        $header = new MessageHeader(
            $this->uuid(),
            $this->createdAt(),
            1,
            MessageHeader::TYPE_EVENT
        );

        return new RemoteMessage(
            $this->messageName(),
            $header,
            [
                'origin' => $this->origin(),
                'processTaskListPosition' => $this->processTaskListPosition()->toString(),
                'technicalMsg' => $this->technicalMsg(),
                'msgParams' => $this->msgParams(),
                 'msgCode' => $this->msgCode()
            ]
        );
    }

    /**
     * @param TaskListPosition $taskListPosition
     * @return \Prooph\Processing\Message\LogMessage
     */
    public function reconnectToProcessTask(TaskListPosition $taskListPosition)
    {
        return new self(
            $this->origin(),
            $taskListPosition,
            $this->technicalMsg(),
            $this->msgCode(),
            $this->msgParams(),
            null,
            $this->createdAt()
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
     * @return \DateTimeImmutable
     */
    public function createdAt()
    {
        return $this->createdAt;
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

    /**
     * @param array $msgParams
     * @throws \InvalidArgumentException
     */
    protected function assertMsgParams(array $msgParams)
    {
        foreach ($msgParams as $entry) {
            if (is_array($entry)) $this->assertMsgParams($entry);
            elseif (! is_scalar($entry)) throw new \InvalidArgumentException('Msg params should only contain arrays or scalar values');
        }
    }
}
 