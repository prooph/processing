<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.09.14 - 22:23
 */

namespace Ginger\Processor\Task;

/**
 * Class TaskList
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TaskList
{
    /**
     * @var TaskListId
     */
    private $taskListId;

    /**
     * @var TaskListEntry[]
     */
    private $taskListEntries;

    /**
     * @param TaskListId $taskListId
     * @param array $tasks
     * @return TaskList
     */
    public static function scheduleTasks(TaskListId $taskListId, array $tasks)
    {
        \Assert\that($tasks)->all()->isInstanceOf('Ginger\Processor\Task\Task');

        $position = 1;

        $tasks = array_map(function(Task $task) use ($taskListId, &$position) {
            $taskListPosition = TaskListPosition::at($taskListId, $position++);
            return TaskListEntry::newEntryAt($taskListPosition, $task);
        }, $tasks);

        return new self($taskListId, $tasks);
    }

    /**
     * @param TaskListId $taskListId
     * @param array $taskListEntries
     * @return TaskList
     */
    public static function fromTaskListEntries(TaskListId $taskListId, array $taskListEntries)
    {
        return new self($taskListId, $taskListEntries);
    }

    /**
     * @param TaskListId $taskListId
     * @param TaskListEntry[] $taskListEntries
     */
    private function __construct(TaskListId $taskListId, array $taskListEntries)
    {
        \Assert\that($taskListEntries)->all()->isInstanceOf('Ginger\Processor\Task\TaskListEntry');

        $this->taskListEntries = $taskListEntries;
        $this->taskListId = $taskListId;
    }

    /**
     * @return TaskListId
     */
    public function taskListId()
    {
        return $this->taskListId;
    }

    /**
     * @param TaskListPosition $taskListPosition
     * @return TaskListEntry|null
     */
    public function getTaskListEntryAtPosition(TaskListPosition $taskListPosition)
    {
        foreach($this->taskListEntries as $taskListEntry) {
            if ($taskListEntry->taskListPosition()->equals($taskListPosition)) return $taskListEntry;
        }

        return null;
    }

    /**
     * @return TaskListEntry|null
     */
    public function getNextNotStartedTaskListEntry()
    {
        foreach($this->taskListEntries as $taskListEntry) {
            if (! $taskListEntry->isStarted()) {
                return $taskListEntry;
            }
        }

        return null;
    }

    /**
     * @return TaskListEntry[]
     */
    public function getAllNotStartedTaskListEntries()
    {
        $openTaskListEntries = array();

        foreach($this->taskListEntries as $taskListEntry) {
            if (! $taskListEntry->isStarted()) {
                $openTaskListEntries[] = $taskListEntry;
            }
        }

        return $openTaskListEntries;
    }

    /**
     * @return TaskListEntry[]
     */
    public function getAllTaskListEntries()
    {
        return $this->taskListEntries;
    }

    /**
     * @return array
     */
    public function getArrayCopyOfEntries()
    {
        return array_map(function(TaskListEntry $taskListEntry) {return $taskListEntry->getArrayCopy();}, $this->taskListEntries);
    }

    /**
     * @return bool
     */
    public function isCompleted()
    {
        foreach($this->taskListEntries as $taskListEntry) {
            if (! $taskListEntry->isFinished()) {
                return false;
            }
        }

        return true;
    }
}
 