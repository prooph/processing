<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 19:32
 */

namespace Prooph\ProcessingTest\Environment;

use Prooph\Common\ServiceLocator\ServiceLocator;
use Prooph\Processing\Environment\Environment;
use Prooph\Processing\Processor\Definition;
use Prooph\ProcessingTest\Mock\SimpleEnvPlugin;
use Prooph\ProcessingTest\Mock\TestWorkflowMessageHandler;
use Prooph\ProcessingTest\TestCase;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Zend\ServiceManager\ServiceManager;

/**
 * Class EnvironmentTest
 *
 * @package Prooph\ProcessingTest\Environment
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
            'processing' => [
                'plugins' => [new SimpleEnvPlugin()]
            ]
        ]);

        $this->assertTrue($env->getPlugin('Prooph\ProcessingTest\Mock\SimpleEnvPlugin')->isRegistered());
    }

    /**
     * @test
     */
    public function it_can_be_set_up_with_a_list_of_plugin_aliases()
    {
        $env = Environment::setUp([
            'services' => [
                'invokables' => [
                    'simple_env_plugin' => 'Prooph\ProcessingTest\Mock\SimpleEnvPlugin'
                ]
            ],
            'processing' => [
                'plugins' => [
                    'simple_env_plugin'
                ]
            ]
        ]);

        $this->assertTrue($env->getPlugin('Prooph\ProcessingTest\Mock\SimpleEnvPlugin')->isRegistered());
    }

    /**
     * @test
     */
    public function it_can_be_set_up_with_a_service_manager()
    {
        $services = new ServiceManager();

        $env = Environment::setUp($services);

        $this->assertInstanceOf(ServiceLocator::class, $env->services());
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
        $this->assertTrue(isset($config["processing"]));
    }

    /**
     * @test
     */
    public function it_uses_default_node_name_when_nothing_else_is_configured()
    {
        $env = Environment::setUp();

        $nodeName = $env->getNodeName();

        $this->assertInstanceOf('Prooph\Processing\\Processor\NodeName', $nodeName);

        $this->assertEquals(Definition::DEFAULT_NODE_NAME, $nodeName->toString());
    }

    /**
     * @test
     */
    public function it_can_be_set_up_with_a_custom_node_name()
    {
        $env = Environment::setUp(['processing' => ['node_name' => 'CustomNode']]);

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
                    "allowed_types"  => ['Prooph\ProcessingTest\Mock\TargetUserDictionary', 'Prooph\ProcessingTest\Mock\AddressDictionary'],
                    "preferred_type" => 'Prooph\ProcessingTest\Mock\AddressDictionary'
                ]
            ]
        ];

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $env = Environment::setUp([
            'processing' => [
                'processes' => [
                    $wfMessage->messageName() => $processDefinition
                ]
            ]
        ]);

        $process = $env->services()->get(Definition::SERVICE_PROCESS_FACTORY)
            ->deriveProcessFromMessage($wfMessage, $env->getNodeName());

        $this->assertInstanceOf('Prooph\Processing\Processor\LinearProcess', $process);
    }

    /**
     * @test
     */
    public function it_adds_a_workflow_processor_buses_provider_to_services_so_every_workflow_message_handler_gets_the_buses_injected()
    {
        $env = Environment::setUp([
            'services' => [
                'invokables' => [
                    'test_workflow_message_handler' => 'Prooph\ProcessingTest\Mock\TestWorkflowMessageHandler'
                ]
            ]
        ]);

        /** @var $messageHandler TestWorkflowMessageHandler */
        $messageHandler = $env->services()->get('test_workflow_message_handler');

        $this->assertInstanceOf('Prooph\Processing\Processor\WorkflowEngine', $messageHandler->getWorkflowEngine());
    }

    /**
     * @test
     */
    public function it_returns_a_ready_to_use_process_factory()
    {
        $env = Environment::setUp();

        $this->assertInstanceOf('Prooph\Processing\Processor\ProcessFactory', $env->getProcessFactory());
    }

    /**
     * @test
     */
    public function it_returns_a_ready_to_use_process_repository()
    {
        $env = Environment::setUp();

        $this->assertInstanceOf('Prooph\Processing\Processor\ProcessRepository', $env->getProcessRepository());
    }

    /**
     * @test
     */
    public function it_returns_a_ready_to_use_workflow_processor()
    {
        $env = Environment::setUp();

        $this->assertInstanceOf('Prooph\Processing\Processor\WorkflowProcessor', $env->getWorkflowProcessor());
    }
}
 