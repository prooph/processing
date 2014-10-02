<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 02.10.14 - 23:19
 */

namespace Ginger\Processor\Task;
use Codeliner\Comparison\EqualsBuilder;

/**
 * Class TaskListPosition
 *
 * @package Ginger\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TaskListPosition 
{
    /**
     * @var TaskListId
     */
    private $taskListId;

    /**
     * @var int
     */
    private $position;

    /**
     * @param TaskListId $taskListId
     * @param int $position
     * @return TaskListPosition
     */
    public static function at(TaskListId $taskListId, $position)
    {
        return new self($taskListId, $position);
    }

    public static function fromString($taskListPositionStr)
    {
        \Assert\that($taskListPositionStr)->string();

        $parts = explode('::', $taskListPositionStr);

        if (count($parts) != 2) {
            throw new \InvalidArgumentException(sprintf(
                "Invalid taskListPositionStr %s provided. Needs to have the format: task-list-uuid::position",
                $taskListPositionStr
            ));
        }

        $taskListId = TaskListId::fromString($parts[0]);

        return new self($taskListId, $parts[1]);
    }

    private function __construct(TaskListId $taskListId, $position)
    {
        \Assert\that($position)->integer();

        $this->taskListId = $taskListId;
        $this->position = $position;
    }

    /**
     * @return int
     */
    public function position()
    {
        return $this->position;
    }

    /**
     * @return TaskListId
     */
    public function taskListId()
    {
        return $this->taskListId;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->taskListId()->toString() . '::' . $this->position();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @param TaskListPosition $position
     * @return bool
     */
    public function equals(TaskListPosition $position)
    {
        return $this->taskListId()->equals($position->taskListId()) && $this->position() === $position->position();
    }
}
 