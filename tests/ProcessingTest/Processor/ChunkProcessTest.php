<?php
/*
 * This file is part of the prooph processing framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 14.03.15 - 00:04
 */

namespace Prooph\ProcessingTest\Processor;

use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\ChunkProcess;
use Prooph\Processing\Processor\Command\StartSubProcess;
use Prooph\Processing\Processor\Definition;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessFactory;
use Prooph\Processing\Processor\RegistryWorkflowEngine;
use Prooph\Processing\Type\String;
use Prooph\Processing\Type\StringCollection;
use Prooph\ProcessingTest\TestCase;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\Router\CommandRouter;

final class ChunkProcessTest extends TestCase
{
    /**
     * @var StartSubProcess[]
     */
    private $startSubProcessCommands = [];

    protected function setUp()
    {
        $commandBus = new CommandBus();

        $commandRouter = new CommandRouter();

        $commandRouter->route(StartSubProcess::MSG_NAME)->to(function ($command) {
            $this->startSubProcessCommands[] = $command;
        });

        $commandBus->utilize($commandRouter);

        $commandBus->utilize(new CallbackStrategy());

        $this->workflowEngine = new RegistryWorkflowEngine();

        $this->workflowEngine->registerCommandBus($commandBus, [NodeName::defaultName()->toString()]);
    }

    protected function tearDown()
    {
        $this->startSubProcessCommands = [];
    }

    /**
     * @test
     * @dataProvider provideStringCollection
     */
    public function it_performs_a_sub_process_for_each_chunk_of_a_collection(StringCollection $stringCollection)
    {
        $processDefinition = [
            'process_type' => Definition::PROCESS_PARALLEL_CHUNK,
            'tasks'        => [
                [
                    "task_type" => Definition::TASK_RUN_SUB_PROCESS,
                    "target_node_name" => NodeName::defaultName()->toString(),
                    "process_definition" => [
                        "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
                        "tasks" => [
                            //Normally the task list should start with a collect data task to read the next chunk
                            //but we skip that task due to testing purposes.
                            [
                                "task_type" => Definition::TASK_PROCESS_DATA,
                                "target"    => 'test-target',
                                "allowed_types" => ['Prooph\Processing\Type\String']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $processFactory = new ProcessFactory();

        $chunkProcess = $processFactory->createProcessFromDefinition($processDefinition, NodeName::defaultName());

        $this->assertInstanceOf('Prooph\Processing\Processor\ChunkProcess', $chunkProcess);

        $message = WorkflowMessage::newDataCollected($stringCollection, 'test-case', NodeName::defaultName(), [
            ChunkProcess::META_OFFSET => 0,
            ChunkProcess::META_LIMIT => 2,
            ChunkProcess::META_TOTAL_ITEMS => 6,
            ChunkProcess::META_COUNT_ONLY => true
        ]);

        $chunkProcess->perform($this->workflowEngine, $message);

        $this->assertEquals(3, count($this->startSubProcessCommands));

        $this->assertFalse($chunkProcess->isFinished());

        foreach ($this->startSubProcessCommands as $i =>  $command) {
            $mockedMessage = WorkflowMessage::newDataCollected(String::fromNativeValue("Fake message"), 'test-case', NodeName::defaultName());

            $mockedMessage->connectToProcessTask($command->parentTaskListPosition());

            $chunkProcess->receiveMessage($mockedMessage, $this->workflowEngine);
        }

        $this->assertTrue($chunkProcess->isSuccessfulDone());
    }

    public function provideStringCollection()
    {
        $string1 = String::fromNativeValue("Processing");
        $string2 = String::fromNativeValue("Workflow");
        $string3 = String::fromNativeValue("Framework");
        $string4 = String::fromNativeValue("Prooph");
        $string5 = String::fromNativeValue("Link");
        $string6 = String::fromNativeValue("Application");

        $stringCollection = StringCollection::fromNativeValue([$string1, $string2, $string3, $string4, $string5, $string6]);

        return [[$stringCollection]];
    }
}
 