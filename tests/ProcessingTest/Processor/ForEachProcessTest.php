<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 06.12.14 - 17:42
 */

namespace Prooph\ProcessingTest\Processor;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Command\StartSubProcess;
use Prooph\Processing\Processor\Definition;
use Prooph\Processing\Processor\Event\SubProcessFinished;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessFactory;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\RegistryWorkflowEngine;
use Prooph\Processing\Type\String;
use Prooph\Processing\Type\StringCollection;
use Prooph\ProcessingTest\TestCase;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\Router\CommandRouter;

/**
 * Class ForEachProcessTest
 *
 * @package Prooph\ProcessingTest\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ForEachProcessTest extends TestCase
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
    public function it_performs_a_sub_process_for_each_item_of_a_collection(StringCollection $stringCollection)
    {
        $processDefinition = [
            'process_type' => Definition::PROCESS_PARALLEL_FOR_EACH,
            'tasks'        => [
                [
                    "task_type" => Definition::TASK_RUN_SUB_PROCESS,
                    "target_node_name" => NodeName::defaultName()->toString(),
                    "process_definition" => [
                        "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
                        "tasks" => [
                            "task_type" => Definition::TASK_PROCESS_DATA,
                            "target"    => 'test-target',
                            "allowed_types" => ['Prooph\Processing\Type\String']
                        ]
                    ]
                ]
            ]
        ];

        $processFactory = new ProcessFactory();

        $forEachProcess = $processFactory->createProcessFromDefinition($processDefinition, NodeName::defaultName());

        $this->assertInstanceOf('Prooph\Processing\Processor\ForEachProcess', $forEachProcess);

        $message = WorkflowMessage::newDataCollected($stringCollection, 'test-case', NodeName::defaultName());

        $forEachProcess->perform($this->workflowEngine, $message);

        $this->assertEquals(3, count($this->startSubProcessCommands));

        $this->assertFalse($forEachProcess->isFinished());

        foreach ($this->startSubProcessCommands as $command) {
            $mockedMessage = WorkflowMessage::newDataCollected(String::fromNativeValue("Fake message"), 'test-case', NodeName::defaultName());

            $mockedMessage->connectToProcessTask($command->parentTaskListPosition());

            $forEachProcess->receiveMessage($mockedMessage, $this->workflowEngine);
        }

        $this->assertTrue($forEachProcess->isSuccessfulDone());
    }

    public function provideStringCollection()
    {
        $string1 = String::fromNativeValue("Processing");
        $string2 = String::fromNativeValue("Workflow");
        $string3 = String::fromNativeValue("Framework");

        $stringCollection = StringCollection::fromNativeValue([$string1, $string2, $string3]);

        return [[$stringCollection]];
    }
}
 