<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.01.15 - 20:18
 */

namespace Prooph\ProcessingTest\Message;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\AbstractWorkflowEngine;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\RegistryWorkflowEngine;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\ProcessingTest\Mock\AbstractWorkflowMessageHandlerMock;
use Prooph\ProcessingTest\Mock\UserDictionary;
use Prooph\ProcessingTest\TestCase;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\Router\EventRouter;

/**
 * Class AbstractWorkflowMessageHandlerTest
 *
 * @package Prooph\ProcessingTest\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class AbstractWorkflowMessageHandlerTest extends TestCase
{
    /**
     * @var AbstractWorkflowMessageHandlerMock
     */
    protected $workflowMessageHandler;

    private $lastProcessingMessage;

    protected function setUp()
    {
        $this->workflowMessageHandler = new AbstractWorkflowMessageHandlerMock();

        $eventBus = new EventBus();

        $eventRouter = new EventRouter();

        $eventRouter->route(MessageNameUtils::LOG_MESSAGE_NAME)->to(function(LogMessage $logMessage) {
            $this->lastProcessingMessage = $logMessage;
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
        $this->lastProcessingMessage = null;
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

        $this->assertInstanceOf('Prooph\Processing\Message\LogMessage', $this->lastProcessingMessage);
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

        $this->assertInstanceOf('Prooph\Processing\Message\LogMessage', $this->lastProcessingMessage);
    }
}
 