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

use Ginger\Processor\NodeName;
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
     * @var NodeName
     */
    private $nodeName;

    /**
     * @var Uuid
     */
    private $uuid;

    /**
     * @param NodeName $nodeName
     * @param ProcessId $processId
     * @return TaskListId
     */
    public static function linkWith(NodeName $nodeName, ProcessId $processId)
    {
        return new self($nodeName, $processId, Uuid::uuid4());
    }

    /**
     * @param string $taskListIdStr
     * @throws \InvalidArgumentException
     * @return TaskListId
     */
    public static function fromString($taskListIdStr)
    {
        if (! is_string($taskListIdStr)) {
            throw new \InvalidArgumentException("TaskListIdStr must be string");
        }

        $parts = explode(':TASK_LIST_ID:', $taskListIdStr);

        if (count($parts) != 2) {
            throw new \InvalidArgumentException(sprintf(
                "Invalid taskLIstIdStr %s provided. Needs to have the format: node-name:PROCESS_ID:process-uuid:TASK_LIST_ID:task-list-uuid",
                $taskListIdStr
            ));
        }

        $envParts = explode(':PROCESS_ID:', $parts[0]);

        if (count($envParts) != 2) {
            throw new \InvalidArgumentException(sprintf(
                "Invalid taskLIstIdStr %s provided. Needs to have the format: node-name:PROCESS_ID:process-uuid:TASK_LIST_ID:task-list-uuid",
                $taskListIdStr
            ));
        }

        $nodeName  = NodeName::fromString($envParts[0]);
        $processId = ProcessId::fromString($envParts[1]);

        return new self($nodeName, $processId, Uuid::fromString($parts[1]));
    }

    /**
     * @param \Ginger\Processor\NodeName $nodeName
     * @param \Ginger\Processor\ProcessId $processId
     * @param Uuid $uuid
     */
    private function __construct(NodeName $nodeName, ProcessId $processId, Uuid $uuid)
    {
        $this->nodeName  = $nodeName;
        $this->processId = $processId;
        $this->uuid = $uuid;
    }

    /**
     * @return NodeName
     */
    public function nodeName()
    {
        return $this->nodeName;
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
        return $this->nodeName()->toString()
        . ':PROCESS_ID:'
        . $this->processId()->toString()
        . ':TASK_LIST_ID:'
        . $this->uuid()->toString();
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
        return $this->nodeName()->equals($taskListId->nodeName())
                && $this->processId()->equals($taskListId->processId())
                && $this->uuid->equals($taskListId->uuid);
    }
}
 