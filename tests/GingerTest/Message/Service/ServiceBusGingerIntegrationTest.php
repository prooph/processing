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

use Ginger\Message\ProophPlugin\FromGingerMessageTranslator;
use Ginger\Message\ProophPlugin\ToGingerMessageTranslator;
use Ginger\Message\WorkflowMessage;
use GingerTest\TestCase;
use GingerTest\Mock\UserDictionary;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\CallbackStrategy;
use Prooph\ServiceBus\InvokeStrategy\ForwardToMessageDispatcherStrategy;
use Prooph\ServiceBus\Message\InMemoryMessageDispatcher;
use Prooph\ServiceBus\Router\EventRouter;

/**
 * Class ServiceBusGingerIntegrationTest
 *
 * @package GingerTest\Message\Service
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ServiceBusGingerIntegrationTest extends TestCase
{
    private $receivedWorkflowMessage;

    /**
     * @var InMemoryMessageDispatcher
     */
    private $messageDispatcher;

    protected function setUp()
    {
        $eventBus = new EventBus();

        $this->messageDispatcher = new InMemoryMessageDispatcher(new CommandBus(), $eventBus);

        $eventRouter = new EventRouter();

        $eventRouter->route('ginger-message-gingertestmockuserdictionary-data-collected')
            ->to(function (WorkflowMessage $workflowMessage) {
                $this->receivedWorkflowMessage = $workflowMessage;
            });

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ToGingerMessageTranslator());

        $eventBus->utilize(new CallbackStrategy());
    }
    /**
     * @test
     */
    public function it_sends_workflow_message_via_message_dispatcher_to_a_handler()
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

        $eventBus = new EventBus();

        $eventRouter = new EventRouter();

        $eventRouter->route($wfMessage->getMessageName())->to($this->messageDispatcher);

        $eventBus->utilize($eventRouter);

        $eventBus->utilize(new ForwardToMessageDispatcherStrategy(new FromGingerMessageTranslator()));

        $eventBus->dispatch($wfMessage);

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $this->receivedWorkflowMessage);
    }
}
 