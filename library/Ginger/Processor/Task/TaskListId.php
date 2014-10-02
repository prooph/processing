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
     * @var Uuid
     */
    private $uuid;

    /**
     * @return TaskListId
     */
    public static function generate()
    {
        return new self(Uuid::uuid4());
    }

    /**
     * @param $uuid
     * @return TaskListId
     */
    public static function fromString($uuid)
    {
        return new self(Uuid::fromString($uuid));
    }

    /**
     * @param Uuid $uuid
     */
    private function __construct(Uuid $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->uuid->toString();
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
        return $this->uuid->equals($taskListId->uuid);
    }
}
 