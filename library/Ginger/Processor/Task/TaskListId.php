<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 02.10.14 - 23:16
 */

namespace Ginger\Processor\Task;

use Ginger\Processor\ProcessId;
use Rhumsaa\Uuid\Uuid;

/**
 * Class TaskListId
 *
 * @package Ginger\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TaskListId 
{
    /**
     * @var ProcessId
     */
    private $processId;

    /**
     * @var Uuid
     */
    private $uuid;

    /**
     * @param ProcessId $processId
     * @return TaskListId
     */
    public static function linkWith(ProcessId $processId)
    {
        return new self($processId, Uuid::uuid4());
    }

    /**
     * @param string $taskListIdStr
     * @throws \InvalidArgumentException
     * @return TaskListId
     */
    public static function fromString($taskListIdStr)
    {
        \Assert\that($taskListIdStr)->string();

        $parts = explode(':TASK_LIST_ID:', $taskListIdStr);

        if (count($parts) != 2) {
            throw new \InvalidArgumentException(sprintf(
                "Invalid taskLIstIdStr %s provided. Needs to have the format: process-uuid:TASK_LIST_ID:task-list-uuid",
                $taskListIdStr
            ));
        }

        $processId = ProcessId::fromString($parts[0]);

        return new self($processId, Uuid::fromString($parts[1]));
    }

    /**
     * @param \Ginger\Processor\ProcessId $processId
     * @param Uuid $uuid
     */
    private function __construct(ProcessId $processId, Uuid $uuid)
    {
        $this->processId = $processId;
        $this->uuid = $uuid;
    }

    /**
     * @return ProcessId
     */
    public function processId()
    {
        return $this->processId;
    }

    /**
     * @return Uuid
     */
    public function uuid()
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->processId()->toString() . ':TASK_LIST_ID:' . $this->uuid()->toString();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @param TaskListId $taskListId
     * @return bool
     */
    public function equals(TaskListId $taskListId)
    {
        return $this->processId()->equals($taskListId->processId()) && $this->uuid->equals($taskListId->uuid);
    }
}
 