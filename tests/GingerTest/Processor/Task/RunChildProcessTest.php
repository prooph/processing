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
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\RunChildProcess;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;

/**
 * Class RunChildProcessTest
 *
 * @package GingerTest\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class RunChildProcessTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_start_child_process_command()
    {
        $childProcessDefinition = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [
                [
                    "task_type"   => Definition::TASK_COLLECT_DATA,
                    "source"      => 'test-case',
                    "ginger_type" => 'GingerTest\Mock\UserDictionary'
                ]
            ],
            "config" => [Definition::CONFIG_STOP_ON_ERROR => true],
        ];

        $task = RunChildProcess::setUp($childProcessDefinition);

        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(ProcessId::generate()), 1);

        $startChildProcess = $task->generateStartCommandForChildProcess($parentTaskListPosition);

        $this->assertTrue($parentTaskListPosition->equals($startChildProcess->parentTaskListPosition()));

        $this->assertEquals($childProcessDefinition, $startChildProcess->childProcessDefinition());
    }

    /**
     * @test
     */
    public function it_returns_start_child_process_command_including_previous_message()
    {
        $childProcessDefinition = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [
                [
                    "task_type"   => Definition::TASK_COLLECT_DATA,
                    "source"      => 'test-case',
                    "ginger_type" => 'GingerTest\Mock\UserDictionary'
                ]
            ],
            "config" => [Definition::CONFIG_STOP_ON_ERROR => true],
        ];

        $task = RunChildProcess::setUp($childProcessDefinition);

        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(ProcessId::generate()), 1);

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

        $startChildProcess = $task->generateStartCommandForChildProcess($parentTaskListPosition, $previousMessage);

        $this->assertTrue($parentTaskListPosition->equals($startChildProcess->parentTaskListPosition()));

        $this->assertEquals($childProcessDefinition, $startChildProcess->childProcessDefinition());

        $this->assertEquals($previousMessage->getMessageName(), $startChildProcess->previousWorkflowMessage()->getMessageName());
    }
}
 