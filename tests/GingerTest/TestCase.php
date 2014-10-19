<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 08.07.14 - 21:33
 */

namespace GingerTest;

use Ginger\Message\MessageNameUtils;
use Ginger\Message\ProophPlugin\HandleWorkflowMessageInvokeStrategy;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\RegistryWorkflowEngine;
use GingerTest\Mock\TestWorkflowMessageHandler;
use GingerTest\Mock\UserDictionary;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\Router\CommandRouter;

/**
 * Class TestCase
 *
 * @package GingerTest
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegistryWorkflowEngine
     */
    protected $workflowEngine;

    /**
     * @var TestWorkflowMessageHandler
     */
    protected $workflowMessageHandler;

    /**
     * @var CommandRouter
     */
    protected $commandRouter;

    protected function setUp()
    {
        $this->workflowMessageHandler = new TestWorkflowMessageHandler();

        $commandBus = new CommandBus();

        $this->commandRouter = new CommandRouter();

        $this->commandRouter->route(MessageNameUtils::getCollectDataCommandName('GingerTest\Mock\UserDictionary'))
            ->to($this->workflowMessageHandler);

        $commandBus->utilize($this->commandRouter);

        $commandBus->utilize(new HandleWorkflowMessageInvokeStrategy());

        $this->workflowEngine = new RegistryWorkflowEngine();

        $this->workflowEngine->registerCommandBus($commandBus, ['test-case', 'test-target']);
    }

    protected function tearDown()
    {
        $this->workflowMessageHandler->reset();
    }

    /**
     * @return WorkflowMessage
     */
    protected function getUserDataCollectedTestMessage()
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

        return WorkflowMessage::newDataCollected($user);
    }
}
 