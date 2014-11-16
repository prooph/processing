<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.11.14 - 19:57
 */

namespace GingerTest\Environment;

use Ginger\Environment\Environment;
use Ginger\Processor\Definition;
use GingerTest\Mock\SimpleBusPlugin;
use GingerTest\TestCase;

/**
 * Class ServicesAwareWorkflowEngineTest
 *
 * @package GingerTest\Environment
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ServicesAwareWorkflowEngineTest extends TestCase
{
    /**
     * @test
     */
    public function it_derives_command_bus_for_target_from_services()
    {
        $env = Environment::setUp();

        $commandBus = $env->getWorkflowEngine()->getCommandBusFor(Definition::SERVICE_WORKFLOW_PROCESSOR);

        $this->assertInstanceOf('Prooph\ServiceBus\CommandBus', $commandBus);
    }

    /**
     * @test
     */
    public function it_derives_event_bus_for_target_from_services()
    {
        $env = Environment::setUp();

        $eventBus = $env->getWorkflowEngine()->getEventBusFor(Definition::SERVICE_WORKFLOW_PROCESSOR);

        $this->assertInstanceOf('Prooph\ServiceBus\EventBus', $eventBus);
    }

    /**
     * @test
     */
    public function it_attaches_plugin_to_configured_command_bus_of_each_target()
    {
        $env = Environment::setUp([
            "ginger" => [
                "buses" => [
                    "multi_target_command_bus" => [
                        "type" => Definition::ENV_CONFIG_TYPE_COMMAND_BUS,
                        "targets" => ["target1", "target2"],
                        "message_handler" => "mocked_message_handler"
                    ]
                ]
            ]
        ]);

        $plugin = new SimpleBusPlugin();

        $env->getWorkflowEngine()->attachPluginToAllCommandBuses($plugin);

        //It should be one time attached to the default workflow_processor_command_bus and two times for each target bus
        $this->assertEquals(3, $plugin->getAttachCount());
    }

    /**
     * @test
     */
    public function it_attaches_plugin_to_configured_event_bus_of_each_target()
    {
        $env = Environment::setUp([
            "ginger" => [
                "buses" => [
                    "multi_target_event_bus" => [
                        "type" => Definition::ENV_CONFIG_TYPE_EVENT_BUS,
                        "targets" => ["target1", "target2"],
                        "message_handler" => "mocked_message_handler"
                    ]
                ]
            ]
        ]);

        $plugin = new SimpleBusPlugin();

        $env->getWorkflowEngine()->attachPluginToAllEventBuses($plugin);

        //It should be one time attached to the default workflow_processor_event_bus and two times for each target bus
        $this->assertEquals(3, $plugin->getAttachCount());
    }
}
 