<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 21:30
 */

namespace GingerTest\Processor;

use Ginger\Message\LogMessage;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessId;
use Ginger\Processor\ProophPlugin\SingleTargetMessageRouter;
use Ginger\Processor\ProophPlugin\WorkflowProcessorInvokeStrategy;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\TestCase;
use Prooph\ServiceBus\EventBus;

/**
 * Class WorkflowProcessorTest
 *
 * @package GingerTest\Processor
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

        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $receivedMessage);

        $this->assertEquals('GingerTest\Mock\TargetUserDictionary', $receivedMessage->getPayload()->getTypeClass());

        $this->assertNotNull($this->lastPostCommitEvent);

        $recordedEvents = $this->lastPostCommitEvent->getRecordedEvents();

        $eventNames = [];

        foreach($recordedEvents as $recordedEvent) {
            $eventNames[] = $recordedEvent->eventName()->toString();
        }

        $expectedEventNames = ['Ginger\Processor\Event\ProcessSetUp', 'Ginger\Processor\Task\Event\TaskEntryMarkedAsRunning'];

        $this->assertEquals($expectedEventNames, $eventNames);
    }

    /**
     * @test
     */
    public function it_continues_process_when_receiving_answer_message_from_workflow_message_handler()
    {
        $wfMessage = $this->getUserDataCollectedTestMessage();

        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $answer = $receivedMessage->answerWithDataProcessingCompleted();

        $this->getTestWorkflowProcessor()->receiveMessage($answer);

        $this->assertNotNull($this->lastPostCommitEvent);

        $recordedEvents = $this->lastPostCommitEvent->getRecordedEvents();

        $eventNames = [];

        foreach($recordedEvents as $recordedEvent) {
            $eventNames[] = $recordedEvent->eventName()->toString();
        }

        $expectedEventNames = ['Ginger\Processor\Task\Event\TaskEntryMarkedAsDone'];

        $this->assertEquals($expectedEventNames, $eventNames);
    }

    /**
     * @test
     */
    public function it_passes_log_message_to_running_process_using_an_event_bus_with_processor_utilities()
    {
        $wfMessage = $this->getUserDataCollectedTestMessage();

        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $receivedMessage = $this->workflowMessageHandler->lastWorkflowMessage();

        $logMessage = LogMessage::logInfoDataProcessingStarted($receivedMessage->getProcessTaskListPosition());

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
            $eventNames[] = $recordedEvent->eventName()->toString();
        }

        $expectedEventNames = ['Ginger\Processor\Task\Event\LogMessageReceived'];

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

        $this->workflowMessageHandler->useEventBus($eventBus);

        $nextAnswer = $wfMessage->prepareDataProcessing(
            TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1)
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
            $eventNames[] = $recordedEvent->eventName()->toString();
        }

        $expectedEventNames = ['Ginger\Processor\Task\Event\TaskEntryMarkedAsDone'];

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
         * Change type to scenario 2 type, so that @see \GingerTest\TestCase::getTestProcessFactory
         * set up the right process
         */
        $wfMessage->changeGingerType('GingerTest\Mock\UserDictionaryS2');

        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $receivedMessage = $this->otherMachineWorkflowMessageHandler->lastWorkflowMessage();

        $this->assertNotNull($receivedMessage);

        $logMessage = LogMessage::logInfoDataProcessingStarted($receivedMessage->getProcessTaskListPosition());

        $this->getOtherMachineWorkflowProcessor()->receiveMessage($logMessage);

        $answer = $receivedMessage->answerWithDataProcessingCompleted();

        $this->getOtherMachineWorkflowProcessor()->receiveMessage($answer);

        $this->assertNotNull($this->lastPostCommitEvent);

        $expectedEventNamesOnLocalhost = [
            'Ginger\Processor\Event\ProcessSetUp',
            'Ginger\Processor\Task\Event\TaskEntryMarkedAsRunning',
            'Ginger\Processor\Task\Event\LogMessageReceived',
            'Ginger\Processor\Task\Event\TaskEntryMarkedAsDone'
        ];

        $expectedEventNamesOnOtherMachine = [
            'Ginger\Processor\Event\ProcessSetUp',
            'Ginger\Processor\Task\Event\TaskEntryMarkedAsRunning',
            'Ginger\Processor\Task\Event\LogMessageReceived',
            'Ginger\Processor\Task\Event\TaskEntryMarkedAsDone',
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
         * Change type to scenario 2 type, so that @see \GingerTest\TestCase::getTestProcessFactory
         * set up the right process
         */
        $wfMessage->changeGingerType('GingerTest\Mock\UserDictionaryS2');

        $this->getTestWorkflowProcessor()->receiveMessage($wfMessage);

        $receivedMessage = $this->otherMachineWorkflowMessageHandler->lastWorkflowMessage();

        $this->assertNotNull($receivedMessage);

        $error = LogMessage::logErrorMsg("Simulated error", $receivedMessage->getProcessTaskListPosition());

        $this->getOtherMachineWorkflowProcessor()->receiveMessage($error);

        $this->assertNotNull($this->lastPostCommitEvent);

        $expectedEventNamesLocalhost = [
            'Ginger\Processor\Event\ProcessSetUp',
            'Ginger\Processor\Task\Event\TaskEntryMarkedAsRunning',
            'Ginger\Processor\Task\Event\LogMessageReceived',
            'Ginger\Processor\Task\Event\TaskEntryMarkedAsFailed'
        ];

        $expectedEventNamesOtherMachine = [
            'Ginger\Processor\Event\ProcessSetUp',
            'Ginger\Processor\Task\Event\TaskEntryMarkedAsRunning',
            'Ginger\Processor\Task\Event\LogMessageReceived',
            'Ginger\Processor\Task\Event\TaskEntryMarkedAsFailed',
        ];

        $this->assertEquals($expectedEventNamesLocalhost, $this->eventNameLog);
        $this->assertEquals($expectedEventNamesOtherMachine, $this->otherMachineEventNameLog);
    }
}
 