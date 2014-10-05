<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 04.10.14 - 21:06
 */

namespace Ginger\Processor\Task\Event;

use Ginger\Processor\Task\TaskListPosition;

/**
 * Class TaskEntryMarkedAsFailed
 *
 * @package Ginger\Processor\Task\Event
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TaskEntryMarkedAsFailed extends TaskEntryChangedEvent
{
    public static function at(TaskListPosition $taskListPosition)
    {
        return self::occur($taskListPosition);
    }
}
 