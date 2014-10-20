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
use Ginger\Processor\ProophPlugin\WorkflowEventRouter;
use Ginger\Processor\ProophPlugin\WorkflowProcessorInvokeStrategy;
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

        $eventBus->utilize(new WorkflowEventRouter($this->getTestWorkflowProcessor()));

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

        $eventBus->utilize(new WorkflowEventRouter($this->getTestWorkflowProcessor()));

        $eventBus->utilize(new WorkflowProcessorInvokeStrategy());

        $this->workflowMessageHandler->useEventBus($eventBus);

        $this->workflowMessageHandler->setNextAnswer($wfMessage->prepareDataProcessing()->answerWithDataProcessingCompleted());

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
}
 