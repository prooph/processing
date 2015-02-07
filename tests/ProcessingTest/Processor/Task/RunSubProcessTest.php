<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.10.14 - 19:53
 */

namespace Prooph\ProcessingTest\Processor\Task;

use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Definition;
use Prooph\Processing\Processor\LinearProcess;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\RunSubProcess;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\ProcessingTest\Mock\UserDictionary;
use Prooph\ProcessingTest\TestCase;

/**
 * Class RunSubProcessTest
 *
 * @package Prooph\ProcessingTest\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class RunSubProcessTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_set_up_with_a_target_node_nome()
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

        $task = RunSubProcess::setUp(NodeName::fromString('other_machine'), $subProcessDefinition);

        $this->assertInstanceOf('Prooph\Processing\Processor\NodeName', $task->targetNodeName());

        $this->assertEquals('other_machine', $task->targetNodeName()->toString());
    }

    /**
     * @test
     */
    public function it_returns_start_sub_process_command()
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

        $task = RunSubProcess::setUp(NodeName::defaultName(), $subProcessDefinition);

        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $startSubProcess = $task->generateStartCommandForSubProcess($parentTaskListPosition);

        $this->assertTrue($parentTaskListPosition->equals($startSubProcess->parentTaskListPosition()));

        $this->assertEquals($subProcessDefinition, $startSubProcess->subProcessDefinition());
    }

    /**
     * @test
     */
    public function it_returns_start_sub_process_command_including_previous_message()
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

        $task = RunSubProcess::setUp(NodeName::defaultName(), $subProcessDefinition);

        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $previousMessage = WorkflowMessage::newDataCollected(UserDictionary::fromNativeValue([
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            ]
        ]), 'test-case', NodeName::defaultName());

        $startSubProcess = $task->generateStartCommandForSubProcess($parentTaskListPosition, $previousMessage);

        $this->assertTrue($parentTaskListPosition->equals($startSubProcess->parentTaskListPosition()));

        $this->assertEquals($subProcessDefinition, $startSubProcess->subProcessDefinition());

        $this->assertEquals($previousMessage->getMessageName(), $startSubProcess->previousWorkflowMessage()->getMessageName());
    }
}
 