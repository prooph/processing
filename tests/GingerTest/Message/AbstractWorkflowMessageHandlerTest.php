<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.01.15 - 20:18
 */

namespace GingerTest\Message;

use Ginger\Message\LogMessage;
use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\AbstractWorkflowEngine;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessId;
use Ginger\Processor\RegistryWorkflowEngine;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\Mock\AbstractWorkflowMessageHandlerMock;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\Router\EventRouter;

/**
 * Class AbstractWorkflowMessageHandlerTest
 *
 * @package GingerTest\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class AbstractWorkflowMessageHandlerTest extends TestCase
{
    /**
     * @var AbstractWorkflowMessageHandlerMock
     */
    protected $workflowMessageHandler;

    private $lastGingerMessage;

    protected function setUp()
    {
        $this->workflowMessageHandler = new AbstractWorkflowMessageHandlerMock();

        $eventBus = new EventBus();

        $eventRouter = new EventRouter();

        $eventRouter->route(MessageNameUtils::LOG_MESSAGE_NAME)->to(function(LogMessage $logMessage) {
            $this->lastGingerMessage = $logMessage;
        });

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new CallbackStrategy());

        $workflowEngine = new RegistryWorkflowEngine();

        $workflowEngine->registerEventBus($eventBus, [AbstractWorkflowEngine::LOCAL_CHANNEL, NodeName::defaultName()->toString()]);

        $this->workflowMessageHandler->useWorkflowEngine($workflowEngine);
    }

    protected function tearDown()
    {
        $this->workflowMessageHandler->reset();
        $this->lastGingerMessage = null;
    }

    /**
     * @test
     */
    public function it_handles_a_collect_data_message()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 2);

        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype(), 'test-case', NodeName::defaultName());

        $wfMessage->connectToProcessTask($taskListPosition);

        $this->workflowMessageHandler->handleWorkflowMessage($wfMessage);

        $this->assertSame($wfMessage, $this->workflowMessageHandler->lastCollectDataMessage());

        $this->assertInstanceOf('Ginger\Message\LogMessage', $this->lastGingerMessage);
    }

    /**
     * @test
     */
    public function it_handles_a_process_data_message()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 2);

        $wfMessage = WorkflowMessage::newDataCollected(UserDictionary::fromNativeValue([
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            ]
        ]), 'test-case', NodeName::defaultName());

        $wfMessage->connectToProcessTask($taskListPosition);

        $wfMessage = $wfMessage->prepareDataProcessing($taskListPosition, 'message-handler');

        $this->workflowMessageHandler->handleWorkflowMessage($wfMessage);

        $this->assertSame($wfMessage, $this->workflowMessageHandler->lastProcessDataMessage());

        $this->assertInstanceOf('Ginger\Message\LogMessage', $this->lastGingerMessage);
    }
}
 