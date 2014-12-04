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
use Ginger\Processor\NodeName;
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
     * @var NodeName
     */
    private $targetNodeName;

    /**
     * @var array
     */
    private $processDefinition;

    /**
     * @var bool
     */
    private $syncLogMessages;

    /**
     * @param NodeName $targetNodeName
     * @param array $processDefinition
     * @param bool $syncLogMessages
     * @return RunSubProcess
     */
    public static function setUp(NodeName $targetNodeName, array $processDefinition, $syncLogMessages = true)
    {
        Assertion::keyExists($processDefinition, "process_type");

        return new self($targetNodeName, $processDefinition, $syncLogMessages);
    }

    /**
     * @param array $taskData
     * @return static
     */
    public static function reconstituteFromArray(array $taskData)
    {
        Assertion::keyExists($taskData, 'target_node_name');
        Assertion::keyExists($taskData, 'process_definition');
        Assertion::keyExists($taskData, 'sync_log_messages');

        return new self(
            NodeName::fromString($taskData['target_node_name']),
            $taskData['process_definition'],
            (bool)$taskData['sync_log_messages']
        );
    }

    /**
     * @param NodeName $targetNodeName
     * @param array $processDefinition
     * @param bool $syncLogMessages
     * @throws \InvalidArgumentException
     */
    private function __construct(NodeName $targetNodeName, array $processDefinition, $syncLogMessages)
    {
        if (! is_bool($syncLogMessages)) {
            throw new \InvalidArgumentException("Argument syncLogMessages must be of type boolean");
        }

        $this->targetNodeName    = $targetNodeName;
        $this->processDefinition = $processDefinition;
        $this->syncLogMessages   = $syncLogMessages;
    }

    /**
     * @return NodeName
     */
    public function getTargetNodeName()
    {
        return $this->targetNodeName;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'target_node_name'   => $this->targetNodeName->toString(),
            'process_definition' => $this->processDefinition,
            'sync_log_messages'  => $this->syncLogMessages,
        ];
    }

    /**
     * @param TaskListPosition $parentTaskListPosition
     * @param WorkflowMessage|null $previousMessage
     * @return StartSubProcess
     */
    public function generateStartCommandForSubProcess(TaskListPosition $parentTaskListPosition, WorkflowMessage $previousMessage = null)
    {
        return StartSubProcess::at($parentTaskListPosition, $this->processDefinition, $this->syncLogMessages, $previousMessage);
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
 