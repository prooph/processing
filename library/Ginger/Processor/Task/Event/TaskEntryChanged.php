<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 18:43
 */

namespace Ginger\Processor\Task\Event;

use Ginger\Processor\Task\TaskListPosition;
use Prooph\EventSourcing\AggregateChanged;

/**
 * Class TaskEntryChanged
 *
 * @package Ginger\Processor\Task\Event
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
 