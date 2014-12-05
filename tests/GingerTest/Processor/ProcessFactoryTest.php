<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 19:48
 */

namespace GingerTest\Processor;

use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Command\StartSubProcess;
use Ginger\Processor\Definition;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessFactory;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use Ginger\Type\String;
use GingerTest\TestCase;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\Router\CommandRouter;

/**
 * Class ProcessFactoryTest
 *
 * @package GingerTest\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_linear_messaging_process_with_collect_data_task_from_process_definition()
    {
        $processDefinition = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [
                [
                    "task_type"   => Definition::TASK_COLLECT_DATA,
                    "source"      => 'test-case',
                    "ginger_type" => 'GingerTest\Mock\UserDictionary'
                ]
            ],
        ];

        $processFactory = new ProcessFactory();

        $process = $processFactory->createProcessFromDefinition($processDefinition, NodeName::defaultName());

        $this->assertInstanceOf('Ginger\Processor\LinearProcess', $process);

        $this->assertFalse($process->isSubProcess());

        $process->perform($this->workflowEngine);

        $collectDataMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $collectDataMessage);

        $this->assertEquals('GingerTest\Mock\UserDictionary', $collectDataMessage->getPayload()->getTypeClass());
    }

    /**
     * @test
     */
    public function it_creates_linear_messaging_process_as_sub_process_if_parent_process_id_is_given()
    {
        $processDefinition = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [
                [
                    "task_type"   => Definition::TASK_COLLECT_DATA,
                    "source"      => 'test-case',
                    "ginger_type" => 'GingerTest\Mock\UserDictionary'
                ]
            ],
        ];

        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $processFactory = new ProcessFactory();

        $process = $processFactory->createProcessFromDefinition(
            $processDefinition,
            NodeName::defaultName(),
            $parentTaskListPosition
        );

        $this->assertInstanceOf('Ginger\Processor\LinearProcess', $process);

        $this->assertTrue($process->isSubProcess());

        $this->assertTrue($parentTaskListPosition->equals($process->parentTaskListPosition()));
    }

    /**
     * @test
     */
    public function it_creates_process_with_process_data_task_from_data_collected_message()
    {
        $processDefinition = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [
                [
                    "task_type"      => Definition::TASK_PROCESS_DATA,
                    "target"         => 'test-target',
                    "allowed_types"  => ['GingerTest\Mock\TargetUserDictionary', 'GingerTest\Mock\AddressDictionary'],
                    "preferred_type" => 'GingerTest\Mock\AddressDictionary'
                ]
            ]
        ];

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $factory = new ProcessFactory(
            [
                $wfMessage->getMessageName() => $processDefinition
            ]
        );

        $process = $factory->deriveProcessFromMessage($wfMessage, NodeName::defaultName());

        $this->assertInstanceOf('Ginger\Processor\LinearProcess', $process);

        $this->commandRouter->route(
            MessageNameUtils::getProcessDataCommandName('GingerTest\Mock\AddressDictionary')
        )
        ->to($this->workflowMessageHandler);

        $process->perform($this->workflowEngine, $wfMessage);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertEquals('GingerTest\Mock\AddressDictionary', $receivedMessage->getPayload()->getTypeClass());
    }

    /**
     * @test
     */
    public function it_creates_process_with_run_sub_process_task_from_definition()
    {
        $subProcessDefinition = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [
                [
                    "task_type"   => Definition::TASK_COLLECT_DATA,
                    "source"      => 'test-case',
                    "ginger_type" => 'GingerTest\Mock\UserDictionary'
                ]
            ],
        ];

        $runSubProcessTaskDefinition = [
            "target_node_name" => 'other_machine',
            "task_type" => Definition::TASK_RUN_SUB_PROCESS,
            "process_definition" => $subProcessDefinition
        ];

        $parentProcessDefinition = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [$runSubProcessTaskDefinition]
        ];

        $processFactory = new ProcessFactory();

        $parentProcess = $processFactory->createProcessFromDefinition($parentProcessDefinition, NodeName::defaultName());

        /** @var $startSubProcess StartSubProcess */
        $startSubProcess = null;

        $otherMachineCommandBus = new CommandBus();

        $otherMachineCommandRouter = new CommandRouter();

        $otherMachineCommandRouter->route(StartSubProcess::MSG_NAME)->to(function(StartSubProcess $command) use (&$startSubProcess) {
            $startSubProcess = $command;
        });

        $otherMachineCommandBus->utilize($otherMachineCommandRouter);

        $otherMachineCommandBus->utilize(new CallbackStrategy());

        $this->workflowEngine->registerCommandBus($otherMachineCommandBus, ['other_machine']);

        $parentProcess->perform($this->workflowEngine);

        $this->assertNotNull($startSubProcess);

        $this->assertEquals($subProcessDefinition, $startSubProcess->subProcessDefinition());
    }

    /**
     * @test
     */
    function it_creates_linear_messaging_process_with_manipulate_payload_task_from_definition()
    {
        $definition = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [
                [
                    "task_type" => Definition::TASK_MANIPULATE_PAYLOAD,
                    'manipulation_script' => __DIR__ . '/../Mock/manipulation/append_world.php'
                ]
            ]
        ];

        $processFactory = new ProcessFactory();

        $process = $processFactory->createProcessFromDefinition($definition, NodeName::defaultName());

        $this->assertInstanceOf('Ginger\Processor\LinearProcess', $process);

        $message = WorkflowMessage::newDataCollected(String::fromString('Hello'));

        $process->perform($this->workflowEngine, $message);

        $this->assertTrue($process->isSuccessfulDone());

        $this->assertEquals('Hello World', $message->getPayload()->getData());
    }
}
 