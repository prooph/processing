<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.11.14 - 01:05
 */

namespace Prooph\ProcessingTest\Environment\Factory;

use Prooph\Processing\Environment\Environment;
use Prooph\Processing\Environment\Factory\AbstractChannelFactory;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Definition;
use Prooph\Processing\Processor\NodeName;
use Prooph\ProcessingTest\Mock\ChannelPlugin\TargetAndOriginChannelPlugin;
use Prooph\ProcessingTest\Mock\ChannelPlugin\TargetAndSenderChannelPlugin;
use Prooph\ProcessingTest\Mock\ChannelPlugin\TargetChannelPlugin;
use Prooph\ProcessingTest\Mock\ChannelPlugin\TargetOriginAndSenderChannelPlugin;
use Prooph\ProcessingTest\Mock\SimpleBusPlugin;
use Prooph\ProcessingTest\Mock\StupidMessageDispatcher;
use Prooph\ProcessingTest\Mock\StupidWorkflowProcessorMock;
use Prooph\ProcessingTest\Mock\TestWorkflowMessageHandler;
use Prooph\ProcessingTest\Mock\UserDictionary;
use Prooph\ProcessingTest\TestCase;
use Zend\ServiceManager\ServiceManager;

/**
 * Class AbstractChannelFactoryTest
 *
 * @package Prooph\ProcessingTest\Environment\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AbstractChannelFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideBusAliases
     */
    public function it_can_create_a_bus_when_correct_alias_is_given($busAlias, $canCreate)
    {
        $factory = new AbstractChannelFactory();

        $this->assertSame($canCreate, $factory->canCreateServiceWithName(new ServiceManager(), $busAlias, $busAlias));
    }

    public function provideBusAliases()
    {
        return [
            ["processing.command_bus.simple_target", true],
            ["processing.event_bus.simple_target", true],
            ["processing.command_bus.target.wih.dots", true],
            ["processing.event_bus.target.wih.dots", true],
            ["processing.unknown.service", false]
        ];
    }

    /**
     * @test
     */
    public function it_creates_a_command_bus_that_dispatches_a_message_to_a_workflow_processor()
    {
        $env = Environment::setUp();

        $processor = new StupidWorkflowProcessorMock();

        $env->services()->set(Definition::SERVICE_WORKFLOW_PROCESSOR, $processor, true);

        $commandBus = $env->services()->get('processing.command_bus.' . Definition::SERVICE_WORKFLOW_PROCESSOR);

        $this->assertInstanceOf('Prooph\ServiceBus\CommandBus', $commandBus);

        $message = WorkflowMessage::collectDataOf(UserDictionary::prototype(), 'test-case', NodeName::defaultName());

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

        $env->services()->set(Definition::SERVICE_WORKFLOW_PROCESSOR, $processor, true);

        $eventBus = $env->services()->get('processing.event_bus.' . Definition::SERVICE_WORKFLOW_PROCESSOR);

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
        ]), 'test-case', NodeName::defaultName());

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
            "processing" => [
                "channels" => [
                    'local' => [
                        'utils' => ["bus_plugin_1", "bus_plugin_2"]
                    ]
                ]
            ]
        ]);

        $env->services()->set("bus_plugin_1", $plugin1);
        $env->services()->set("bus_plugin_2", $plugin2);

        $env->services()->get('processing.command_bus.' . $env->getNodeName()->toString());

        $this->assertTrue($plugin1->isRegistered());
        $this->assertTrue($plugin2->isRegistered());
    }

    /**
     * @test
     */
    public function it_creates_command_bus_that_dispatches_a_message_to_a_message_dispatcher()
    {
        $env = Environment::setUp([
            "processing" => [
                "channels" => [
                    'stupid_dispatcher_channel' => [
                        'targets' => ["remote_command_handler"],
                        "message_dispatcher" => "stupid_message_dispatcher"
                    ]
                ]
            ]
        ]);

        $messageDispatcher = new StupidMessageDispatcher();

        $env->services()->set("stupid_message_dispatcher", $messageDispatcher);

        $commandBus = $env->services()->get("processing.command_bus.remote_command_handler");

        $message = WorkflowMessage::collectDataOf(UserDictionary::prototype(), 'test-case', NodeName::defaultName());

        $commandBus->dispatch($message);

        $this->assertEquals($message->messageName(), $messageDispatcher->getLastReceivedMessage()->name());
    }

    /**
     * @test
     */
    public function it_creates_a_command_bus_that_dispatches_a_message_to_a_workflow_message_handler()
    {
        $env = Environment::setUp([
            "processing" => [
                "channels" => [
                    'message_handler_channel' => [
                        'targets' => ["test_command_handler"],
                    ]
                ]
            ]
        ]);

        $messageHandler = new TestWorkflowMessageHandler();

        $env->services()->set('test_command_handler', $messageHandler);

        $commandBus = $env->services()->get("processing.command_bus.test_command_handler");

        $message = WorkflowMessage::collectDataOf(UserDictionary::prototype(), 'test-case', NodeName::defaultName());

        $commandBus->dispatch($message);

        $this->assertSame($message, $messageHandler->lastWorkflowMessage());
    }

    /**
     * @test
     */
    public function it_selects_the_target_channel_even_when_origin_and_sender_do_not_match()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ]
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target___a_origin___a_sender');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertTrue($targetChannelPlugin->isRegistered());
    }

    /**
     * @test
     */
    public function it_selects_the_default_channel_because_origin_is_not_given()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'origin' => 'required_origin',
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ]
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertFalse($targetChannelPlugin->isRegistered());
    }

    /**
     * @test
     */
    public function it_selects_the_target_and_origin_channel()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $targetAndOriginPlugin = new TargetAndOriginChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);
        $serviceLocator->setService('target_and_origin_channel_plugin', $targetAndOriginPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ],
                    'target_and_origin_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'origin' => 'my_origin',
                        'utils' => [
                            'target_and_origin_channel_plugin'
                        ]
                    ]
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target___my_origin');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertFalse($targetChannelPlugin->isRegistered());
        $this->assertTrue($targetAndOriginPlugin->isRegistered());
    }

    /**
     * @test
     */
    public function it_selects_the_target_channel_because_origin_criteria_does_not_match_but_is_not_set_for_target_channel()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $targetAndOriginPlugin = new TargetAndOriginChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);
        $serviceLocator->setService('target_and_origin_channel_plugin', $targetAndOriginPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ],
                    'target_and_origin_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'origin' => 'another_origin',
                        'utils' => [
                            'target_and_origin_channel_plugin'
                        ]
                    ]
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target___my_origin');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertTrue($targetChannelPlugin->isRegistered());
        $this->assertFalse($targetAndOriginPlugin->isRegistered());
    }

    /**
     * @test
     */
    public function it_selects_the_target_and_sender_channel_by_using_the_origin_as_sender()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $targetAndSenderPlugin = new TargetAndSenderChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);
        $serviceLocator->setService('target_and_sender_channel_plugin', $targetAndSenderPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ],
                    'target_and_sender_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'sender' => 'my_sender',
                        'utils' => [
                            'target_and_sender_channel_plugin'
                        ]
                    ]
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target___my_sender');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertFalse($targetChannelPlugin->isRegistered());
        $this->assertTrue($targetAndSenderPlugin->isRegistered());
    }

    /**
     * @test
     */
    public function it_selects_the_target_and_sender_channel()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $targetAndSenderPlugin = new TargetAndSenderChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);
        $serviceLocator->setService('target_and_sender_channel_plugin', $targetAndSenderPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ],
                    'target_and_sender_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'sender' => 'my_sender',
                        'utils' => [
                            'target_and_sender_channel_plugin'
                        ]
                    ]
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target___my_origin___my_sender');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertFalse($targetChannelPlugin->isRegistered());
        $this->assertTrue($targetAndSenderPlugin->isRegistered());
    }

    /**
     * @test
     */
    public function it_selects_the_target_channel_because_sender_criteria_does_not_match_but_is_not_set_for_target_channel()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $targetAndSenderPlugin = new TargetAndSenderChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);
        $serviceLocator->setService('target_and_sender_channel_plugin', $targetAndSenderPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ],
                    'target_and_origin_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'sender' => 'another_sender',
                        'utils' => [
                            'target_and_sender_channel_plugin'
                        ]
                    ]
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target___my_origin');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertTrue($targetChannelPlugin->isRegistered());
        $this->assertFalse($targetAndSenderPlugin->isRegistered());
    }

    /**
     * @test
     */
    public function it_selects_local_channel_because_no_criteria_combination_matches()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $targetAndOriginPlugin = new TargetAndOriginChannelPlugin();

        $targetAndSenderPlugin = new TargetAndSenderChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);
        $serviceLocator->setService('target_and_origin_channel_plugin', $targetAndOriginPlugin);
        $serviceLocator->setService('target_and_sender_channel_plugin', $targetAndSenderPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_and_origin_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'origin' => 'my_origin',
                        'sender' => 'another_sender',
                        'utils' => [
                            'target_and_origin_channel_plugin'
                        ]
                    ],
                    'target_and_sender_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'origin' => 'another_origin',
                        'sender' => 'my_sender',
                        'utils' => [
                            'target_and_sender_channel_plugin'
                        ]
                    ],
                    'local' => [
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ]
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target___my_origin___my_sender');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertFalse($targetAndOriginPlugin->isRegistered());
        $this->assertFalse($targetAndSenderPlugin->isRegistered());
        $this->assertTrue($targetChannelPlugin->isRegistered());
    }

    /**
     * @test
     */
    public function it_selects_the_channel_with_the_best_match()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $targetAndOriginPlugin = new TargetAndOriginChannelPlugin();

        $targetAndSenderPlugin = new TargetAndSenderChannelPlugin();

        $targetOriginAndSenderPlugin = new TargetOriginAndSenderChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);
        $serviceLocator->setService('target_and_origin_channel_plugin', $targetAndOriginPlugin);
        $serviceLocator->setService('target_and_sender_channel_plugin', $targetAndSenderPlugin);
        $serviceLocator->setService('target_origin_and_sender_channel_plugin', $targetOriginAndSenderPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ],
                    'target_and_origin_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'origin' => 'my_origin',
                        'utils' => [
                            'target_and_origin_channel_plugin'
                        ]
                    ],
                    'target_and_sender_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'sender' => 'my_sender',
                        'utils' => [
                            'target_and_sender_channel_plugin'
                        ]
                    ],
                    'target_origin_and_sender_channel' => [
                        'targets' => [
                            'my_target'
                        ],
                        'origin' => 'my_origin',
                        'sender' => 'my_sender',
                        'utils' => [
                            'target_origin_and_sender_channel_plugin'
                        ]
                    ],
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target___my_origin___my_sender');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertFalse($targetAndOriginPlugin->isRegistered());
        $this->assertFalse($targetAndSenderPlugin->isRegistered());
        $this->assertFalse($targetChannelPlugin->isRegistered());
        $this->assertTrue($targetOriginAndSenderPlugin->isRegistered());
    }

    /**
     * @test
     */
    public function it_selects_the_local_channel_because_target_is_in_no_list()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $targetAndOriginPlugin = new TargetAndOriginChannelPlugin();

        $targetAndSenderPlugin = new TargetAndSenderChannelPlugin();

        $targetOriginAndSenderPlugin = new TargetOriginAndSenderChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);
        $serviceLocator->setService('target_and_origin_channel_plugin', $targetAndOriginPlugin);
        $serviceLocator->setService('target_and_sender_channel_plugin', $targetAndSenderPlugin);
        $serviceLocator->setService('target_origin_and_sender_channel_plugin', $targetOriginAndSenderPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_channel' => [
                        'targets' => [
                            'another_target'
                        ],
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ],
                    'target_and_origin_channel' => [
                        'targets' => [
                            'another_target'
                        ],
                        'origin' => 'my_origin',
                        'utils' => [
                            'target_and_origin_channel_plugin'
                        ]
                    ],
                    'target_and_sender_channel' => [
                        'targets' => [
                            'another_target'
                        ],
                        'sender' => 'my_sender',
                        'utils' => [
                            'target_and_sender_channel_plugin'
                        ]
                    ],
                    'target_origin_and_sender_channel' => [
                        'targets' => [
                            'another_target'
                        ],
                        'origin' => 'my_origin',
                        'sender' => 'my_sender',
                        'utils' => [
                            'target_origin_and_sender_channel_plugin'
                        ]
                    ],
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target___my_origin___my_sender');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertFalse($targetAndOriginPlugin->isRegistered());
        $this->assertFalse($targetAndSenderPlugin->isRegistered());
        $this->assertFalse($targetChannelPlugin->isRegistered());
        $this->assertFalse($targetOriginAndSenderPlugin->isRegistered());
    }

    /**
     * @test
     */
    public function it_selects_the_target_and_sender_channel_because_target_list_contains_wildcard()
    {
        $targetChannelPlugin = new TargetChannelPlugin();

        $targetAndOriginPlugin = new TargetAndOriginChannelPlugin();

        $targetAndSenderPlugin = new TargetAndSenderChannelPlugin();

        $targetOriginAndSenderPlugin = new TargetOriginAndSenderChannelPlugin();

        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('target_channel_plugin', $targetChannelPlugin);
        $serviceLocator->setService('target_and_origin_channel_plugin', $targetAndOriginPlugin);
        $serviceLocator->setService('target_and_sender_channel_plugin', $targetAndSenderPlugin);
        $serviceLocator->setService('target_origin_and_sender_channel_plugin', $targetOriginAndSenderPlugin);

        $serviceLocator->setService('configuration', [
            'processing' => [
                'channels' => [
                    'target_channel' => [
                        'targets' => [
                            'another_target'
                        ],
                        'utils' => [
                            'target_channel_plugin'
                        ]
                    ],
                    'target_and_origin_channel' => [
                        'targets' => [
                            'another_target'
                        ],
                        'origin' => 'my_origin',
                        'utils' => [
                            'target_and_origin_channel_plugin'
                        ]
                    ],
                    'target_and_sender_channel' => [
                        'targets' => [
                            '*' //Wildcard matching all targets
                        ],
                        'sender' => 'my_sender',
                        'utils' => [
                            'target_and_sender_channel_plugin'
                        ]
                    ],
                    'target_origin_and_sender_channel' => [
                        'targets' => [
                            'another_target'
                        ],
                        'origin' => 'my_origin',
                        'sender' => 'my_sender',
                        'utils' => [
                            'target_origin_and_sender_channel_plugin'
                        ]
                    ],
                ]
            ]
        ]);

        $env = Environment::setUp($serviceLocator);

        $channel = $env->services()->get('processing.command_bus.my_target___my_origin___my_sender');

        $this->assertInstanceOf(\Prooph\ServiceBus\CommandBus::class, $channel);

        $this->assertFalse($targetAndOriginPlugin->isRegistered());
        $this->assertTrue($targetAndSenderPlugin->isRegistered());
        $this->assertFalse($targetChannelPlugin->isRegistered());
        $this->assertFalse($targetOriginAndSenderPlugin->isRegistered());
    }
}
 