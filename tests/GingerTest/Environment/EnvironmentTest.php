<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 19:32
 */

namespace GingerTest\Environment;

use Ginger\Environment\Environment;
use Ginger\Processor\Definition;
use GingerTest\Mock\SimpleEnvPlugin;
use GingerTest\Mock\TestWorkflowMessageHandler;
use GingerTest\TestCase;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Zend\ServiceManager\ServiceManager;

/**
 * Class EnvironmentTest
 *
 * @package GingerTest\Environment
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class EnvironmentTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_set_up_without_a_config()
    {
        $env = Environment::setUp();

        $this->assertInstanceOf('Prooph\EventStore\EventStore', $env->getEventStore());
    }

    /**
     * @test
     */
    public function it_can_be_set_up_with_custom_pes_config()
    {
        $env = Environment::setUp([
            'prooph.event_store' => [
                'adapter' => [
                    'type' => 'Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter',
                    'options' => [
                        'connection' => [
                            'driver' => 'Pdo_Sqlite',
                            'database' => ':memory:'
                        ],
                    ]
                ]
            ]
        ]);

        $this->assertInstanceOf('Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter', $env->getEventStore()->getAdapter());
    }

    /**
     * @test
     */
    public function it_can_be_set_up_with_a_list_of_plugins()
    {
        $env = Environment::setUp([
            'ginger' => [
                'plugins' => [new SimpleEnvPlugin()]
            ]
        ]);

        $this->assertTrue($env->getPlugin('GingerTest\Mock\SimpleEnvPlugin')->isRegistered());
    }

    /**
     * @test
     */
    public function it_can_be_set_up_with_a_list_of_plugin_aliases()
    {
        $env = Environment::setUp([
            'services' => [
                'invokables' => [
                    'simple_env_plugin' => 'GingerTest\Mock\SimpleEnvPlugin'
                ]
            ],
            'ginger' => [
                'plugins' => [
                    'simple_env_plugin'
                ]
            ]
        ]);

        $this->assertTrue($env->getPlugin('GingerTest\Mock\SimpleEnvPlugin')->isRegistered());
    }

    /**
     * @test
     */
    public function it_can_be_set_up_with_a_service_manager()
    {
        $services = new ServiceManager();

        $env = Environment::setUp($services);

        $this->assertSame($services, $env->services());
    }

    /**
     * @test
     */
    public function it_adds_workflow_services_to_service_manager()
    {
        $services = new ServiceManager();

        $env = Environment::setUp($services);

        $this->assertTrue($env->services()->has(Definition::SERVICE_ENVIRONMENT));
        $this->assertTrue($env->services()->has(Definition::SERVICE_WORKFLOW_PROCESSOR));
        $this->assertTrue($env->services()->has(Definition::SERVICE_PROCESS_FACTORY));
        $this->assertTrue($env->services()->has(Definition::SERVICE_PROCESS_REPOSITORY));
        $this->assertTrue($env->services()->has('configuration'));
        $this->assertTrue($env->services()->has('config'));
    }

    /**
     * @test
     */
    public function it_merges_global_config_with_default_env_config()
    {
        $services = new ServiceManager();

        $services->setService('configuration', ["global_config_key" => "value"]);

        $env = Environment::setUp($services);

        $config = $env->services()->get('configuration');

        $this->assertTrue(isset($config["global_config_key"]));
        $this->assertTrue(isset($config["ginger"]));
    }

    /**
     * @test
     */
    public function it_uses_default_node_name_when_nothing_else_is_configured()
    {
        $env = Environment::setUp();

        $nodeName = $env->getNodeName();

        $this->assertInstanceOf('Ginger\Processor\NodeName', $nodeName);

        $this->assertEquals(Definition::DEFAULT_NODE_NAME, $nodeName->toString());
    }

    /**
     * @test
     */
    public function it_can_be_set_up_with_a_custom_node_name()
    {
        $env = Environment::setUp(['ginger' => ['node_name' => 'CustomNode']]);

        $this->assertEquals('CustomNode', $env->getNodeName()->toString());
    }

    /**
     * @test
     */
    public function it_can_be_set_up_with_a_process_definition()
    {
        $processDefinition = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [
                [
                    "task_type"      => Definition::TASK_PROCESS_DATA,
                    "target"         => 'test-target',
                    "allowed_types"  => ['GingerTest\Mock\TargetUserDictionary', 'GingerTest\Mock\AddressDictionary'],
                    "preferred_type" => 'GingerTest\Mock\AddressDictionary'
                ]
            ]
        ];

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $env = Environment::setUp([
            'ginger' => [
                'processes' => [
                    $wfMessage->getMessageName() => $processDefinition
                ]
            ]
        ]);

        $process = $env->services()->get(Definition::SERVICE_PROCESS_FACTORY)
            ->deriveProcessFromMessage($wfMessage, $env->getNodeName());

        $this->assertInstanceOf('Ginger\Processor\LinearProcess', $process);
    }

    /**
     * @test
     */
    public function it_adds_a_workflow_processor_buses_provider_to_services_so_every_workflow_message_handler_gets_the_buses_injected()
    {
        $env = Environment::setUp();

        $env->services()->setInvokableClass('test_workflow_message_handler', 'GingerTest\Mock\TestWorkflowMessageHandler');

        /** @var $messageHandler TestWorkflowMessageHandler */
        $messageHandler = $env->services()->get('test_workflow_message_handler');

        $this->assertInstanceOf('Ginger\Processor\WorkflowEngine', $messageHandler->getWorkflowEngine());
    }

    /**
     * @test
     */
    public function it_returns_a_ready_to_use_process_factory()
    {
        $env = Environment::setUp();

        $this->assertInstanceOf('Ginger\Processor\ProcessFactory', $env->getProcessFactory());
    }

    /**
     * @test
     */
    public function it_returns_a_ready_to_use_process_repository()
    {
        $env = Environment::setUp();

        $this->assertInstanceOf('Ginger\Processor\ProcessRepository', $env->getProcessRepository());
    }

    /**
     * @test
     */
    public function it_returns_a_ready_to_use_workflow_processor()
    {
        $env = Environment::setUp();

        $this->assertInstanceOf('Ginger\Processor\WorkflowProcessor', $env->getWorkflowProcessor());
    }
}
 