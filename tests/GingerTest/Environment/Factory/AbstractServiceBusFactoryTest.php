<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.11.14 - 01:05
 */

namespace GingerTest\Environment\Factory;

use Ginger\Environment\Environment;
use Ginger\Environment\Factory\AbstractServiceBusFactory;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Definition;
use GingerTest\Mock\SimpleBusPlugin;
use GingerTest\Mock\StupidMessageDispatcher;
use GingerTest\Mock\StupidWorkflowProcessorMock;
use GingerTest\Mock\TestWorkflowMessageHandler;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;
use Zend\ServiceManager\ServiceManager;

/**
 * Class AbstractServiceBusFactoryTest
 *
 * @package GingerTest\Environment\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AbstractServiceBusFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideBusAliases
     */
    public function it_can_create_a_bus_when_correct_alias_is_given($busAlias, $canCreate)
    {
        $factory = new AbstractServiceBusFactory();

        $this->assertSame($canCreate, $factory->canCreateServiceWithName(new ServiceManager(), $busAlias, $busAlias));
    }

    public function provideBusAliases()
    {
        return [
            ["ginger.command_bus.simple_target", true],
            ["ginger.event_bus.simple_target", true],
            ["ginger.command_bus.target.wih.dots", true],
            ["ginger.event_bus.target.wih.dots", true],
            ["ginger.unknown.service", false]
        ];
    }

    /**
     * @test
     */
    public function it_creates_a_command_bus_that_dispatches_a_message_to_a_workflow_processor()
    {
        $env = Environment::setUp();

        $processor = new StupidWorkflowProcessorMock();

        $env->services()->setAllowOverride(true);

        $env->services()->setService(Definition::SERVICE_WORKFLOW_PROCESSOR, $processor);

        $commandBus = $env->services()->get('ginger.command_bus.' . Definition::SERVICE_WORKFLOW_PROCESSOR);

        $this->assertInstanceOf('Prooph\ServiceBus\CommandBus', $commandBus);

        $message = WorkflowMessage::collectDataOf(UserDictionary::prototype());

        $commandBus->dispatch($message);

        $this->assertSame($message, $processor->getLastReceivedMessage());
    }

    /**
     * @test
     */
    public function it_creates_an_event_bus_that_dispatches_a_message_to_a_workflow_processor()
    {
        $env = Environment::setUp();

        $processor = new StupidWorkflowProcessorMock();

        $env->services()->setAllowOverride(true);

        $env->services()->setService(Definition::SERVICE_WORKFLOW_PROCESSOR, $processor);

        $eventBus = $env->services()->get('ginger.event_bus.' . Definition::SERVICE_WORKFLOW_PROCESSOR);

        $this->assertInstanceOf('Prooph\ServiceBus\EventBus', $eventBus);

        $message = WorkflowMessage::newDataCollected(UserDictionary::fromNativeValue([
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            ]
        ]));

        $eventBus->dispatch($message);

        $this->assertSame($message, $processor->getLastReceivedMessage());
    }

    /**
     * @test
     */
    public function it_creates_a_command_bus_and_attaches_configured_plugins_to_it()
    {
        $plugin1 = new SimpleBusPlugin();

        $plugin2 = new SimpleBusPlugin();

        $env = Environment::setUp([
            "ginger" => [
                "buses" => [
                    'workflow_processor_command_bus' => [
                        'utils' => ["bus_plugin_1", "bus_plugin_2"]
                    ]
                ]
            ]
        ]);

        $env->services()->setService("bus_plugin_1", $plugin1);
        $env->services()->setService("bus_plugin_2", $plugin2);

        $env->services()->get('ginger.command_bus.' . Definition::SERVICE_WORKFLOW_PROCESSOR);

        $this->assertTrue($plugin1->isRegistered());
        $this->assertTrue($plugin2->isRegistered());
    }

    /**
     * @test
     */
    public function it_creates_command_bus_that_dispatches_a_message_to_a_message_dispatcher()
    {
        $env = Environment::setUp([
            "ginger" => [
                "buses" => [
                    'stupid_dispatcher_command_bus' => [
                        'type' => "command_bus",
                        'targets' => ["remote_command_handler"],
                        "message_handler" => "stupid_message_dispatcher"
                    ]
                ]
            ]
        ]);

        $messageDispatcher = new StupidMessageDispatcher();

        $env->services()->setService("stupid_message_dispatcher", $messageDispatcher);

        $commandBus = $env->services()->get("ginger.command_bus.remote_command_handler");

        $message = WorkflowMessage::collectDataOf(UserDictionary::prototype());

        $commandBus->dispatch($message);

        $this->assertEquals($message->getMessageName(), $messageDispatcher->getLastReceivedMessage()->name());
    }

    /**
     * @test
     */
    public function it_creates_a_command_bus_that_dispatches_a_message_to_a_workflow_message_handler()
    {
        $env = Environment::setUp([
            "ginger" => [
                "buses" => [
                    'message_handler_command_bus' => [
                        'type' => "command_bus",
                        'targets' => ["test_command_handler"],
                        "message_handler" => "test_command_handler"
                    ]
                ]
            ]
        ]);

        $messageHandler = new TestWorkflowMessageHandler();

        $env->services()->setService('test_command_handler', $messageHandler);

        $commandBus = $env->services()->get("ginger.command_bus.test_command_handler");

        $message = WorkflowMessage::collectDataOf(UserDictionary::prototype());

        $commandBus->dispatch($message);

        $this->assertSame($message, $messageHandler->lastWorkflowMessage());
    }
}
 