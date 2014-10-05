<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 04.10.14 - 20:07
 */

namespace Ginger\Processor\Task\Event;

use Ginger\Processor\Task\TaskListPosition;

class TaskEntryMarkedAsDone extends TaskEntryChangedEvent
{
    public static function at(TaskListPosition $taskListPosition)
    {
        return self::occur($taskListPosition);
    }
}
 