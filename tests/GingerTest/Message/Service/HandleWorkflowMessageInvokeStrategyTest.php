<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.07.14 - 18:56
 */

namespace GingerTest\Message\Service;

use Ginger\Message\Service\HandleWorkflowMessageInvokeStrategy;
use Ginger\Message\WorkflowMessage;
use GingerTest\TestCase;
use GingerTest\Type\Mock\TestWorkflowMessageHandler;
use GingerTest\Type\Mock\UserDictionary;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Router\CommandRouter;
use Prooph\ServiceBus\Router\EventRouter;

/**
 * Class HandleWorkflowMessageInvokeStrategyTest
 *
 * @package GingerTest\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class HandleWorkflowMessageInvokeStrategyTest extends TestCase
{
    /**
     * @var TestWorkflowMessageHandler
     */
    private $testWorkflowMessageHandler;

    protected function setUp()
    {
        $this->testWorkflowMessageHandler = new TestWorkflowMessageHandler();
    }

    /**
     * @test
     */
    public function it_invokes_ginger_command_on_workflow_message_handler()
    {
        $wfCommand = WorkflowMessage::collectDataOf(UserDictionary::prototype());

        $commandBus = new CommandBus();

        $commandRouter = new CommandRouter();

        $commandRouter->route($wfCommand->getMessageName())->to($this->testWorkflowMessageHandler);

        $commandBus->utilize($commandRouter);

        $commandBus->utilize(new HandleWorkflowMessageInvokeStrategy());

        $commandBus->dispatch($wfCommand);

        $this->assertSame($wfCommand, $this->testWorkflowMessageHandler->lastWorkflowMessage());
    }

    /**
     * @test
     */
    public function it_invokes_ginger_event_on_workflow_message_handler()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $wfEvent = WorkflowMessage::newDataCollected($user);

        $eventBus = new EventBus();

        $eventRouter = new EventRouter();

        $eventRouter->route($wfEvent->getMessageName())->to($this->testWorkflowMessageHandler);

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new HandleWorkflowMessageInvokeStrategy());

        $eventBus->dispatch($wfEvent);

        $this->assertSame($wfEvent, $this->testWorkflowMessageHandler->lastWorkflowMessage());
    }
}
 