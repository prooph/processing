<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 01.10.14 - 22:51
 */

namespace Ginger\Processor\Task;

use Ginger\Message\LogMessage;

/**
 * Class TaskListEntry
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TaskListEntry 
{
    CONST STATUS_NOT_STARTED = "not_started";
    const STATUS_IN_PROGRESS = "in_progress";
    const STATUS_FAILED = "failed";
    CONST STATUS_DONE = "done";
    const STATUS_DONE_WITH_WARNING = "done_with_warning";

    /**
     * @var int
     */
    private $listPosition;

    /**
     * @var Task
     */
    private $task;

    /**
     * @var string
     */
    private $status;

    private $startedOn;

    private $finishedOn;

    /**
     * @var LogMessage[]
     */
    private $log = array();
    /**
     * @param Task $task
     * @return TaskListEntry
     */
    public static function newEntry(Task $task)
    {
        return new self($task, self::STATUS_NOT_STARTED, null);
    }

    /**
     * @param array $entryData
     * @return TaskListEntry
     */
    public static function reconstituteFromArray(array $entryData)
    {
        \Assert\that($entryData)->keyExists('task');
        \Assert\that($entryData)->keyExists('status');
        \Assert\that($entryData)->keyExists('comments');

        if (! $entryData['task'] instanceof Task) {
            $entryData['task'] = Task::reconstituteFromArray($entryData['task']);
        }

        return new self($entryData['task'], $entryData['status'], $entryData['comments']);
    }


    /**
     * @param Task $task
     * @param $status
     * @param array $comments
     */
    private function __construct(Task $task, $status, array $comments)
    {
        \Assert\that($status)->inArray(array(self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS, self::STATUS_FAILED, self::STATUS_DONE));

        \Assert\that($comments)->all()->string();

        $this->task = $task;
        $this->status = $status;
        $this->comment = $comment;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'task' => $this->task->getArrayCopy(),
            'status' => $this->status,
            'comment' => $this->comment
        ];
    }
}
 