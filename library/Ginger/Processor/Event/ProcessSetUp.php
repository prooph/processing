<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 23:52
 */

namespace Ginger\Processor\Event;

use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\TaskList;
use Prooph\EventSourcing\AggregateChanged;

/**
 * Class ProcessSetUp Event
 *
 * @package Ginger\Processor\Event
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessSetUp extends AggregateChanged
{
    /**
     * @var ProcessId
     */
    private $processId;

    /**
     * @var ProcessId
     */
    private $parentProcessId;

    /**
     * @param ProcessId $processId
     * @param TaskList $taskList
     * @param array $config
     * @return ProcessSetUp
     */
    public static function with(ProcessId $processId, TaskList $taskList, array $config)
    {
        $instance = self::occur(
            $processId->toString(),
            [
                'config' => $config,
                'parentProcessId' => null,
                'taskList' => $taskList->getArrayCopy()
            ]
        );

        $instance->processId = $processId;

        return $instance;
    }

    /**
     * @param ProcessId $processId
     * @param ProcessId $parentProcessId
     * @param TaskList $taskList
     * @param array $config
     * @return static
     */
    public static function asChildProcess(ProcessId $processId, ProcessId $parentProcessId, TaskList $taskList, array $config)
    {
        $instance = self::occur(
            $processId->toString(),
            [
                'config' => $config,
                'parentProcessId' => $parentProcessId->toString(),
                'taskList' => $taskList->getArrayCopy()
            ]
        );

        $instance->processId = $processId;

        $instance->parentProcessId = $parentProcessId;

        return $instance;
    }

    /**
     * @return ProcessId
     */
    public function processId()
    {
        if (is_null($this->processId)) {
            $this->processId = ProcessId::fromString($this->aggregateId);
        }

        return $this->processId;
    }

    /**
     * @return ProcessId|null
     */
    public function parentProcessId()
    {
        if (is_null($this->parentProcessId) && ! is_null($this->payload['parentProcessId'])) {
            $this->parentProcessId = ProcessId::fromString($this->payload['parentProcessId']);
        }

        return $this->parentProcessId;
    }

    /**
     * @return array
     */
    public function config()
    {
        return $this->payload['config'];
    }

    /**
     * @return array[taskListId => string, entries => entryArr[]]
     */
    public function taskList()
    {
        return $this->payload['taskList'];
    }
}
 