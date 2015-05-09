<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 21:07
 */

namespace Prooph\ProcessingTest\Message\Service;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\ProophPlugin\FromProcessingMessageTranslator;
use Prooph\Processing\Message\ProophPlugin\ToProcessingMessageTranslator;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Command\StartSubProcess;
use Prooph\Processing\Processor\Event\SubProcessFinished;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\ProcessingTest\TestCase;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\InvokeStrategy\ForwardToRemoteMessageDispatcherStrategy;
use Prooph\ServiceBus\Message\InMemoryRemoteMessageDispatcher;
use Prooph\ServiceBus\Router\CommandRouter;
use Prooph\ServiceBus\Router\EventRouter;

/**
 * Class ServiceBusProcessingIntegrationTest
 *
 * @package Prooph\ProcessingTest\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ServiceBusProcessingIntegrationTest extends TestCase
{
    private $receivedMessage;

    /**
     * @var InMemoryRemoteMessageDispatcher
     */
    private $messageDispatcher;

    protected function setUp()
    {
        parent::setUp();

        $eventBus = new EventBus();

        $commandBus = new CommandBus();

        $this->messageDispatcher = new InMemoryRemoteMessageDispatcher($commandBus, $eventBus);

        $commandRouter = new CommandRouter();

        $commandRouter->route(StartSubProcess::MSG_NAME)->to(function (StartSubProcess $command) {
            $this->receivedMessage = $command;
        });

        $commandBus->utilize($commandRouter);

        $commandBus->utilize(new ToProcessingMessageTranslator());

        $commandBus->utilize(new CallbackStrategy());

        $eventRouter = new EventRouter();

        $eventRouter->route('processing-message-proophprocessingtestmockuserdictionary-data-collected')
            ->to(function (WorkflowMessage $workflowMessage) {
                $this->receivedMessage = $workflowMessage;
            });

        $eventRouter->route('processing-log-message')
            ->to(function (LogMessage $logMessage) {
                $this->receivedMessage = $logMessage;
            });

        $eventRouter->route(SubProcessFinished::MSG_NAME)->to(function (SubProcessFinished $event) {
            $this->receivedMessage = $event;
        });

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ToProcessingMessageTranslator());

        $eventBus->utilize(new CallbackStrategy());
    }

    /**
     * @test
     */
    public function it_sends_workflow_message_via_message_dispatcher_to_a_handler()
    {
        $wfMessage = $this->getUserDataCollectedTestMessage();

        $wfMessage->addMetadata(array('metadata' => true));

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfMessage->connectToProcessTask($taskListPosition);

        $eventBus = new EventBus();

        $eventRouter = new EventRouter();

        $eventRouter->route($wfMessage->messageName())->to($this->messageDispatcher);

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ForwardToRemoteMessageDispatcherStrategy(new FromProcessingMessageTranslator()));

        $eventBus->dispatch($wfMessage);

        /** @var $receivedMessage WorkflowMessage */
        $receivedMessage = $this->receivedMessage;

        $this->assertInstanceOf('Prooph\Processing\Message\WorkflowMessage', $receivedMessage);
        $this->assertTrue($taskListPosition->equals($receivedMessage->processTaskListPosition()));
        $this->assertTrue($wfMessage->uuid()->equals($receivedMessage->uuid()));
        $this->assertEquals($wfMessage->payload()->extractTypeData(), $receivedMessage->payload()->extractTypeData());
        $this->assertEquals($wfMessage->version(), $receivedMessage->version());
        $this->assertEquals($wfMessage->createdAt()->format('Y-m-d H:i:s'), $receivedMessage->createdAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(array('metadata' => true), $receivedMessage->metadata());
    }

    /**
     * @test
     */
    public function it_sends_log_message_via_message_dispatcher_to_a_handler()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $wfMessage->connectToProcessTask($taskListPosition);

        $logMessage = LogMessage::logWarningMsg("Just a fake warning", $wfMessage);

        $eventBus = new EventBus();

        $eventRouter = new EventRouter();

        $eventRouter->route($logMessage->messageName())->to($this->messageDispatcher);

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ForwardToRemoteMessageDispatcherStrategy(new FromProcessingMessageTranslator()));

        $eventBus->dispatch($logMessage);

        /** @var $receivedMessage LogMessage */
        $receivedMessage = $this->receivedMessage;

        $this->assertInstanceOf('Prooph\Processing\Message\LogMessage', $receivedMessage);
        $this->assertTrue($taskListPosition->equals($receivedMessage->processTaskListPosition()));
        $this->assertTrue($logMessage->uuid()->equals($receivedMessage->uuid()));
        $this->assertEquals($logMessage->technicalMsg(), $receivedMessage->technicalMsg());
        $this->assertEquals($logMessage->createdAt()->format('Y-m-d H:i:s'), $receivedMessage->createdAt()->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function it_sends_a_start_sub_process_command_via_message_dispatcher_to_a_handler()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $startSupProcess = StartSubProcess::at($taskListPosition, ['process_type' => 'faked'], true, 'sub-processor');

        $commandBus = new CommandBus();

        $commandRouter = new CommandRouter();

        $commandRouter->route(StartSubProcess::MSG_NAME)->to($this->messageDispatcher);

        $commandBus->utilize($commandRouter);

        $commandBus->utilize(new ForwardToRemoteMessageDispatcherStrategy(new FromProcessingMessageTranslator()));

        $commandBus->dispatch($startSupProcess);

        /** @var $receivedMessage StartSubProcess */
        $receivedMessage = $this->receivedMessage;

        $this->assertInstanceOf(get_class($startSupProcess), $receivedMessage);
        $this->assertTrue($taskListPosition->equals($receivedMessage->parentTaskListPosition()));
        $this->assertTrue($startSupProcess->uuid()->equals($receivedMessage->uuid()));
        $this->assertEquals($startSupProcess->payload(), $receivedMessage->payload());
        $this->assertEquals($startSupProcess->createdAt()->format('Y-m-d H:i:s'), $receivedMessage->createdAt()->format('Y-m-d H:i:s'));
        $this->assertEquals($startSupProcess->target(), $receivedMessage->target());
    }

    /**
     * @test
     */
    public function it_sends_a_sub_process_finished_event_via_message_dispatcher_to_a_handler()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $wfMessage->connectToProcessTask($taskListPosition);

        $logMessage = LogMessage::logDebugMsg("Just a fake event", $wfMessage);

        $subProcessFinished = SubProcessFinished::record(
            NodeName::defaultName(),
            $taskListPosition->taskListId()->processId(),
            true,
            $logMessage,
            $taskListPosition
        );

        $eventBus = new EventBus();

        $eventRouter = new EventRouter();

        $eventRouter->route(SubProcessFinished::MSG_NAME)->to($this->messageDispatcher);

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ForwardToRemoteMessageDispatcherStrategy(new FromProcessingMessageTranslator()));

        $eventBus->dispatch($subProcessFinished);

        /** @var $receivedMessage SubProcessFinished */
        $receivedMessage = $this->receivedMessage;

        $this->assertInstanceOf(get_class($subProcessFinished), $receivedMessage);
        $this->assertTrue($taskListPosition->taskListId()->processId()->equals($receivedMessage->subProcessId()));
        $this->assertTrue($taskListPosition->equals($receivedMessage->parentTaskListPosition()));
        $this->assertTrue($subProcessFinished->uuid()->equals($receivedMessage->uuid()));
        $this->assertTrue($logMessage->uuid()->equals($receivedMessage->lastMessage()->uuid()));
        $this->assertEquals($logMessage->technicalMsg(), $receivedMessage->lastMessage()->technicalMsg());
        $this->assertEquals($subProcessFinished->createdAt()->format('Y-m-d H:i:s'), $receivedMessage->createdAt()->format('Y-m-d H:i:s'));
    }
}
 