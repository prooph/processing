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
use Assert\Assertion;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Command\StartSubProcess;
use Ginger\Processor\Process;

/**
 * Class RunSubProcess
 *
 * This task provides information that a sub process should be used to perform the next step.
 * The parent process should use RunSubProcess::getStartCommandForSubProcess and send this command to
 * the WorkflowProcessor. The WorkflowProcessor creates the sub process from given information and performs it.
 *
 * @package Ginger\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class RunSubProcess implements Task
{
    /**
     * @var array
     */
    private $processDefinition;

    /**
     * @param array $processDefinition
     * @return RunSubProcess
     */
    public static function setUp(array $processDefinition)
    {
        Assertion::keyExists($processDefinition, "process_type");

        return new self($processDefinition);
    }

    /**
     * @param array $taskData
     * @return static
     */
    public static function reconstituteFromArray(array $taskData)
    {
        Assertion::keyExists($taskData, 'process_definition');

        return new self($taskData['process_definition']);
    }

    /**
     * @param array $processDefinition
     */
    private function __construct(array $processDefinition)
    {
        $this->processDefinition = $processDefinition;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'process_definition' => $this->processDefinition
        ];
    }

    /**
     * @param TaskListPosition $parentTaskListPosition
     * @param WorkflowMessage|null $previousMessage
     * @return StartSubProcess
     */
    public function generateStartCommandForSubProcess(TaskListPosition $parentTaskListPosition, WorkflowMessage $previousMessage = null)
    {
        return StartSubProcess::at($parentTaskListPosition, $this->processDefinition, $previousMessage);
    }

    /**
     * @param Task $task
     * @return bool
     */
    public function equals(Task $task)
    {
        if (! $task instanceof RunSubProcess) return false;

        return $this->getArrayCopy() === $task->getArrayCopy();
    }
}
 