<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 16:55
 */

namespace Ginger\Processor;

use Assert\Assertion;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Task\CollectData;
use Ginger\Processor\Task\ProcessData;
use Ginger\Processor\Task\Task;

/**
 * Class ProcessFactory
 *
 * @package Ginger\Processor
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
     * @throws \InvalidArgumentException If process definition for message is not defined
     * @return Process
     */
    public function deriveProcessFromMessage(WorkflowMessage $message)
    {
        if (isset($this->processDefinitions[$message->getMessageName()])) {
            return $this->createProcessFromDefinition($this->processDefinitions[$message->getMessageName()]);
        }

        throw new \InvalidArgumentException(sprintf(
            "Create process from message failed due to unknown message: %s",
            $message->getMessageName()
        ));
    }

    /**
     * @param array $processDefinition
     * @throws \InvalidArgumentException If process definition is incomplete or invalid
     * @return Process
     */
    public function createProcessFromDefinition(array $processDefinition)
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
                return LinearMessagingProcess::setUp($tasks, $processConfig);
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
        \Assert\that($taskDefinition["source"])->notEmpty()->string();
        Assertion::keyExists($taskDefinition, "ginger_type");
        \Assert\that($taskDefinition["ginger_type"])->implementsInterface('Ginger\Type\Type');

        $gingerType = $taskDefinition["ginger_type"];

        $prototype = $gingerType::prototype();

        return CollectData::from($taskDefinition["source"], $prototype);
    }

    /**
     * @param array $taskDefinition
     * @return ProcessData
     */
    private function createProcessDataTaskFromDefinition(array $taskDefinition)
    {
        Assertion::keyExists($taskDefinition, "target");
        \Assert\that($taskDefinition["target"])->notEmpty()->string();
        Assertion::keyExists($taskDefinition, "allowed_types");
        Assertion::isArray($taskDefinition["allowed_types"]);
        Assertion::allString($taskDefinition["allowed_types"]);

        $preferredType = (isset($taskDefinition["preferred_type"]))? $taskDefinition["preferred_type"] : null;

        return ProcessData::address($taskDefinition["target"], $taskDefinition["allowed_types"], $preferredType);
    }
}
 