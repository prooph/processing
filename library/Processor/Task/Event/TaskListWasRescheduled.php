<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12/6/14 - 1:26 AM
 */
namespace Prooph\Processing\Processor\Task\Event;

use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\TaskList;
use Prooph\EventSourcing\AggregateChanged;

/**
 * Class TaskListWasRescheduled
 *
 * @package Prooph\Processing\Processor\Task\Event
 * @author Alexander Miertsch <alexander.miertsch.extern@sixt.com>
 */
final class TaskListWasRescheduled extends AggregateChanged
{
    /**
     * @var TaskList
     */
    private $newTaskList;

    /**
     * @param TaskList $newTaskList
     * @param ProcessId $processId
     * @return TaskListWasRescheduled
     */
    public static function with(TaskList $newTaskList, ProcessId $processId)
    {
        $event = self::occur($processId->toString(), ['new_task_list' => $newTaskList->getArrayCopy()]);

        $event->newTaskList = $newTaskList;

        return $event;
    }

    /**
     * @return ProcessId
     */
    public function processId()
    {
        return ProcessId::fromString($this->aggregateId);
    }

    /**
     * @return TaskList
     */
    public function newTaskList()
    {
        if (is_null($this->newTaskList)) {
            $this->newTaskList = TaskList::fromArray($this->payload['new_task_list']);
        }

        return $this->newTaskList;
    }
} 