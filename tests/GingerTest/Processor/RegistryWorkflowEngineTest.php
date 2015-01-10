<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 23:34
 */

namespace GingerTest\Processor;

use Ginger\Processor\Definition;
use Ginger\Processor\RegistryWorkflowEngine;
use GingerTest\Mock\SimpleBusPlugin;
use GingerTest\TestCase;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;

/**
 * Class RegistryWorkflowEngineTest
 *
 * @package GingerTest\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class RegistryWorkflowEngineTest extends TestCase
{
    /**
     * @test
     */
    public function it_provides_command_bus_for_target()
    {
        $commandBus = $this->getTestWorkflowEngine()->getCommandChannelFor('crm');

        $this->assertInstanceOf('Prooph\ServiceBus\CommandBus', $commandBus);
    }

    /**
     * @test
     */
    public function it_provides_event_bus_for_target()
    {
        $eventBus = $this->getTestWorkflowEngine()->getEventChannelFor('wawi');

        $this->assertInstanceOf('Prooph\ServiceBus\EventBus', $eventBus);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_register_second_command_bus_for_same_target()
    {
        $this->setExpectedException('\RuntimeException');

        $this->getTestWorkflowEngine()->registerCommandBus(new CommandBus(), ['crm']);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_register_second_event_bus_for_same_target()
    {
        $this->setExpectedException('\RuntimeException');

        $this->getTestWorkflowEngine()->registerEventBus(new EventBus(), ['crm']);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_no_command_bus_is_registered_for_target()
    {
        $this->setExpectedException('\RuntimeException');

        $this->getTestWorkflowEngine()->getCommandChannelFor('unknown');
    }

    /**
     * @test
     */
    public function it_throws_exception_if_no_event_bus_is_registered_for_target()
    {
        $this->setExpectedException('\RuntimeException');

        $this->getTestWorkflowEngine()->getEventChannelFor('unknown');
    }

    /**
     * @test
     */
    public function it_attaches_plugin_to_registered_command_buses()
    {
        $workflowEngine = $this->getTestWorkflowEngine();

        $commandBus = new CommandBus();

        $workflowEngine->registerCommandBus($commandBus, [Definition::SERVICE_WORKFLOW_PROCESSOR]);

        $plugin = new SimpleBusPlugin();

        $workflowEngine->attachPluginToAllChannels($plugin);

        //Plugin should be attached to the new command bus as well as to the already configured channel (command + event bus) of the test system
        $this->assertEquals(3, $plugin->getAttachCount());
    }

    /**
     * @test
     */
    public function it_attaches_plugin_to_registered_event_buses()
    {
        $workflowEngine = $this->getTestWorkflowEngine();

        $eventBus = new EventBus();

        $workflowEngine->registerEventBus($eventBus, [Definition::SERVICE_WORKFLOW_PROCESSOR]);

        $plugin = new SimpleBusPlugin();

        $workflowEngine->attachPluginToAllChannels($plugin);

        //Plugin should be attached to the new event bus as well as to the already configured channel (command + event bus) of the test system
        $this->assertEquals(3, $plugin->getAttachCount());
    }

    /**
     * @return RegistryWorkflowEngine
     */
    protected function getTestWorkflowEngine()
    {
        $workflowEngine = new RegistryWorkflowEngine();

        $commandBus = new CommandBus();

        $workflowEngine->registerCommandBus($commandBus, ['crm', 'online-shop']);

        $eventBus = new EventBus();

        $workflowEngine->registerEventBus($eventBus, ['crm', 'wawi']);

        return $workflowEngine;
    }
}
 