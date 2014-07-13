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
use Ginger\Message\Service\ServiceBusInvokeStrategyProvider;
use Ginger\Message\WorkflowMessage;
use GingerTest\Type\Mock\TestWorkflowMessageHandler;
use GingerTest\Type\Mock\UserDictionary;
use Prooph\ServiceBus\Service\Definition;
use Prooph\ServiceBus\Service\ServiceBusConfiguration;
use Prooph\ServiceBus\Service\ServiceBusManager;
use Prooph\ServiceBusTest\TestCase;

/**
 * Class HandleWorkflowMessageInvokeStrategyTest
 *
 * @package GingerTest\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class HandleWorkflowMessageInvokeStrategyTest extends TestCase
{
    /**
     * @var ServiceBusManager
     */
    private $serviceBusManager;

    /**
     * @var TestWorkflowMessageHandler
     */
    private $testWorkflowMessageHandler;

    protected function setUp()
    {
        $this->serviceBusManager = new ServiceBusManager(
            new ServiceBusConfiguration(array(
                Definition::COMMAND_MAP => array(
                    'ginger-message-gingertesttypemockuserdictionary-collect-data' => "test_workflow_message_handler"
                ),
                Definition::EVENT_MAP => array(
                    'ginger-message-gingertesttypemockuserdictionary-data-collected' => "test_workflow_message_handler"
                )
            ))
        );

        $this->serviceBusManager->events()->attach(new ServiceBusInvokeStrategyProvider());

        $this->testWorkflowMessageHandler = new TestWorkflowMessageHandler();

        $this->serviceBusManager->setService("test_workflow_message_handler", $this->testWorkflowMessageHandler);
    }

    /**
     * @test
     */
    public function it_invokes_ginger_command_on_workflow_message_handler()
    {
        $wfCommand = WorkflowMessage::collectDataOf(UserDictionary::prototype());

        $this->serviceBusManager->route($wfCommand);

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

        $this->serviceBusManager->route($wfEvent);

        $this->assertSame($wfEvent, $this->testWorkflowMessageHandler->lastWorkflowMessage());
    }
}
 