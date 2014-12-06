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
use Assert\Assertion;

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
        foreach ($tasks as $task) {
            Assertion::isInstanceOf($task, 'Ginger\Processor\Task\Task');
        }

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
     * @param array $taskListArr
     * @return TaskList
     */
    public static function fromArray(array $taskListArr)
    {
        Assertion::keyExists($taskListArr, 'taskListId');
        Assertion::keyExists($taskListArr, 'entries');
        Assertion::isArray($taskListArr['entries']);

        $taskListId = TaskListId::fromString($taskListArr['taskListId']);

        $entries = [];

        foreach ($taskListArr['entries'] as $entryArr) {
            $entries[] = TaskListEntry::fromArray($entryArr);
        }

        return self::fromTaskListEntries($taskListId, $entries);
    }

    /**
     * @param TaskListId $taskListId
     * @param TaskListEntry[] $taskListEntries
     */
    private function __construct(TaskListId $taskListId, array $taskListEntries)
    {
        foreach ($taskListEntries as $taskListEntry) {
            Assertion::isInstanceOf($taskListEntry, 'Ginger\Processor\Task\TaskListEntry');
        }

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
    public function getArrayCopy()
    {
        return [
            'taskListId' => $this->taskListId()->toString(),
            'entries' => $this->getArrayCopyOfEntries()
        ];
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
    public function isStarted()
    {
        foreach($this->taskListEntries as $taskListEntry) {
            if ($taskListEntry->isStarted()) {
                return true;
            }
        }

        return false;
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

    /**
     * @return bool
     */
    public function isSuccessfulDone()
    {
        if (!$this->isCompleted()) {
            return false;
        }

        foreach($this->taskListEntries as $taskListEntry) {
            if ($taskListEntry->isFailed()) {
                return false;
            }
        }

        return true;
    }
}
 