<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 18:43
 */

namespace Prooph\Processing\Processor\Task\Event;

use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\EventSourcing\AggregateChanged;

/**
 * Class TaskEntryChanged
 *
 * @package Prooph\Processing\Processor\Task\Event
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TaskEntryChanged extends AggregateChanged
{
    protected $taskListPosition;

    public static function at(TaskListPosition $taskListPosition, array $payload = array())
    {
        $payload['taskListPosition'] = $taskListPosition->toString();

        return parent::occur($taskListPosition->taskListId()->processId()->toString(), $payload);
    }

    /**
     * @return TaskListPosition
     */
    public function taskListPosition()
    {
        return TaskListPosition::fromString($this->payload['taskListPosition']);
    }
}
 