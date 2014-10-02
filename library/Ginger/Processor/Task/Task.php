<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.09.14 - 22:32
 */

namespace Ginger\Processor\Task;

/**
 * Interface Task
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface Task
{
    //a task can be: send command on commandBus xyz
    //or publish event on eventBus xyz
    //or trigger SupProcess

    /**
     * @param array $taskData
     * @return static
     */
    public static function reconstituteFromArray(array $taskData);

    /**
     * @return array
     */
    public function getArrayCopy();

    /**
     * @param Task $task
     * @return bool
     */
    public function equals(Task $task);
}
 