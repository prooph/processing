<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.10.14 - 21:08
 */

namespace GingerTest\Processor\Command;

use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Command\StartSubProcess;
use Ginger\Processor\Definition;
use Ginger\Processor\LinearMessagingProcess;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;

/**
 * Class StartSubProcessTest
 *
 * @package GingerTest\Processor\Command
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StartSubProcessTest extends TestCase
{
    /**
     * @test
     */
    public function it_collects_information_for_the_sub_process()
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

        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(ProcessId::generate()), 1);

        $command = StartSubProcess::at($parentTaskListPosition, $subProcessDefinition, $previousMessage);

        $this->assertTrue($parentTaskListPosition->equals($command->parentTaskListPosition()));

        $this->assertEquals($subProcessDefinition, $command->subProcessDefinition());

        $this->assertEquals($previousMessage->getMessageName(), $command->previousWorkflowMessage()->getMessageName());
    }

    /**
     * @test
     */
    public function it_does_not_require_a_previous_message()
    {
        $parentProcess = LinearMessagingProcess::setUp([]);

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

        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(ProcessId::generate()), 1);

        $command = StartSubProcess::at($parentTaskListPosition, $subProcessDefinition);

        $this->assertTrue($parentTaskListPosition->equals($command->parentTaskListPosition()));

        $this->assertEquals($subProcessDefinition, $command->subProcessDefinition());
    }
}
 