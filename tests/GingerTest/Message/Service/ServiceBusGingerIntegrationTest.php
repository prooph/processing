<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 21:07
 */

namespace GingerTest\Message\Service;

use Ginger\Message\Service\ServiceBusGingerFactoriesProvider;
use Ginger\Message\Service\ServiceBusGingerMessageRouter;
use Ginger\Message\WorkflowMessage;
use GingerTest\TestCase;
use GingerTest\Type\Mock\UserDictionary;
use Prooph\ServiceBus\Command\CommandReceiver;
use Prooph\ServiceBus\Event\EventReceiver;
use Prooph\ServiceBus\Message\InMemoryMessageDispatcher;
use Prooph\ServiceBus\Message\Queue;
use Prooph\ServiceBus\Service\CommandReceiverLoader;
use Prooph\ServiceBus\Service\Definition;
use Prooph\ServiceBus\Service\EventReceiverLoader;
use Prooph\ServiceBus\Service\ServiceBusConfiguration;
use Prooph\ServiceBus\Service\ServiceBusManager;

/**
 * Class ServiceBusGingerIntegrationTest
 *
 * @package GingerTest\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ServiceBusGingerIntegrationTest extends TestCase
{
    /**
     * @var ServiceBusManager
     */
    private $serviceBus;

    private $receivedWorkflowMessage;

    protected function setUp()
    {
        $queue = new Queue('ginger-gingertesttypemockuserdictionary-bus');

        $messageDispatcher = new InMemoryMessageDispatcher();

        $serviceBusManager = new ServiceBusManager(
            new ServiceBusConfiguration(array(
                Definition::EVENT_MAP => array(
                    'ginger-message-gingertesttypemockuserdictionary-data-collected'
                    => function (WorkflowMessage $workflowMessage) {
                            $this->receivedWorkflowMessage = $workflowMessage;
                        }
                ),
                Definition::EVENT_BUS => array(
                    $queue->name() => array(
                        Definition::MESSAGE_DISPATCHER => 'in_memory_message_dispatcher'
                    )
                )
            ))
        );

        $serviceBusManager->events()->attach(new ServiceBusGingerFactoriesProvider());

        $serviceBusManager->events()->attach(new ServiceBusGingerMessageRouter());

        $serviceBusManager->initialize();

        $eventReceiver = new EventReceiver($serviceBusManager);

        $eventReceiverLoader = new EventReceiverLoader();

        $eventReceiverLoader->setService($queue->name(), $eventReceiver);

        $messageDispatcher->registerEventReceiverLoaderForQueue($queue, $eventReceiverLoader);

        $serviceBusManager->getMessageDispatcherLoader()->setService('in_memory_message_dispatcher', $messageDispatcher);

        $this->serviceBus = $serviceBusManager;
    }
    /**
     * @test
     */
    public function it_registers_all_required_factories_so_that_a_workflow_message_can_be_send_through_service_bus()
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

        $wfMessage = WorkflowMessage::newDataCollected($user);

        $this->serviceBus->route($wfMessage);

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $this->receivedWorkflowMessage);
    }
}
 