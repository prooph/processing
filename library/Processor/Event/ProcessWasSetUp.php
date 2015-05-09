<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 23:52
 */

namespace Prooph\Processing\Processor\Event;

use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\TaskList;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\EventSourcing\AggregateChanged;

/**
 * Class ProcessWasSetUp Event
 *
 * @package Prooph\Processing\Processor\Event
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessWasSetUp extends AggregateChanged
{
    /**
     * @var ProcessId
     */
    private $processId;

    /**
     * @var TaskListPosition
     */
    private $parentTaskListPosition;

    /**
     * @param ProcessId $processId
     * @param TaskList $taskList
     * @param array $config
     * @return ProcessWasSetUp
     */
    public static function with(ProcessId $processId, TaskList $taskList, array $config)
    {
        $instance = self::occur(
            $processId->toString(),
            [
                'config' => $config,
                'parent_task_list_Position' => null,
                'task_list' => $taskList->getArrayCopy(),
                'sync_log_messages' => false
            ]
        );

        $instance->processId = $processId;

        return $instance;
    }

    /**
     * @param ProcessId $processId
     * @param TaskListPosition $parentTaskListPosition
     * @param TaskList $taskList
     * @param array $config
     * @param bool $syncLogMessages
     * @throws \InvalidArgumentException
     * @return static
     */
    public static function asSubProcess(ProcessId $processId, TaskListPosition $parentTaskListPosition, TaskList $taskList, array $config, $syncLogMessages)
    {
        if (! is_bool($syncLogMessages)) {
            throw new \InvalidArgumentException("Argument syncLogMessages must be of type boolean");
        }

        $instance = self::occur(
            $processId->toString(),
            [
                'config' => $config,
                'parent_task_list_Position' => $parentTaskListPosition->toString(),
                'task_list' => $taskList->getArrayCopy(),
                'sync_log_messages' => $syncLogMessages
            ]
        );

        $instance->processId = $processId;

        $instance->parentTaskListPosition = $parentTaskListPosition;

        return $instance;
    }

    /**
     * @return ProcessId
     */
    public function processId()
    {
        if (is_null($this->processId)) {
            $this->processId = ProcessId::fromString($this->aggregateId());
        }

        return $this->processId;
    }

    /**
     * @return TaskListPosition|null
     */
    public function parentTaskListPosition()
    {
        if (is_null($this->parentTaskListPosition) && ! is_null($this->payload['parent_task_list_Position'])) {
            $this->parentTaskListPosition = TaskListPosition::fromString($this->payload['parent_task_list_Position']);
        }

        return $this->parentTaskListPosition;
    }

    /**
     * @return array
     */
    public function config()
    {
        return $this->payload['config'];
    }

    /**
     * @return array[taskListId => string, entries => entryArr[]]
     */
    public function taskList()
    {
        return $this->payload['task_list'];
    }

    /**
     * @return bool
     */
    public function syncLogMessages()
    {
        return (bool)$this->payload['sync_log_messages'];
    }
}
 