<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 16:55
 */

namespace Prooph\Processing\Processor;

use Assert\Assertion;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Task\CollectData;
use Prooph\Processing\Processor\Task\ManipulatePayload;
use Prooph\Processing\Processor\Task\ProcessData;
use Prooph\Processing\Processor\Task\RunSubProcess;
use Prooph\Processing\Processor\Task\Task;
use Prooph\Processing\Processor\Task\TaskListPosition;

/**
 * Class ProcessFactory
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessFactory 
{
    /**
     * @var array
     */
    private $processDefinitions = array();

    /**
     * @param array $processDefinitions
     */
    public function __construct(array $processDefinitions = array())
    {
        $this->processDefinitions = $processDefinitions;
    }

    /**
     * @param WorkflowMessage $message
     * @param NodeName $nodeName
     * @throws \InvalidArgumentException If process definition for message is not defined
     * @return Process
     */
    public function deriveProcessFromMessage(WorkflowMessage $message, NodeName $nodeName)
    {
        if (isset($this->processDefinitions[$message->messageName()])) {
            return $this->createProcessFromDefinition($this->processDefinitions[$message->messageName()], $nodeName);
        }

        throw new \InvalidArgumentException(sprintf(
            "Derive process from message failed due to unknown message: %s",
            $message->messageName()
        ));
    }

    /**
     * @param array $processDefinition
     * @param NodeName $nodeName
     * @param TaskListPosition|null $parentTaskListPosition
     * @throws \InvalidArgumentException If process definition is incomplete or invalid
     * @return Process
     */
    public function createProcessFromDefinition(array $processDefinition, NodeName $nodeName, TaskListPosition $parentTaskListPosition = null)
    {
        Assertion::keyExists($processDefinition, "process_type");
        Assertion::keyExists($processDefinition, "tasks");
        Assertion::isArray($processDefinition["tasks"]);

        $tasks = [];

        foreach ($processDefinition["tasks"] as $task) {
            Assertion::isArray($task);
            $tasks[] = $this->createTaskFromDefinition($task);
        }

        $processConfig = (isset($processDefinition['config']) && is_array($processDefinition['config']))?
            $processDefinition['config'] : array();

        switch($processDefinition["process_type"]) {
            case Definition::PROCESS_LINEAR_MESSAGING:
                return (is_null($parentTaskListPosition))?
                    LinearProcess::setUp($nodeName, $tasks, $processConfig)
                    : LinearProcess::setUpAsSubProcess($parentTaskListPosition, $nodeName, $tasks, $processConfig);
            case Definition::PROCESS_PARALLEL_FOR_EACH:
                return (is_null($parentTaskListPosition))?
                    ForEachProcess::setUp($nodeName, $tasks, $processConfig)
                    : ForEachProcess::setUpAsSubProcess($parentTaskListPosition, $nodeName, $tasks, $processConfig);
            case Definition::PROCESS_PARALLEL_CHUNK:
                return (is_null($parentTaskListPosition))?
                    ChunkProcess::setUp($nodeName, $tasks, $processConfig)
                    : ChunkProcess::setUpAsSubProcess($parentTaskListPosition, $nodeName, $tasks, $processConfig);
            default:
                throw new \InvalidArgumentException(sprintf(
                    "Unsupported process_type given: %s",
                    $processDefinition["process_type"]
                ));
        }
    }

    /**
     * @param array $taskDefinition
     * @throws \InvalidArgumentException If task definition is incomplete or invalid
     * @return Task
     */
    public function createTaskFromDefinition(array $taskDefinition)
    {
        Assertion::keyExists($taskDefinition, "task_type");

        switch($taskDefinition["task_type"]) {
            case Definition::TASK_COLLECT_DATA:
                return $this->createCollectDataTaskFromDefinition($taskDefinition);
            case Definition::TASK_PROCESS_DATA:
                return $this->createProcessDataTaskFromDefinition($taskDefinition);
            case Definition::TASK_RUN_SUB_PROCESS:
                return $this->createRunSubProcessTaskFromDefinition($taskDefinition);
            case Definition::TASK_MANIPULATE_PAYLOAD:
                return $this->createManipulatePayloadFromDefinition($taskDefinition);
            default:
                throw new \InvalidArgumentException(sprintf(
                    "Unsupported task_type given: %s",
                    $taskDefinition['task_type']
                ));

        }
    }

    /**
     * @param array $taskDefinition
     * @return CollectData
     */
    private function createCollectDataTaskFromDefinition(array $taskDefinition)
    {
        Assertion::keyExists($taskDefinition, "source");
        Assertion::notEmpty($taskDefinition["source"]);
        Assertion::string($taskDefinition["source"]);
        Assertion::keyExists($taskDefinition, "processing_type");
        Assertion::implementsInterface($taskDefinition["processing_type"], 'Prooph\Processing\Type\Type');

        $processingType = $taskDefinition["processing_type"];

        $prototype = $processingType::prototype();

        $metadata = isset($taskDefinition['metadata'])? $taskDefinition['metadata'] : array();

        return CollectData::from($taskDefinition["source"], $prototype, $metadata);
    }

    /**
     * @param array $taskDefinition
     * @return ProcessData
     */
    private function createProcessDataTaskFromDefinition(array $taskDefinition)
    {
        Assertion::keyExists($taskDefinition, "target");
        Assertion::notEmpty($taskDefinition["target"]);
        Assertion::string($taskDefinition["target"]);
        Assertion::keyExists($taskDefinition, "allowed_types");
        Assertion::isArray($taskDefinition["allowed_types"]);
        Assertion::allString($taskDefinition["allowed_types"]);

        $preferredType = (isset($taskDefinition["preferred_type"]))? $taskDefinition["preferred_type"] : null;

        $metadata = isset($taskDefinition['metadata'])? $taskDefinition['metadata'] : array();

        return ProcessData::address($taskDefinition["target"], $taskDefinition["allowed_types"], $preferredType, $metadata);
    }

    /**
     * @param array $taskDefinition
     * @return RunSubProcess
     */
    private function createRunSubProcessTaskFromDefinition(array $taskDefinition)
    {
        Assertion::keyExists($taskDefinition, "target_node_name");
        Assertion::keyExists($taskDefinition, "process_definition");
        Assertion::isArray($taskDefinition["process_definition"]);

        return RunSubProcess::setUp(NodeName::fromString($taskDefinition['target_node_name']), $taskDefinition["process_definition"]);
    }

    /**
     * @param array $taskDefinition
     * @return ManipulatePayload
     */
    private function createManipulatePayloadFromDefinition(array $taskDefinition)
    {
        Assertion::keyExists($taskDefinition, 'manipulation_script');

        return ManipulatePayload::with($taskDefinition['manipulation_script']);
    }
}
 