<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 01.10.14 - 22:51
 */

namespace Prooph\Processing\Processor\Task;

use Assert\Assertion;
use Prooph\Common\Messaging\RemoteMessage;
use Prooph\Processing\Message\LogMessage;
use Prooph\ServiceBus\Message\StandardMessage;

/**
 * Class TaskListEntry
 *
 * @package Prooph\Processing\Processor
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
     * @var \DateTimeImmutable
     */
    private $startedOn;

    /**
     * @var \DateTimeImmutable
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
        Assertion::keyExists($taskListEntryData, 'taskListPosition');
        Assertion::keyExists($taskListEntryData, 'taskData');
        Assertion::keyExists($taskListEntryData, 'taskClass');
        Assertion::keyExists($taskListEntryData, 'status');
        Assertion::keyExists($taskListEntryData, 'startedOn');
        Assertion::keyExists($taskListEntryData, 'finishedOn');
        Assertion::keyExists($taskListEntryData, 'log');
        Assertion::inArray($taskListEntryData['status'], [
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
     * @return \DateTimeImmutable|null
     */
    public function startedOn()
    {
        return $this->startedOn;
    }

    /**
     * @return \DateTimeImmutable|null
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
     * @param \DateTimeImmutable|null $startedOn
     * @throws \RuntimeException
     */
    public function markAsRunning(\DateTimeImmutable $startedOn = null)
    {
        if ($this->isStarted()) {
            //Seems like we've received a duplicate message, we should ignore that
            return;
        }

        if ($this->isFinished()) {
            //Seems like we've received a duplicate message, we should ignore that
            return;
        }

        if (is_null($startedOn)) {
            $startedOn = new \DateTimeImmutable();
        }


        $this->startedOn = $startedOn;
        $this->status = self::STATUS_IN_PROGRESS;
    }

    /**
     * @param \DateTimeImmutable $finishedOn
     * @throws \RuntimeException
     */
    public function markAsSuccessfulDone(\DateTimeImmutable $finishedOn = null)
    {
        if (is_null($finishedOn)) {
            $finishedOn = new \DateTimeImmutable();
        }

        if (! $this->isRunning()) {
            if (! $this->isStarted()) {
                $this->markAsRunning(new \DateTimeImmutable());
            } else {
                if ($this->finishedOn > $finishedOn) {
                    //Seems like we've received a duplicate message, we should ignore that
                    return;
                }
            }
        }

        $this->finishedOn = $finishedOn;

        $this->status = self::STATUS_DONE;
    }

    /**
     * @param \DateTimeImmutable $finishedOn
     * @throws \RuntimeException
     */
    public function markAsFailed(\DateTimeImmutable $finishedOn = null)
    {
        if (is_null($finishedOn)) {
            $finishedOn = new \DateTimeImmutable();
        }

        if (! $this->isRunning()) {
            if (! $this->isStarted()) {
                $this->markAsRunning(new \DateTimeImmutable());
            } else {
                if ($this->finishedOn > $finishedOn) {
                    //Seems like we've received a duplicate message, we should ignore that
                    return;
                }
            }
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
        if (! $this->taskListPosition()->equals($message->processTaskListPosition())) {
            throw new \InvalidArgumentException(sprintf(
                "Cannot log message %s. TaskListPosition of message does not match with position of the TaskListEntry: %s != %s",
                $message->uuid()->toString(),
                $message->processTaskListPosition()->toString(),
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
            $sbMessage = RemoteMessage::fromArray($sbMessageArr);

            $this->log[] = LogMessage::fromServiceBusMessage($sbMessage);
        }
    }
}
 