<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.10.14 - 19:53
 */

namespace GingerTest\Processor\Task;

use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Definition;
use Ginger\Processor\LinearMessagingProcess;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\RunSubProcess;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;

/**
 * Class RunSubProcessTest
 *
 * @package GingerTest\Processor\Task
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
                    "ginger_type" => 'GingerTest\Mock\UserDictionary'
                ]
            ],
            "config" => [Definition::PROCESS_CONFIG_STOP_ON_ERROR => true],
        ];

        $task = RunSubProcess::setUp(NodeName::fromString('other_machine'), $subProcessDefinition);

        $this->assertInstanceOf('Ginger\Processor\NodeName', $task->getTargetNodeName());

        $this->assertEquals('other_machine', $task->getTargetNodeName()->toString());
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
                    "ginger_type" => 'GingerTest\Mock\UserDictionary'
                ]
            ],
            "config" => [Definition::PROCESS_CONFIG_STOP_ON_ERROR => true],
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
                    "ginger_type" => 'GingerTest\Mock\UserDictionary'
                ]
            ],
            "config" => [Definition::PROCESS_CONFIG_STOP_ON_ERROR => true],
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
        ]));

        $startSubProcess = $task->generateStartCommandForSubProcess($parentTaskListPosition, $previousMessage);

        $this->assertTrue($parentTaskListPosition->equals($startSubProcess->parentTaskListPosition()));

        $this->assertEquals($subProcessDefinition, $startSubProcess->subProcessDefinition());

        $this->assertEquals($previousMessage->getMessageName(), $startSubProcess->previousWorkflowMessage()->getMessageName());
    }
}
 