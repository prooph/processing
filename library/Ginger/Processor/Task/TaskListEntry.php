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

     /**
     * @var TaskListPosition
     */
    private $taskListPosition;

    /**
     * @var Task
     */
    private $task;

    /**
     * @var string
     */
    private $status;

    /**
     * @var \DateTime
     */
    private $startedOn;

    /**
     * @var \DateTime
     */
    private $finishedOn;

    /**
     * @var LogMessage[]
     */
    private $log = array();

    /**
     * @param TaskListPosition $taskListPosition
     * @param Task $task
     * @return TaskListEntry
     */
    public static function newEntryAt(TaskListPosition $taskListPosition, Task $task)
    {
        return new self($taskListPosition, $task);
    }

    /**
     * @param TaskListPosition $position
     * @param Task $task
     */
    private function __construct(TaskListPosition $position, Task $task)
    {
        $this->taskListPosition = $position;
        $this->task = $task;
        $this->status = self::STATUS_NOT_STARTED;
    }

    /**
     * @return TaskListPosition
     */
    public function taskListPosition()
    {
        return $this->taskListPosition;
    }

    /**
     * @return bool
     */
    public function isStarted()
    {
        return $this->status !== self::STATUS_NOT_STARTED;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_DONE]);
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->isStarted() && ! $this->isFinished();
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return $this->isFinished() && $this->status === self::STATUS_FAILED;
    }

    /**
     * @return bool
     */
    public function isDone()
    {
        return $this->isFinished() && ! $this->isFailed();
    }

    /**
     * @return \DateTime|null
     */
    public function startedOn()
    {
        return $this->startedOn;
    }

    /**
     * @return \DateTime|null
     */
    public function finishedOn()
    {
        return $this->finishedOn;
    }

    /**
     * @return LogMessage[]
     */
    public function messageLog()
    {
        return $this->log;
    }

    /**
     * @param \DateTime|null $startedOn
     * @throws \RuntimeException
     */
    public function markAsRunning(\DateTime $startedOn = null)
    {
        if ($this->isStarted()) {
            throw new \RuntimeException(sprintf(
                "TaskListEntry %s cannot be marked as running. It is already started",
                $this->taskListPosition()->toString()
            ));
        }

        if ($this->isFinished()) {
            throw new \RuntimeException(sprintf(
                "TaskListEntry %s cannot be marked as running. It is already finished",
                $this->taskListPosition()->toString()
            ));
        }

        if (is_null($startedOn)) {
            $startedOn = new \DateTime();
        }


        $this->startedOn = $startedOn;
        $this->status = self::STATUS_IN_PROGRESS;
    }

    /**
     * @param \DateTime $finishedOn
     * @throws \RuntimeException
     */
    public function markAsSuccessfulDone(\DateTime $finishedOn = null)
    {
        if (! $this->isRunning()) {
            throw new \RuntimeException(sprintf(
                "TaskListEntry %s cannot be marked as successful done. It is not marked as running",
                $this->taskListPosition()->toString()
            ));
        }

        if (is_null($finishedOn)) {
            $finishedOn = new \DateTime();
        }

        $this->finishedOn = $finishedOn;

        $this->status = self::STATUS_DONE;
    }

    /**
     * @param \DateTime $finishedOn
     * @throws \RuntimeException
     */
    public function maskAsFailed(\DateTime $finishedOn = null)
    {
        if (! $this->isRunning()) {
            throw new \RuntimeException(sprintf(
                "TaskListEntry %s cannot be marked as failed. It is not marked as running",
                $this->taskListPosition()->toString()
            ));
        }

        if (is_null($finishedOn)) {
            $finishedOn = new \DateTime();
        }

        $this->finishedOn = $finishedOn;

        $this->status = self::STATUS_FAILED;
    }

    /**
     * @param LogMessage $message
     * @throws \InvalidArgumentException
     */
    public function logMessage(LogMessage $message)
    {
        if (! $this->taskListPosition()->equals($message->getProcessTaskListPosition())) {
            throw new \InvalidArgumentException(sprintf(
                "Cannot log message %s. TaskListPosition of message does not match with position of the TaskListEntry: %s != %s",
                $message->getUuid()->toString(),
                $message->getProcessTaskListPosition()->toString(),
                $this->taskListPosition()->toString()
            ));
        }

        $this->log[] = $message;
    }
}
 