<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 08.10.14 - 21:29
 */

namespace Ginger\Processor\Task;

/**
 * Class RunChildProcess
 *
 * @package Ginger\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class RunChildProcess implements Task
{

    /**
     * @param array $taskData
     * @return static
     */
    public static function reconstituteFromArray(array $taskData)
    {
        // TODO: Implement reconstituteFromArray() method.
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        // TODO: Implement getArrayCopy() method.
    }

    /**
     * @param Task $task
     * @return bool
     */
    public function equals(Task $task)
    {
        // TODO: Implement equals() method.
    }
}
 