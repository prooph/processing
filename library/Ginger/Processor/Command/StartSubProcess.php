<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.10.14 - 20:34
 */

namespace Ginger\Processor\Command;

use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Task\TaskListPosition;
use Prooph\ServiceBus\Command;
use Prooph\ServiceBus\Message\StandardMessage;

/**
 * Class StartSubProcess
 *
 * @package Ginger\Processor\Command
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StartSubProcess extends Command
{
    const MSG_NAME = "ginger-processor-command-start-sub-process";

    /**
     * @param TaskListPosition $parentTaskListPosition
     * @param array $processDefinition
     * @param bool $syncLogMessages
     * @param WorkflowMessage $previousMessage
     * @throws \InvalidArgumentException
     * @return StartSubProcess
     */
    public static function at(TaskListPosition $parentTaskListPosition, array $processDefinition, $syncLogMessages, WorkflowMessage $previousMessage = null)
    {
        if (! is_bool($syncLogMessages)) {
            throw new \InvalidArgumentException("Argument syncLogMessages must be of type boolean");
        }

        $previousMessageArrayOrNull = (is_null($previousMessage))? null : $previousMessage->toServiceBusMessage()->toArray();

        $payload = [
            'parent_task_list_position' => $parentTaskListPosition->toString(),
            'sync_log_messages' => $syncLogMessages,
            'sub_process_definition' => $processDefinition,
            'previous_message' => $previousMessageArrayOrNull
        ];

        return new self(self::MSG_NAME, $payload);
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
            $sbMessage = StandardMessage::fromArray($this->payload['previous_message']);

            return WorkflowMessage::fromServiceBusMessage($sbMessage);
        }

        return null;
    }
}
 