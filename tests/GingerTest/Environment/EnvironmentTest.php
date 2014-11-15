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

        $process = $env->services()->get('ginger.process_factory')->deriveProcessFromMessage($wfMessage);

        $this->assertInstanceOf('Ginger\Processor\LinearMessagingProcess', $process);
    }
}
 