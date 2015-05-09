<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.07.14 - 18:56
 */

namespace Prooph\ProcessingTest\Message\Service;

use Prooph\Processing\Message\ProophPlugin\HandleWorkflowMessageInvokeStrategy;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\NodeName;
use Prooph\ProcessingTest\TestCase;
use Prooph\ProcessingTest\Mock\TestWorkflowMessageHandler;
use Prooph\ProcessingTest\Mock\UserDictionary;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Router\CommandRouter;
use Prooph\ServiceBus\Router\EventRouter;

/**
 * Class HandleWorkflowMessageInvokeStrategyTest
 *
 * @package Prooph\ProcessingTest\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class HandleWorkflowMessageInvokeStrategyTest extends TestCase
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
    public function it_invokes_processing_command_on_workflow_message_handler()
    {
        $wfCommand = WorkflowMessage::collectDataOf(UserDictionary::prototype(), 'test-case', NodeName::defaultName());

        $commandBus = new CommandBus();

        $commandRouter = new CommandRouter();

        $commandRouter->route($wfCommand->messageName())->to($this->workflowMessageHandler);

        $commandBus->utilize($commandRouter);

        $commandBus->utilize(new HandleWorkflowMessageInvokeStrategy());

        $commandBus->dispatch($wfCommand);

        $this->assertSame($wfCommand, $this->workflowMessageHandler->lastWorkflowMessage());
    }

    /**
     * @test
     */
    public function it_invokes_processing_event_on_workflow_message_handler()
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

        $wfEvent = WorkflowMessage::newDataCollected($user, 'test-case', NodeName::defaultName());

        $eventBus = new EventBus();

        $eventRouter = new EventRouter();

        $eventRouter->route($wfEvent->messageName())->to($this->workflowMessageHandler);

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new HandleWorkflowMessageInvokeStrategy());

        $eventBus->dispatch($wfEvent);

        $this->assertSame($wfEvent, $this->workflowMessageHandler->lastWorkflowMessage());
    }
}
 