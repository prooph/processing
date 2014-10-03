<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 18:41
 */

namespace Ginger\Processor\Task\Event;

use Ginger\Processor\Task\TaskListPosition;

class TaskEntryMarkedAsRunning extends TaskEntryChangedEvent
{
    public static function at(TaskListPosition $taskListPosition)
    {
        return self::occur($taskListPosition);
    }
}
 