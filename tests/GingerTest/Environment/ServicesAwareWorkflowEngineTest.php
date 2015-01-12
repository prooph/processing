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

        $commandBus = $env->getWorkflowEngine()->getCommandChannelFor(Definition::SERVICE_WORKFLOW_PROCESSOR);

        $this->assertInstanceOf('Prooph\ServiceBus\CommandBus', $commandBus);
    }

    /**
     * @test
     */
    public function it_derives_local_command_bus_when_target_is_null()
    {
        $env = Environment::setUp();

        $commandBus = $env->getWorkflowEngine()->getCommandChannelFor(Definition::SERVICE_WORKFLOW_PROCESSOR);
        $localBus = $env->getWorkflowEngine()->getCommandChannelFor(null);

        $this->assertSame($commandBus, $localBus);
    }

    /**
     * @test
     */
    public function it_derives_event_bus_for_target_from_services()
    {
        $env = Environment::setUp();

        $eventBus = $env->getWorkflowEngine()->getEventChannelFor(Definition::SERVICE_WORKFLOW_PROCESSOR);

        $this->assertInstanceOf('Prooph\ServiceBus\EventBus', $eventBus);
    }

    /**
     * @test
     */
    public function it_derives_local_event_bus_when_target_is_null()
    {
        $env = Environment::setUp();

        $eventBus = $env->getWorkflowEngine()->getEventChannelFor(Definition::SERVICE_WORKFLOW_PROCESSOR);
        $localBus = $env->getWorkflowEngine()->getEventChannelFor(null);

        $this->assertSame($eventBus, $localBus);
    }

    /**
     * @test
     */
    public function it_lazy_attaches_plugin_to_configured_channel_of_each_target()
    {
        $env = Environment::setUp([
            "ginger" => [
                "channels" => [
                    "multi_target_channel" => [
                        "targets" => ["target1", "target2"],
                        "message_dispatcher" => "mocked_message_handler"
                    ]
                ]
            ]
        ]);

        $plugin = new SimpleBusPlugin();

        //Preload channels to check if plugin is added to them
        $env->getWorkflowEngine()->getCommandChannelFor("target1");
        $env->getWorkflowEngine()->getEventChannelFor("target1");

        $env->getWorkflowEngine()->attachPluginToAllChannels($plugin);

        $this->assertEquals(2, $plugin->getAttachCount());

        //Request channels again to check that plugin is not attached twice
        $env->getWorkflowEngine()->getCommandChannelFor("target1");
        $env->getWorkflowEngine()->getEventChannelFor("target1");

        $this->assertEquals(2, $plugin->getAttachCount());

        //Load next channels to check if plugin is attached to them, too
        $env->getWorkflowEngine()->getCommandChannelFor("target2");
        $env->getWorkflowEngine()->getEventChannelFor("target2");


        $this->assertEquals(4, $plugin->getAttachCount());
    }
}
 