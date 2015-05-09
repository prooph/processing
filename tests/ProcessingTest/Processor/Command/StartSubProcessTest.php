<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.10.14 - 21:08
 */

namespace Prooph\ProcessingTest\Processor\Command;

use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Command\StartSubProcess;
use Prooph\Processing\Processor\Definition;
use Prooph\Processing\Processor\LinearProcess;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\ProcessingTest\Mock\UserDictionary;
use Prooph\ProcessingTest\TestCase;

/**
 * Class StartSubProcessTest
 *
 * @package Prooph\ProcessingTest\Processor\Command
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
                    "processing_type" => 'Prooph\ProcessingTest\Mock\UserDictionary'
                ]
            ],
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
        ]), 'test-case', 'processor');

        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $command = StartSubProcess::at($parentTaskListPosition, $subProcessDefinition, true, 'sub-processor', $previousMessage);

        $this->assertTrue($parentTaskListPosition->equals($command->parentTaskListPosition()));

        $this->assertTrue($command->syncLogMessages());

        $this->assertEquals($subProcessDefinition, $command->subProcessDefinition());

        $this->assertEquals($previousMessage->messageName(), $command->previousWorkflowMessage()->messageName());

        $this->assertEquals(NodeName::defaultName()->toString(), $command->origin());

        $this->assertEquals('sub-processor', $command->target());
    }

    /**
     * @test
     */
    public function it_does_not_require_a_previous_message()
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

        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $command = StartSubProcess::at($parentTaskListPosition, $subProcessDefinition, false, 'sub-processor');

        $this->assertTrue($parentTaskListPosition->equals($command->parentTaskListPosition()));

        $this->assertEquals($subProcessDefinition, $command->subProcessDefinition());

        $this->assertFalse($command->syncLogMessages());

        $this->assertEquals('sub-processor', $command->target());
    }

    /**
     * @test
     */
    public function it_translates_to_service_bus_message_and_back()
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

        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $command = StartSubProcess::at($parentTaskListPosition, $subProcessDefinition, false, 'sub-processor');

        $sbMessage = $command->toServiceBusMessage();

        $this->assertInstanceOf('Prooph\Common\Messaging\RemoteMessage', $sbMessage);

        $copyOfCommand = StartSubProcess::fromServiceBusMessage($sbMessage);

        $this->assertInstanceOf('Prooph\Processing\Processor\Command\StartSubProcess', $copyOfCommand);

        $this->assertTrue($parentTaskListPosition->equals($copyOfCommand->parentTaskListPosition()));

        $this->assertEquals($subProcessDefinition, $copyOfCommand->subProcessDefinition());

        $this->assertFalse($copyOfCommand->syncLogMessages());

        $this->assertEquals(NodeName::defaultName()->toString(), $copyOfCommand->origin());

        $this->assertEquals('sub-processor', $copyOfCommand->target());
    }
}
 