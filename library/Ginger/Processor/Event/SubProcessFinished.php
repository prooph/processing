<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 04.12.14 - 21:38
 */

namespace Ginger\Processor\Event;

use Ginger\Message\LogMessage;
use Ginger\Message\MessageNameUtils;
use Ginger\Message\ProophPlugin\ServiceBusTranslatableMessage;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\TaskListPosition;
use Prooph\ServiceBus\Event;
use Prooph\ServiceBus\Message\StandardMessage;

/**
 * Class SubProcessFinished
 *
 * @package Ginger\Processor\Event
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SubProcessFinished extends Event
{
    const MSG_NAME = "ginger-processor-event-sub-process-finished";

    /**
     * @param NodeName $nodeName
     * @param ProcessId $subProcessId
     * @param bool $succeed
     * @param ServiceBusTranslatableMessage $lastReceivedMessage
     * @param TaskListPosition $parentTaskListPosition
     * @throws \InvalidArgumentException
     * @return SubProcessFinished
     */
    public static function record(NodeName $nodeName, ProcessId $subProcessId, $succeed, ServiceBusTranslatableMessage $lastReceivedMessage, TaskListPosition $parentTaskListPosition)
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
        $sbMessage = StandardMessage::fromArray($this->payload['last_message']);

        if (MessageNameUtils::isGingerLogMessage($sbMessage->name())) {
            return LogMessage::fromServiceBusMessage($sbMessage);
        }

        if (MessageNameUtils::isGingerMessage($sbMessage->name())) {
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
}
 