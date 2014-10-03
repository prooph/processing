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
use Prooph\ServiceBus\Message\StandardMessage;

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
     * @param array $taskListEntryData
     * @return TaskListEntry
     */
    public static function fromArray(array $taskListEntryData)
    {
        \Assert\that($taskListEntryData)
            ->keyExists('taskListPosition')
            ->keyExists('taskData')
            ->keyExists('taskClass')
            ->keyExists('status')
            ->keyExists('startedOn')
            ->keyExists('finishedOn')
            ->keyExists('log');

        \Assert\that($taskListEntryData['status'])->inArray([
            self::STATUS_NOT_STARTED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_DONE,
            self::STATUS_FAILED
        ]);


        $taskListPosition = TaskListPosition::fromString($taskListEntryData['taskListPosition']);

        $taskClass = $taskListEntryData['taskClass'];

        $task = $taskClass::reconstituteFromArray($taskListEntryData['taskData']);

        $startedOn = (is_null($taskListEntryData['startedOn']))? null : new \DateTime($taskListEntryData['startedOn']);
        $finishedOn = (is_null($taskListEntryData['finishedOn']))? null : new \DateTime($taskListEntryData['finishedOn']);

        $instance = new self($taskListPosition, $task);

        $instance->status = $taskListEntryData['status'];

        $instance->startedOn = $startedOn;

        $instance->finishedOn = $finishedOn;

        $instance->setLogFromArray($taskListEntryData['log']);

        return $instance;
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
     * @return Task
     */
    public function task()
    {
        return $this->task;
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
    public function markAsFailed(\DateTime $finishedOn = null)
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

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'taskListPosition' => $this->taskListPosition()->toString(),
            'taskData' => $this->task()->getArrayCopy(),
            'taskClass' => get_class($this->task()),
            'status' => $this->status,
            'startedOn' => (is_null($this->startedOn))? null : $this->startedOn->format(\DateTime::ISO8601),
            'finishedOn' => (is_null($this->finishedOn))? null : $this->finishedOn->format(\DateTime::ISO8601),
            'log' => $this->logToArray()
        ];
    }

    /**
     * @return array
     */
    protected function logToArray()
    {
        $log = array();

        foreach ($this->log as $message) {

            $sbMessage = $message->toServiceBusMessage();

            $log[] = $sbMessage->toArray();
        }

        return $log;
    }

    protected function setLogFromArray(array $log)
    {
        foreach ($log as $sbMessageArr) {
            $sbMessage = StandardMessage::fromArray($sbMessageArr);

            $this->log[] = LogMessage::fromServiceBusMessage($sbMessage);
        }
    }
}
 