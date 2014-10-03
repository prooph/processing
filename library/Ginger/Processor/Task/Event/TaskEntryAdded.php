<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 01:19
 */

namespace Ginger\Processor\Task\Event;

use Ginger\Processor\Task\Task;
use Ginger\Processor\Task\TaskListPosition;

class TaskEntryAdded extends TaskEntryChangedEvent
{
    private $task;



    /**
     * @param TaskListPosition $taskListPosition
     * @param Task $task
     * @return TaskEntryAdded
     */
    public static function at(TaskListPosition $taskListPosition, Task $task)
    {
        $instance = self::occur($taskListPosition, [
            'taskData' => $task->getArrayCopy(),
            'taskClass' => get_class($task)
        ]);

        $instance->task = $task;
        $instance->taskListPosition = $taskListPosition;

        return $instance;
    }

    /**
     * @return TaskListPosition
     */
    public function taskListPosition()
    {
        if (is_null($this->taskListPosition)) {
            $this->taskListPosition = TaskListPosition::fromString($this->payload['taskListPosition']);
        }
        return $this->taskListPosition;
    }

    /**
     * @return Task
     */
    public function task()
    {
        if (is_null($this->task)) {
            $taskClass = $this->payload['taskClass'];
            $this->task = $taskClass::reconstituteFromArray($this->payload['taskData']);
        }

        return $this->task;
    }
}
 