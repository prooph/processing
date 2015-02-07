<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 19:48
 */

namespace Prooph\ProcessingTest\Processor;

use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Command\StartSubProcess;
use Prooph\Processing\Processor\Definition;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessFactory;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\Processing\Type\String;
use Prooph\ProcessingTest\TestCase;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\Router\CommandRouter;

/**
 * Class ProcessFactoryTest
 *
 * @package Prooph\ProcessingTest\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessFactoryTest extends TestCase
{
    protected function setUp()
    {
        parent::setUpLocalMachine();
    }

    protected function tearDown()
    {
        parent::tearDownTestEnvironment();
    }

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
                    "processing_type" => 'Prooph\ProcessingTest\Mock\UserDictionary',
                    "metadata"    => [
                        'filter' => [
                            'name' => 'John'
                        ]
                    ]
                ]
            ],
        ];

        $processFactory = new ProcessFactory();

        $process = $processFactory->createProcessFromDefinition($processDefinition, NodeName::defaultName());

        $this->assertInstanceOf('Prooph\Processing\Processor\LinearProcess', $process);

        $this->assertFalse($process->isSubProcess());

        $process->perform($this->workflowEngine);

        $collectDataMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertInstanceOf('Prooph\Processing\Message\WorkflowMessage', $collectDataMessage);

        $this->assertEquals('Prooph\ProcessingTest\Mock\UserDictionary', $collectDataMessage->payload()->getTypeClass());

        $this->assertEquals(['filter' => ['name' => 'John']], $collectDataMessage->metadata());
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
                    "processing_type" => 'Prooph\ProcessingTest\Mock\UserDictionary'
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

        $this->assertInstanceOf('Prooph\Processing\Processor\LinearProcess', $process);

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
                    "allowed_types"  => ['Prooph\ProcessingTest\Mock\TargetUserDictionary', 'Prooph\ProcessingTest\Mock\AddressDictionary'],
                    "preferred_type" => 'Prooph\ProcessingTest\Mock\AddressDictionary',
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

        $this->assertInstanceOf('Prooph\Processing\Processor\LinearProcess', $process);

        $this->commandRouter->route(
            MessageNameUtils::getProcessDataCommandName('Prooph\ProcessingTest\Mock\AddressDictionary')
        )
        ->to($this->workflowMessageHandler);

        $process->perform($this->workflowEngine, $wfMessage);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertEquals('Prooph\ProcessingTest\Mock\AddressDictionary', $receivedMessage->payload()->getTypeClass());
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
                    "processing_type" => 'Prooph\ProcessingTest\Mock\UserDictionary'
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

        $this->assertInstanceOf('Prooph\Processing\Processor\LinearProcess', $process);

        $message = WorkflowMessage::newDataCollected(
            String::fromString('Hello'),
            'test-case',
            NodeName::defaultName()
        );

        $process->perform($this->workflowEngine, $message);

        $this->assertTrue($process->isSuccessfulDone());

        $this->assertEquals('Hello World', $message->payload()->extractTypeData());
    }
}
 