<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 05.10.14 - 14:38
 */

namespace GingerTest\Processor;


use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\LinearProcess;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\CollectData;
use Ginger\Processor\Task\ProcessData;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\Router\EventRouter;

/**
 * Class LinearMessagingProcessTest
 *
 * @package GingerTest\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class LinearProcessTest extends TestCase
{
    /**
     * @test
     */
    public function it_performs_collect_data_as_first_task_if_no_initial_wfm_is_given()
    {
        $task = CollectData::from('test-case', UserDictionary::prototype());

        $process = LinearProcess::setUp(NodeName::defaultName(), [$task]);

        $process->perform($this->workflowEngine);

        $collectDataMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $collectDataMessage);

        $this->assertEquals('GingerTest\Mock\UserDictionary', $collectDataMessage->getPayload()->getTypeClass());

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

        $wfm = WorkflowMessage::collectDataOf(UserDictionary::prototype());

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
            $answer->getMessageName() => [
                function (WorkflowMessage $answer) use ($process) {
                    $process->receiveMessage($answer, $this->workflowEngine);
                }
            ]
        ]))->utilize(new CallbackStrategy());

        $this->workflowMessageHandler->useEventBus($eventBus);

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
        $this->workflowEngine->getCommandBusFor('test-case')->deactivate($this->commandRouter);

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

        $task2 = ProcessData::address('test-target', ['GingerTest\Mock\UserDictionary']);

        $process = LinearProcess::setUp(NodeName::defaultName(), [$task1, $task2]);

        $wfm = WorkflowMessage::collectDataOf(UserDictionary::prototype());

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
        $processDataMessage = $answer1->prepareDataProcessing($taskListPosition);

        //Remove TaskListPosition again
        $ref = new \ReflectionClass($processDataMessage);

        $taskListPositionProp = $ref->getProperty('processTaskListPosition');

        $taskListPositionProp->setAccessible(true);

        $taskListPositionProp->setValue($processDataMessage, null);

        $this->commandRouter->route($processDataMessage->getMessageName())->to($this->workflowMessageHandler);

        $answer2 = $processDataMessage->answerWithDataProcessingCompleted();

        $eventBus = new EventBus();

        $eventRouter = new EventRouter([
            $answer1->getMessageName() => [
                function (WorkflowMessage $answer) use ($process, $answer2) {
                    $this->workflowMessageHandler->setNextAnswer($answer2);
                    $process->receiveMessage($answer, $this->workflowEngine);
                }
            ],
            $answer2->getMessageName() => [
                function (WorkflowMessage $answer) use ($process) {
                    $process->receiveMessage($answer, $this->workflowEngine);
                }
            ]
        ]);

        $eventBus->utilize($eventRouter)->utilize(new CallbackStrategy());

        $this->workflowMessageHandler->useEventBus($eventBus);

        $process->perform($this->workflowEngine);

        $this->assertTrue($process->isFinished());

        $this->assertTrue($process->isSuccessfulDone());
    }

    /**
     * @test
     */
    public function it_changes_type_class_if_target_does_not_allow_the_source_type()
    {
        $task = ProcessData::address('test-target', ['GingerTest\Mock\TargetUserDictionary']);

        $process = LinearProcess::setUp(NodeName::defaultName(), [$task]);

        $wfm = WorkflowMessage::collectDataOf(UserDictionary::prototype());

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
            MessageNameUtils::getProcessDataCommandName('GingerTest\Mock\TargetUserDictionary')
        )->to($this->workflowMessageHandler);

        $process->perform($this->workflowEngine, $answer);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertEquals('GingerTest\Mock\TargetUserDictionary', $receivedMessage->getPayload()->getTypeClass());
    }
}
 