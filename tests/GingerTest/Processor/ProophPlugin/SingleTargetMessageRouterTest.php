<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 15.11.14 - 20:08
 */

namespace GingerTest\Processor\ProophPlugin;

use Ginger\Message\WorkflowMessage;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProophPlugin\SingleTargetMessageRouter;
use GingerTest\Mock\TestWorkflowMessageHandler;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\InMemoryMessageDispatcher;
use Prooph\ServiceBus\Process\CommandDispatch;
use Prooph\ServiceBus\Process\EventDispatch;

/**
 * Class SingleTargetMessageRouterTest
 *
 * @package GingerTest\Processor\ProophPlugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SingleTargetMessageRouterTest extends TestCase
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
     * @dataProvider dataProviderTargetListeners
     */
    public function it_accepts_different_types_of_target_handlers($targetHandler)
    {
        new SingleTargetMessageRouter($targetHandler);
    }

    public function dataProviderTargetListeners()
    {
        parent::setUpLocalMachine();
        return [
            [$this->getTestWorkflowProcessor()],
            [new TestWorkflowMessageHandler()],
            [new InMemoryMessageDispatcher(new CommandBus(), new EventBus())],
            ["ginger.workflow_processor_alias"]
        ];
    }

    /**
     * @test
     */
    public function it_injects_target_handler_to_command_dispatch_when_command_is_a_workflow_message()
    {
        $message = WorkflowMessage::collectDataOf(
            UserDictionary::prototype(),
            'test-case',
            NodeName::defaultName()
        );

        $commandDispatch = new CommandDispatch();

        $commandDispatch->setCommand($message);

        $router = new SingleTargetMessageRouter($this->getTestWorkflowProcessor());

        $router->onRouteCommand($commandDispatch);

        $this->assertSame($this->getTestWorkflowProcessor(), $commandDispatch->getCommandHandler());
    }

    /**
     * @test
     */
    public function it_injects_target_handler_to_event_dispatch_when_event_is_a_workflow_message()
    {
        $message = WorkflowMessage::newDataCollected(UserDictionary::fromNativeValue([
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            ]
        ]), 'test-case', NodeName::defaultName());

        $eventDispatch = new EventDispatch();

        $eventDispatch->setEvent($message);

        $router = new SingleTargetMessageRouter($this->getTestWorkflowProcessor());

        $router->onRouteEvent($eventDispatch);

        $this->assertSame($this->getTestWorkflowProcessor(), $eventDispatch->getEventListeners()[0]);
    }
}
 