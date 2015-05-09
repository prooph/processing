<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.10.14 - 20:34
 */

namespace Prooph\Processing\Processor\Command;

use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\RemoteMessage;
use Prooph\Processing\Message\ProcessingMessage;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Task\TaskListPosition;

/**
 * Class StartSubProcess
 *
 * @package Prooph\Processing\Processor\Command
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StartSubProcess extends Command implements ProcessingMessage
{
    const MSG_NAME = "processing-processor-command-start-sub-process";

    /**
     * @param TaskListPosition $parentTaskListPosition
     * @param array $processDefinition
     * @param bool $syncLogMessages
     * @param string $target
     * @param WorkflowMessage $previousMessage
     * @throws \InvalidArgumentException
     * @return StartSubProcess
     */
    public static function at(TaskListPosition $parentTaskListPosition, array $processDefinition, $syncLogMessages, $target, WorkflowMessage $previousMessage = null)
    {
        if (! is_bool($syncLogMessages)) {
            throw new \InvalidArgumentException("Argument syncLogMessages must be of type boolean");
        }

        if (! is_string($target)) throw new \InvalidArgumentException("Target must be string");
        if (empty($target)) throw new \InvalidArgumentException('Target must be a non empty string');

        $previousMessageArrayOrNull = (is_null($previousMessage))? null : $previousMessage->toServiceBusMessage()->toArray();

        $payload = [
            'parent_task_list_position' => $parentTaskListPosition->toString(),
            'sync_log_messages' => $syncLogMessages,
            'sub_process_definition' => $processDefinition,
            'previous_message' => $previousMessageArrayOrNull,
            'origin' => $parentTaskListPosition->taskListId()->nodeName()->toString(),
            'target' => $target,
        ];

        return new self(self::MSG_NAME, $payload);
    }

    /**
     * Origin of a start sub process command is always the parent processor node name.
     * It is set on initialization.
     *
     * @return string
     */
    public function origin()
    {
        return $this->payload['origin'];
    }

    /**
     * Target of the start sub process command is always the processor of the sub process.
     * It is set on initialization by the RunSubProcess task
     *
     * @return null|string
     */
    public function target()
    {
        return $this->payload['target'];
    }

    /**
     * @return TaskListPosition
     */
    public function parentTaskListPosition()
    {
        return TaskListPosition::fromString($this->payload['parent_task_list_position']);
    }

    /**
     * @return bool
     */
    public function syncLogMessages()
    {
        return (bool)$this->payload['sync_log_messages'];
    }

    /**
     * @return array
     */
    public function subProcessDefinition()
    {
        return $this->payload['sub_process_definition'];
    }

    /**
     * @return null|WorkflowMessage
     */
    public function previousWorkflowMessage()
    {
        if ($this->payload['previous_message']) {
            $sbMessage = RemoteMessage::fromArray($this->payload['previous_message']);

            return WorkflowMessage::fromServiceBusMessage($sbMessage);
        }

        return null;
    }

    /**
     * @param RemoteMessage $message
     * @return static
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
 