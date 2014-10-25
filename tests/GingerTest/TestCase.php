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
use Ginger\Processor\Command\StartChildProcess;
use Ginger\Processor\Definition;
use Ginger\Processor\ProcessFactory;
use Ginger\Processor\ProcessRepository;
use Ginger\Processor\ProophPlugin\WorkflowProcessorInvokeStrategy;
use Ginger\Processor\RegistryWorkflowEngine;
use Ginger\Processor\WorkflowProcessor;
use GingerTest\Mock\TestWorkflowMessageHandler;
use GingerTest\Mock\UserDictionary;
use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Prooph\EventStore\Stream\AggregateStreamStrategy;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\Router\CommandRouter;

/**
 * Class TestCase
 *
 * This is the base class for all GingerTests. It provides a lot of test objects which are used in the various
 * test cases. It also defines test workflow scenarios and set up the involved components to support them.
 *
 * 1. Scenario:
 *   - Start new LinearMessagingProcess when GingerTest\Mock\UserDictionary was collected from test-case source.
 *     - Use TestCase::getUserDataCollectedTestMessage method to get a ready to use WorkflowMessage
 *   - Send a ProcessData message to a TestWorkflowMessageHandler
 *     - GingerType changes to GingerTest\Mock\TargetUserDictionary
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

    /**
     * @var WorkflowProcessor
     */
    private $workflowProcessor;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var ProcessRepository
     */
    private $processRepository;

    /**
     * @var PostCommitEvent
     */
    protected $lastPostCommitEvent;

    protected function setUp()
    {
        $this->workflowMessageHandler = new TestWorkflowMessageHandler();

        $commandBus = new CommandBus();

        $this->commandRouter = new CommandRouter();

        $this->commandRouter->route(MessageNameUtils::getCollectDataCommandName('GingerTest\Mock\UserDictionary'))
            ->to($this->workflowMessageHandler);

        $this->commandRouter->route(MessageNameUtils::getCollectDataCommandName('GingerTest\Mock\UserDictionaryS2'))
            ->to($this->workflowMessageHandler);

        $this->commandRouter->route(MessageNameUtils::getProcessDataCommandName('GingerTest\Mock\TargetUserDictionary'))
            ->to($this->workflowMessageHandler);

        $commandBus->utilize($this->commandRouter);

        $commandBus->utilize(new HandleWorkflowMessageInvokeStrategy());

        $commandBus->utilize(new WorkflowProcessorInvokeStrategy());

        $this->workflowEngine = new RegistryWorkflowEngine();

        $this->workflowEngine->registerCommandBus($commandBus, ['test-case', 'test-target', Definition::WORKFLOW_PROCESSOR]);
    }

    protected function tearDown()
    {
        $this->workflowMessageHandler->reset();

        $this->eventStore = null;
        $this->processRepository = null;
        $this->workflowProcessor = null;
        $this->lastPostCommitEvent = null;
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

    /**
     * @return WorkflowProcessor
     */
    protected function getTestWorkflowProcessor()
    {
        if (is_null($this->workflowProcessor)) {
            $this->workflowProcessor = new WorkflowProcessor(
                $this->getTestEventStore(),
                $this->getTestProcessRepository(),
                $this->workflowEngine,
                $this->getTestProcessFactory()
            );

            $this->commandRouter->route(StartChildProcess::MSG_NAME)->to($this->workflowProcessor);
        }

        return $this->workflowProcessor;
    }

    /**
     * @return EventStore
     */
    protected function getTestEventStore()
    {
        if (is_null($this->eventStore)) {
            $inMemoryAdapter = new InMemoryAdapter();

            $config = new Configuration();

            $config->setAdapter($inMemoryAdapter);

            $this->eventStore = new EventStore($config);

            $this->eventStore->getPersistenceEvents()->attach("commit.post", function(PostCommitEvent $postCommitEvent) {
                $this->lastPostCommitEvent = $postCommitEvent;
            });
        }

        return $this->eventStore;
    }

    /**
     * @return ProcessRepository
     */
    protected function getTestProcessRepository()
    {
        if (is_null($this->processRepository)) {
            $this->processRepository = new ProcessRepository($this->getTestEventStore());
        }

        $this->getTestEventStore()->beginTransaction();

        $this->getTestEventStore()->create(new Stream(new StreamName('Ginger\Processor\Process'), []));

        $this->getTestEventStore()->commit();

        return $this->processRepository;
    }

    /**
     * @return ProcessFactory
     */
    private function getTestProcessFactory()
    {
        $processDefinitionS1 = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [
                [
                    "task_type"      => Definition::TASK_PROCESS_DATA,
                    "target"         => 'test-target',
                    "allowed_types"  => ['GingerTest\Mock\TargetUserDictionary']
                ]
            ]
        ];

        $processDefinitionS2 = [
            "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
            "tasks" => [
                [
                    "task_type"          => Definition::TASK_RUN_CHILD_PROCESS,
                    "process_definition" => [
                        "process_type" => Definition::PROCESS_LINEAR_MESSAGING,
                        "tasks" => [
                            [
                                "task_type"      => Definition::TASK_PROCESS_DATA,
                                "target"         => 'test-target',
                                "allowed_types"  => ['GingerTest\Mock\TargetUserDictionary']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $scenario2Message = clone $wfMessage;

        $scenario2Message->changeGingerType('GingerTest\Mock\UserDictionaryS2');

        return new ProcessFactory(
            [
                //Scenario 1 definition
                $wfMessage->getMessageName() => $processDefinitionS1,
                //Scenario 2 definition
                $scenario2Message->getMessageName() => $processDefinitionS2
            ]
        );
    }
}
 