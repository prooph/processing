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
use Ginger\Processor\Definition;
use Ginger\Processor\ProcessFactory;
use GingerTest\TestCase;

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
            "config" => [Definition::CONFIG_STOP_ON_ERROR => true],
        ];

        $processFactory = new ProcessFactory();

        $process = $processFactory->createProcessFromDefinition($processDefinition);

        $this->assertInstanceOf('Ginger\Processor\LinearMessagingProcess', $process);

        $process->perform($this->workflowEngine);

        $collectDataMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $collectDataMessage);

        $this->assertEquals('GingerTest\Mock\UserDictionary', $collectDataMessage->getPayload()->getTypeClass());

        $this->assertTrue($process->config()->booleanValue(Definition::CONFIG_STOP_ON_ERROR));
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

        $process = $factory->deriveProcessFromMessage($wfMessage);

        $this->assertInstanceOf('Ginger\Processor\LinearMessagingProcess', $process);

        $this->commandRouter->route(
            MessageNameUtils::getProcessDataCommandName('GingerTest\Mock\AddressDictionary')
        )
        ->to($this->workflowMessageHandler);

        $process->perform($this->workflowEngine, $wfMessage);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertEquals('GingerTest\Mock\AddressDictionary', $receivedMessage->getPayload()->getTypeClass());
    }
}
 