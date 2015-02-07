<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 08.10.14 - 21:30
 */

namespace Prooph\Processing\Processor\Task;

/**
 * Class NotifyListeners
 *
 * @package Prooph\Processing\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class NotifyListeners implements Task
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
 