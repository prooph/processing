<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 05.10.14 - 14:38
 */

namespace Prooph\ProcessingTest\Processor;

use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\AbstractWorkflowEngine;
use Prooph\Processing\Processor\LinearProcess;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\RegistryWorkflowEngine;
use Prooph\Processing\Processor\Task\CollectData;
use Prooph\Processing\Processor\Task\ProcessData;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\ProcessingTest\Mock\UserDictionary;
use Prooph\ProcessingTest\TestCase;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\Router\EventRouter;

/**
 * Class LinearProcessTest
 *
 * @package Prooph\ProcessingTest\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class LinearProcessTest extends TestCase
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
    public function it_performs_collect_data_as_first_task_if_no_initial_wfm_is_given()
    {
        $task = CollectData::from('test-case', UserDictionary::prototype());

        $process = LinearProcess::setUp(NodeName::defaultName(), [$task]);

        $process->perform($this->workflowEngine);

        $collectDataMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertInstanceOf('Prooph\Processing\Message\WorkflowMessage', $collectDataMessage);

        $this->assertEquals('Prooph\ProcessingTest\Mock\UserDictionary', $collectDataMessage->payload()->getTypeClass());

        $this->assertEquals('test-case', $collectDataMessage->target());

        $this->assertFalse($process->isSubProcess());

        $this->assertFalse($process->isFinished());

        $this->workflowMessageHandler->reset();

        //It should not perform the task twice
        $process->perform($this->workflowEngine);

        $this->assertNull($this->workflowMessageHandler->lastWorkflowMessage());
    }

    /**
     * @test
     */
    public function it_finishes_the_task_when_it_receives_an_answer_message()
    {
        $task = CollectData::from('test-case', UserDictionary::prototype());

        $process = LinearProcess::setUp(NodeName::defaultName(), [$task]);

        $wfm = WorkflowMessage::collectDataOf(UserDictionary::prototype(), NodeName::defaultName(), 'test-handler');

        $answer = $wfm->answerWith(UserDictionary::fromNativeValue([
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            ]
        ]));

        $this->workflowMessageHandler->setNextAnswer($answer);

        $eventBus = new EventBus();

        $eventBus->utilize(new EventRouter([
            $answer->messageName() => [
                function (WorkflowMessage $answer) use ($process) {
                    $process->receiveMessage($answer, $this->workflowEngine);
                }
            ]
        ]))->utilize(new CallbackStrategy());

        $workflowEngine = new RegistryWorkflowEngine();

        $workflowEngine->registerEventBus($eventBus, [NodeName::defaultName()->toString()]);

        $this->workflowMessageHandler->useWorkflowEngine($workflowEngine);

        $process->perform($this->workflowEngine);

        $this->assertTrue($process->isFinished());

        $this->assertTrue($process->isSuccessfulDone());
    }

    /**
     * @test
     */
    public function it_marks_task_list_entry_as_failed_if_command_dispatch_fails()
    {
        $task = CollectData::from('test-case', UserDictionary::prototype());

        $process = LinearProcess::setUp(NodeName::defaultName(), [$task]);

        //We deactivate the router so message cannot be dispatched
        $this->workflowEngine->getCommandChannelFor('test-case')->deactivate($this->commandRouter);

        $process->perform($this->workflowEngine);

        $this->assertTrue($process->isFinished());

        $this->assertFalse($process->isSuccessfulDone());
    }

    /**
     * @test
     */
    public function it_performs_next_task_after_receiving_answer_for_previous_task()
    {
        $task1 = CollectData::from('test-case', UserDictionary::prototype());

        $task2 = ProcessData::address('test-target', ['Prooph\ProcessingTest\Mock\UserDictionary']);

        $process = LinearProcess::setUp(NodeName::defaultName(), [$task1, $task2]);

        $wfm = WorkflowMessage::collectDataOf(UserDictionary::prototype(), NodeName::defaultName(), 'test-case');

        $answer1 = $wfm->answerWith(UserDictionary::fromNativeValue([
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            ]
        ]));

        $this->workflowMessageHandler->setNextAnswer($answer1);

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 2);

        //Fake follow up task execution
        $processDataMessage = $answer1->prepareDataProcessing($taskListPosition, 'test-handler');

        //Remove TaskListPosition again
        $ref = new \ReflectionClass($processDataMessage);

        $taskListPositionProp = $ref->getProperty('processTaskListPosition');

        $taskListPositionProp->setAccessible(true);

        $taskListPositionProp->setValue($processDataMessage, null);

        $this->commandRouter->route($processDataMessage->messageName())->to($this->workflowMessageHandler);

        $answer2 = $processDataMessage->answerWithDataProcessingCompleted();

        $eventBus = new EventBus();

        $eventRouter = new EventRouter([
            $answer1->messageName() => [
                function (WorkflowMessage $answer) use ($process, $answer2) {
                    $this->workflowMessageHandler->setNextAnswer($answer2);
                    $process->receiveMessage($answer, $this->workflowEngine);
                }
            ],
            $answer2->messageName() => [
                function (WorkflowMessage $answer) use ($process) {
                    $process->receiveMessage($answer, $this->workflowEngine);
                }
            ]
        ]);

        $eventBus->utilize($eventRouter)->utilize(new CallbackStrategy());

        $workflowEngine = new RegistryWorkflowEngine();

        $workflowEngine->registerEventBus($eventBus, [NodeName::defaultName()->toString()]);

        $this->workflowMessageHandler->useWorkflowEngine($workflowEngine);

        $process->perform($this->workflowEngine);

        $this->assertTrue($process->isFinished());

        $this->assertTrue($process->isSuccessfulDone());
    }

    /**
     * @test
     */
    public function it_changes_type_class_if_target_does_not_allow_the_source_type()
    {
        $task = ProcessData::address('test-target', ['Prooph\ProcessingTest\Mock\TargetUserDictionary']);

        $process = LinearProcess::setUp(NodeName::defaultName(), [$task]);

        $wfm = WorkflowMessage::collectDataOf(UserDictionary::prototype(), 'test-case', NodeName::defaultName());

        $answer = $wfm->answerWith(UserDictionary::fromNativeValue([
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            ]
        ]));

        $this->commandRouter->route(
            MessageNameUtils::getProcessDataCommandName('Prooph\ProcessingTest\Mock\TargetUserDictionary')
        )->to($this->workflowMessageHandler);

        $process->perform($this->workflowEngine, $answer);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertEquals('Prooph\ProcessingTest\Mock\TargetUserDictionary', $receivedMessage->payload()->getTypeClass());
    }

    /**
     * @test
     */
    public function it_sets_wf_message_target_to_target_defined_in_the_process_task()
    {
        $task = ProcessData::address('test-target', ['Prooph\ProcessingTest\Mock\TargetUserDictionary']);

        $process = LinearProcess::setUp(NodeName::defaultName(), [$task]);

        $wfm = WorkflowMessage::collectDataOf(UserDictionary::prototype(), 'test-case', NodeName::defaultName());

        $answer = $wfm->answerWith(UserDictionary::fromNativeValue([
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            ]
        ]));

        $this->commandRouter->route(
            MessageNameUtils::getProcessDataCommandName('Prooph\ProcessingTest\Mock\TargetUserDictionary')
        )->to($this->workflowMessageHandler);

        $process->perform($this->workflowEngine, $answer);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertEquals('test-target', $receivedMessage->target());
    }
}
 