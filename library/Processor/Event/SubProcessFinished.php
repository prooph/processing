<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 04.12.14 - 21:38
 */

namespace Prooph\Processing\Processor\Event;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\RemoteMessage;
use Prooph\Processing\Message\ProcessingMessage;
use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\TaskListPosition;

/**
 * Class SubProcessFinished
 *
 * @package Prooph\Processing\Processor\Event
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SubProcessFinished extends DomainEvent implements ProcessingMessage
{
    const MSG_NAME = "processing-processor-event-sub-process-finished";

    /**
     * @param NodeName $nodeName
     * @param ProcessId $subProcessId
     * @param bool $succeed
     * @param ProcessingMessage $lastReceivedMessage
     * @param TaskListPosition $parentTaskListPosition
     * @throws \InvalidArgumentException
     * @return SubProcessFinished
     */
    public static function record(NodeName $nodeName, ProcessId $subProcessId, $succeed, ProcessingMessage $lastReceivedMessage, TaskListPosition $parentTaskListPosition)
    {
        if (! is_bool($succeed)) {
            throw new \InvalidArgumentException("Succeed must be a boolean");
        }

        $payload = [
            'processor_node_name' => $nodeName->toString(),
            'parent_task_list_position' => $parentTaskListPosition->toString(),
            'sub_process_id' => $subProcessId->toString(),
            'succeed' => $succeed,
            'last_message' => $lastReceivedMessage->toServiceBusMessage()->toArray()
        ];

        return new self(self::MSG_NAME, $payload);
    }

    /**
     * @return NodeName
     */
    public function processorNodeName()
    {
        return NodeName::fromString($this->payload['processor_node_name']);
    }

    /**
     * The origin of a sub process finished event is always the sub processor node name.
     *
     * @return string
     */
    public function origin()
    {
        return $this->payload['processor_node_name'];
    }

    /**
     * Target of the sub process finished event is always the parent processor
     *
     * @return null|string
     */
    public function target()
    {
        return $this->parentTaskListPosition()->taskListId()->nodeName()->toString();
    }

    /**
     * @return TaskListPosition
     */
    public function parentTaskListPosition()
    {
        return TaskListPosition::fromString($this->payload['parent_task_list_position']);
    }

    /**
     * @return ProcessId
     */
    public function subProcessId()
    {
        return ProcessId::fromString($this->payload['sub_process_id']);
    }

    /**
     * @return bool
     */
    public function succeed()
    {
        return (bool)$this->payload['succeed'];
    }

    /**
     * @return WorkflowMessage|LogMessage
     * @throws \RuntimeException
     */
    public function lastMessage()
    {
        $sbMessage = RemoteMessage::fromArray($this->payload['last_message']);

        if (MessageNameUtils::isProcessingLogMessage($sbMessage->name())) {
            return LogMessage::fromServiceBusMessage($sbMessage);
        }

        if (MessageNameUtils::isWorkflowMessage($sbMessage->name())) {
            return WorkflowMessage::fromServiceBusMessage($sbMessage);
        }

        throw new \RuntimeException(
            sprintf(
                "Sub process %s has received last a message with name %s that has no known message format",
                $this->processorNodeName() . '::' . $this->subProcessId(),
                $sbMessage->name()
            )
        );
    }

    /**
     * @param RemoteMessage $message
     * @return SubProcessFinished
     */
    public static function fromServiceBusMessage(RemoteMessage $message)
    {
        return self::fromRemoteMessage($message);
    }

    /**
     * @return RemoteMessage
     */
    public function toServiceBusMessage()
    {
        return $this->toRemoteMessage();
    }
}
 