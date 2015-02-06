<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 21:07
 */

namespace GingerTest\Message\Service;

use Ginger\Message\LogMessage;
use Ginger\Message\ProophPlugin\FromGingerMessageTranslator;
use Ginger\Message\ProophPlugin\ToGingerMessageTranslator;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Command\StartSubProcess;
use Ginger\Processor\Event\SubProcessFinished;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\TestCase;
use GingerTest\Mock\UserDictionary;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\InvokeStrategy\ForwardToMessageDispatcherStrategy;
use Prooph\ServiceBus\Message\InMemoryMessageDispatcher;
use Prooph\ServiceBus\Router\CommandRouter;
use Prooph\ServiceBus\Router\EventRouter;

/**
 * Class ServiceBusGingerIntegrationTest
 *
 * @package GingerTest\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ServiceBusGingerIntegrationTest extends TestCase
{
    private $receivedMessage;

    /**
     * @var InMemoryMessageDispatcher
     */
    private $messageDispatcher;

    protected function setUp()
    {
        parent::setUp();

        $eventBus = new EventBus();

        $commandBus = new CommandBus();

        $this->messageDispatcher = new InMemoryMessageDispatcher($commandBus, $eventBus);

        $commandRouter = new CommandRouter();

        $commandRouter->route(StartSubProcess::MSG_NAME)->to(function (StartSubProcess $command) {
            $this->receivedMessage = $command;
        });

        $commandBus->utilize($commandRouter);

        $commandBus->utilize(new ToGingerMessageTranslator());

        $commandBus->utilize(new CallbackStrategy());

        $eventRouter = new EventRouter();

        $eventRouter->route('ginger-message-gingertestmockuserdictionary-data-collected')
            ->to(function (WorkflowMessage $workflowMessage) {
                $this->receivedMessage = $workflowMessage;
            });

        $eventRouter->route('ginger-log-message')
            ->to(function (LogMessage $logMessage) {
                $this->receivedMessage = $logMessage;
            });

        $eventRouter->route(SubProcessFinished::MSG_NAME)->to(function (SubProcessFinished $event) {
            $this->receivedMessage = $event;
        });

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ToGingerMessageTranslator());

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

        $eventRouter->route($wfMessage->getMessageName())->to($this->messageDispatcher);

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ForwardToMessageDispatcherStrategy(new FromGingerMessageTranslator()));

        $eventBus->dispatch($wfMessage);

        /** @var $receivedMessage WorkflowMessage */
        $receivedMessage = $this->receivedMessage;

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $receivedMessage);
        $this->assertTrue($taskListPosition->equals($receivedMessage->processTaskListPosition()));
        $this->assertTrue($wfMessage->uuid()->equals($receivedMessage->uuid()));
        $this->assertEquals($wfMessage->payload()->extractTypeData(), $receivedMessage->payload()->extractTypeData());
        $this->assertEquals($wfMessage->version(), $receivedMessage->version());
        $this->assertEquals($wfMessage->createdOn()->format('Y-m-d H:i:s'), $receivedMessage->createdOn()->format('Y-m-d H:i:s'));
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

        $eventRouter->route($logMessage->getMessageName())->to($this->messageDispatcher);

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ForwardToMessageDispatcherStrategy(new FromGingerMessageTranslator()));

        $eventBus->dispatch($logMessage);

        /** @var $receivedMessage LogMessage */
        $receivedMessage = $this->receivedMessage;

        $this->assertInstanceOf('Ginger\Message\LogMessage', $receivedMessage);
        $this->assertTrue($taskListPosition->equals($receivedMessage->processTaskListPosition()));
        $this->assertTrue($logMessage->uuid()->equals($receivedMessage->uuid()));
        $this->assertEquals($logMessage->technicalMsg(), $receivedMessage->technicalMsg());
        $this->assertEquals($logMessage->createdOn()->format('Y-m-d H:i:s'), $receivedMessage->createdOn()->format('Y-m-d H:i:s'));
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

        $commandBus->utilize(new ForwardToMessageDispatcherStrategy(new FromGingerMessageTranslator()));

        $commandBus->dispatch($startSupProcess);

        /** @var $receivedMessage StartSubProcess */
        $receivedMessage = $this->receivedMessage;

        $this->assertInstanceOf(get_class($startSupProcess), $receivedMessage);
        $this->assertTrue($taskListPosition->equals($receivedMessage->parentTaskListPosition()));
        $this->assertTrue($startSupProcess->uuid()->equals($receivedMessage->uuid()));
        $this->assertEquals($startSupProcess->payload(), $receivedMessage->payload());
        $this->assertEquals($startSupProcess->createdOn()->format('Y-m-d H:i:s'), $receivedMessage->createdOn()->format('Y-m-d H:i:s'));
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

        $eventBus->utilize(new ForwardToMessageDispatcherStrategy(new FromGingerMessageTranslator()));

        $eventBus->dispatch($subProcessFinished);

        /** @var $receivedMessage SubProcessFinished */
        $receivedMessage = $this->receivedMessage;

        $this->assertInstanceOf(get_class($subProcessFinished), $receivedMessage);
        $this->assertTrue($taskListPosition->taskListId()->processId()->equals($receivedMessage->subProcessId()));
        $this->assertTrue($taskListPosition->equals($receivedMessage->parentTaskListPosition()));
        $this->assertTrue($subProcessFinished->uuid()->equals($receivedMessage->uuid()));
        $this->assertTrue($logMessage->uuid()->equals($receivedMessage->lastMessage()->uuid()));
        $this->assertEquals($logMessage->technicalMsg(), $receivedMessage->lastMessage()->technicalMsg());
        $this->assertEquals($subProcessFinished->occurredOn()->format('Y-m-d H:i:s'), $receivedMessage->occurredOn()->format('Y-m-d H:i:s'));
    }
}
 