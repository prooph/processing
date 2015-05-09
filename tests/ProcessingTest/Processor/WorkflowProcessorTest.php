<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 21:30
 */

namespace Prooph\ProcessingTest\Processor;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\ProophPlugin\SingleTargetMessageRouter;
use Prooph\Processing\Processor\ProophPlugin\WorkflowProcessorInvokeStrategy;
use Prooph\Processing\Processor\RegistryWorkflowEngine;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\ProcessingTest\TestCase;
use Prooph\ServiceBus\EventBus;
use Zend\EventManager\Event;

/**
 * Class WorkflowProcessorTest
 *
 * @package Prooph\ProcessingTest\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowProcessorTest extends TestCase
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
    public function it_starts_a_new_process_when_it_receives_a_data_collected_message_that_is_not_connected_to_a_task_list_position()
    {
        $wfMessage = $this->getUserDataCollectedTestMessage();

        $processStartedByMessageId    = null;
        $processStartedByMessageName  = null;
        $startedProcessId             = null;

        $this->getTestWorkflowProcessor()->events()->attach(
            "process_was_started_by_message",
            function (Event $e) use (&$processStartedByMessageId, &$processStartedByMessageName, &$startedProcessId) {
                $processStartedByMessageId = $e->getParam("message_id");
                $processStartedByMessageName = $e->getParam("message_name");
                $startedProcessId = $e->getParam("process_id");

            }
        );



        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertInstanceOf('Prooph\Processing\Message\WorkflowMessage', $receivedMessage);

        $this->assertEquals('Prooph\ProcessingTest\Mock\TargetUserDictionary', $receivedMessage->payload()->getTypeClass());

        $this->assertNotNull($this->lastPostCommitEvent);

        $recordedEvents = $this->lastPostCommitEvent->getRecordedEvents();

        $eventNames = [];

        $recordedProcessId = null;

        foreach($recordedEvents as $recordedEvent) {
            $eventNames[] = $recordedEvent->messageName();

            if ($recordedEvent->messageName() == 'Prooph\Processing\Processor\Event\ProcessWasSetUp') {
                $recordedProcessId = $recordedEvent->metadata()['aggregate_id'];
            }
        }

        $expectedEventNames = ['Prooph\Processing\Processor\Event\ProcessWasSetUp', 'Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsRunning'];

        $this->assertEquals($expectedEventNames, $eventNames);
        $this->assertEquals($wfMessage->uuid()->toString(), $processStartedByMessageId);
        $this->assertEquals($wfMessage->messageName(), $processStartedByMessageName);
        $this->assertNotNull($startedProcessId);
        $this->assertEquals($recordedProcessId, $startedProcessId);
    }

    /**
     * @test
     */
    public function it_continues_process_when_receiving_answer_message_from_workflow_message_handler()
    {
        $processDidFinishId           = null;
        $processDidFinishAt           = null;
        $processDidSuccessfullyFinish = null;

        $this->getTestWorkflowProcessor()->events()->attach(
            "process_did_finish",
            function (Event $e) use (&$processDidFinishId, &$processDidFinishAt, &$processDidSuccessfullyFinish) {
                $processDidFinishId = $e->getParam('process_id');
                $processDidFinishAt = $e->getParam('finished_at');
                $processDidSuccessfullyFinish = $e->getParam('succeed');
            }
        );

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $answer = $receivedMessage->answerWithDataProcessingCompleted();

        $this->getTestWorkflowProcessor()->receiveMessage($answer);

        $this->assertNotNull($this->lastPostCommitEvent);

        $recordedEvents = $this->lastPostCommitEvent->getRecordedEvents();

        $eventNames = [];
        $recordedProcessId = null;

        foreach($recordedEvents as $recordedEvent) {
            $eventNames[] = $recordedEvent->messageName();

            if ($recordedEvent->messageName() == 'Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsDone') {
                $recordedProcessId = $recordedEvent->metadata()['aggregate_id'];
            }
        }

        $expectedEventNames = ['Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsDone'];

        $this->assertEquals($expectedEventNames, $eventNames);
        $this->assertNotNull($processDidFinishId);
        $this->assertEquals($recordedProcessId, $processDidFinishId);
        $this->assertEquals($wfMessage->createdAt()->format(\DateTime::ISO8601), $processDidFinishAt);
        $this->assertTrue($processDidSuccessfullyFinish);
    }

    /**
     * @test
     */
    public function it_passes_log_message_to_running_process_using_an_event_bus_with_processor_utilities()
    {
        $wfMessage = $this->getUserDataCollectedTestMessage();

        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $logMessage = LogMessage::logInfoDataProcessingStarted($receivedMessage);

        //Set up EventBus
        $eventBus = new EventBus();

        $eventBus->utilize(new SingleTargetMessageRouter($this->getTestWorkflowProcessor()));

        $eventBus->utilize(new WorkflowProcessorInvokeStrategy());

        //Publish LogMessage
        $eventBus->dispatch($logMessage);

        $this->assertNotNull($this->lastPostCommitEvent);

        $recordedEvents = $this->lastPostCommitEvent->getRecordedEvents();

        $eventNames = [];

        foreach($recordedEvents as $recordedEvent) {
            $eventNames[] = $recordedEvent->messageName();
        }

        $expectedEventNames = ['Prooph\Processing\Processor\Task\Event\LogMessageReceived'];

        $this->assertEquals($expectedEventNames, $eventNames);
    }

    /**
     * @test
     */
    public function it_queues_incoming_messages_during_active_transaction_to_avoid_nested_transactions()
    {
        $wfMessage = $this->getUserDataCollectedTestMessage();

        $eventBus = new EventBus();

        $eventBus->utilize(new SingleTargetMessageRouter($this->getTestWorkflowProcessor()));

        $eventBus->utilize(new WorkflowProcessorInvokeStrategy());

        $workflowEngine = new RegistryWorkflowEngine();

        $workflowEngine->registerEventBus($eventBus, [NodeName::defaultName()->toString()]);

        $this->workflowMessageHandler->useWorkflowEngine($workflowEngine);

        $nextAnswer = $wfMessage->prepareDataProcessing(
            TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1),
            NodeName::defaultName()
        )->answerWithDataProcessingCompleted();

        $ref = new \ReflectionClass($nextAnswer);

        $refProp = $ref->getProperty('processTaskListPosition');

        $refProp->setAccessible(true);

        $refProp->setValue($nextAnswer, null);

        $this->workflowMessageHandler->setNextAnswer($nextAnswer);

        //Without queueing incoming messages an exception will be thrown, cause the WorkflowMessageHandler answers
        //during active transaction and the WorkflowProcessor would try to load the not yet persisted process.
        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $this->assertNotNull($this->lastPostCommitEvent);

        $recordedEvents = $this->lastPostCommitEvent->getRecordedEvents();

        $eventNames = [];

        foreach($recordedEvents as $recordedEvent) {
            $eventNames[] = $recordedEvent->messageName();
        }

        $expectedEventNames = ['Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsDone'];

        $this->assertEquals($expectedEventNames, $eventNames);
    }

    /**
     * @test
     */
    public function it_continues_parent_process_when_sub_process_is_finished()
    {
        $this->setUpOtherMachine();

        $wfMessage = $this->getUserDataCollectedTestMessage();

        /**
         * Change type to scenario 2 type, so that @see \Prooph\ProcessingTest\TestCase::getTestProcessFactory
         * set up the right process
         */
        $wfMessage->changeProcessingType('Prooph\ProcessingTest\Mock\UserDictionaryS2');

        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $receivedMessage = $this->otherMachineWorkflowMessageHandler->lastWorkflowMessage();

        $this->assertNotNull($receivedMessage);

        $logMessage = LogMessage::logInfoDataProcessingStarted($receivedMessage);

        $this->getOtherMachineWorkflowProcessor()->receiveMessage($logMessage);

        $answer = $receivedMessage->answerWithDataProcessingCompleted();

        $this->getOtherMachineWorkflowProcessor()->receiveMessage($answer);

        $this->assertNotNull($this->lastPostCommitEvent);

        $expectedEventNamesOnLocalhost = [
            'Prooph\Processing\Processor\Event\ProcessWasSetUp',
            'Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsRunning',
            'Prooph\Processing\Processor\Task\Event\LogMessageReceived',
            'Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsDone'
        ];

        $expectedEventNamesOnOtherMachine = [
            'Prooph\Processing\Processor\Event\ProcessWasSetUp',
            'Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsRunning',
            'Prooph\Processing\Processor\Task\Event\LogMessageReceived',
            'Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsDone',
        ];

        $this->assertEquals($expectedEventNamesOnLocalhost, $this->eventNameLog);
        $this->assertEquals($expectedEventNamesOnOtherMachine, $this->otherMachineEventNameLog);
    }

    /**
     * @test
     */
    public function it_marks_task_of_parent_process_as_failed_when_sub_process_is_finished_with_error()
    {
        $this->setUpOtherMachine();

        $wfMessage = $this->getUserDataCollectedTestMessage();

        /**
         * Change type to scenario 2 type, so that @see \Prooph\ProcessingTest\TestCase::getTestProcessFactory
         * set up the right process
         */
        $wfMessage->changeProcessingType('Prooph\ProcessingTest\Mock\UserDictionaryS2');

        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $receivedMessage = $this->otherMachineWorkflowMessageHandler->lastWorkflowMessage();

        $this->assertNotNull($receivedMessage);

        $error = LogMessage::logErrorMsg("Simulated error", $receivedMessage);

        $this->getOtherMachineWorkflowProcessor()->receiveMessage($error);

        $this->assertNotNull($this->lastPostCommitEvent);

        $expectedEventNamesLocalhost = [
            'Prooph\Processing\Processor\Event\ProcessWasSetUp',
            'Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsRunning',
            'Prooph\Processing\Processor\Task\Event\LogMessageReceived',
            'Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsFailed'
        ];

        $expectedEventNamesOtherMachine = [
            'Prooph\Processing\Processor\Event\ProcessWasSetUp',
            'Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsRunning',
            'Prooph\Processing\Processor\Task\Event\LogMessageReceived',
            'Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsFailed',
        ];

        $this->assertEquals($expectedEventNamesLocalhost, $this->eventNameLog);
        $this->assertEquals($expectedEventNamesOtherMachine, $this->otherMachineEventNameLog);
    }
}
 