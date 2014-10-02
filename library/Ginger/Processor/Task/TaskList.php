<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.09.14 - 22:23
 */

namespace Ginger\Processor\Task;

use Ginger\Processor\ProcessId;
use Rhumsaa\Uuid\Uuid;

/**
 * Class TaskList
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TaskList
{
    /**
     * @var Uuid
     */
    private $uuid;

    /**
     * @var ProcessId
     */
    private $processId;

    /**
     * @var array
     */
    private $tasks;

    /**
     * @param Task[] $tasks
     * @param \Ginger\Processor\ProcessId $processId
     * @return TaskList
     */
    public static function scheduleTasks(array $tasks, ProcessId $processId)
    {
        \Assert\that($tasks)->all()->isInstanceOf('Ginger\Processor\Task');
    }

    /**
     * @param Task[] $tasks
     */
    private function __construct(array $tasks)
    {


        $this->tasks = $tasks;
    }


    public function markTaskAsDone(Task $task, $listPosition = null)
    {
        //WorkflowMessage needs Metadata dictionary
    }

    public function getNextOpenTaskEntry()
    {

    }

    public function getAllOpenTaskEntries()
    {

    }

    public function getAllTaskEntries()
    {

    }
}
 