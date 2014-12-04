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

        $this->messageDispatcher = new InMemoryMessageDispatcher(new CommandBus(), $eventBus);

        $eventRouter = new EventRouter();

        $eventRouter->route('ginger-message-gingertestmockuserdictionary-data-collected')
            ->to(function (WorkflowMessage $workflowMessage) {
                $this->receivedMessage = $workflowMessage;
            });

        $eventRouter->route('ginger-log-message')
            ->to(function (LogMessage $logMessage) {
                $this->receivedMessage = $logMessage;
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

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfMessage->connectToProcessTask($taskListPosition);

        $eventBus = new EventBus();

        $eventRouter = new EventRouter();

        $eventRouter->route($wfMessage->getMessageName())->to($this->messageDispatcher);

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ForwardToMessageDispatcherStrategy(new FromGingerMessageTranslator()));

        $eventBus->dispatch($wfMessage);

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $this->receivedMessage);
        $this->assertTrue($taskListPosition->equals($this->receivedMessage->getProcessTaskListPosition()));
        $this->assertTrue($wfMessage->getUuid()->equals($this->receivedMessage->getUuid()));
        $this->assertEquals($wfMessage->getPayload()->getData(), $this->receivedMessage->getPayload()->getData());
        $this->assertEquals($wfMessage->getVersion(), $this->receivedMessage->getVersion());
        $this->assertEquals($wfMessage->getCreatedOn()->format('Y-m-d H:i:s'), $this->receivedMessage->getCreatedOn()->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function it_sends_log_message_via_message_dispatcher_to_a_handler()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $logMessage = LogMessage::logWarningMsg("Just a fake warning", $taskListPosition);

        $eventBus = new EventBus();

        $eventRouter = new EventRouter();

        $eventRouter->route($logMessage->getMessageName())->to($this->messageDispatcher);

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ForwardToMessageDispatcherStrategy(new FromGingerMessageTranslator()));

        $eventBus->dispatch($logMessage);

        $this->assertInstanceOf('Ginger\Message\LogMessage', $this->receivedMessage);
        $this->assertTrue($taskListPosition->equals($this->receivedMessage->getProcessTaskListPosition()));
        $this->assertTrue($logMessage->getUuid()->equals($this->receivedMessage->getUuid()));
        $this->assertEquals($logMessage->getTechnicalMsg(), $this->receivedMessage->getTechnicalMsg());
        $this->assertEquals($logMessage->getCreatedOn()->format('Y-m-d H:i:s'), $this->receivedMessage->getCreatedOn()->format('Y-m-d H:i:s'));
    }
}
 