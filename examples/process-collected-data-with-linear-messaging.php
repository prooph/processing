<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 22:29
 */

/**
 * This example shows a simple linear messaging workflow with one task.
 * The WorkflowProcessor receives an initial "data-collected" WorkflowMessage from a source,
 * starts a new process and the process forwards the collected data to a target-file-writer which writes
 * the data to a new file -> examples/data/target-data.txt
 */

chdir(__DIR__);

require_once '../vendor/autoload.php';

/**
 * @return \Ginger\Processor\WorkflowProcessor
 */
function set_up_workflow_environment() {

    //We define the linear messaging process ...
    $processDefinition = [
        "process_type" => \Ginger\Processor\Definition::PROCESS_LINEAR_MESSAGING,
        "tasks" => [
            [
                "task_type"     => \Ginger\Processor\Definition::TASK_PROCESS_DATA,
                "target"        => "target-file-writer",
                "allowed_types" => ['GingerExample\Type\SourceUser']
            ]
        ]
    ];

    //... and map it to the name of the initial workflow message which will trigger the process
    $processFactory = new \Ginger\Processor\ProcessFactory(
        [
            \Ginger\Message\MessageNameUtils::getDataCollectedEventName('GingerExample\Type\SourceUser') => $processDefinition
        ]
    );

    $eventStore         = _set_up_event_store();
    $workflowEngine     = _set_up_workflow_engine();
    $processRepository  = new \Ginger\Processor\ProcessRepository($eventStore);

    $eventStore->beginTransaction();

    $eventStore->create(
        new \Prooph\EventStore\Stream\Stream(
            new \Prooph\EventStore\Stream\StreamName('Ginger\Processor\Process'),
            []
        )
    );

    $eventStore->commit();

    /**
     * The WorkflowProcessor is a so called process manager. It triggers and receives messages with the help of
     * a WorkflowEngine, starts and updates processes and manages the persistence of recorded process events with the
     * help of an EventStore and a ProcessRepository.
     * New processes are derived from a ProcessFactory which is capable of parsing process definitions and set up
     * processes with a TaskList.
     */
    $workflowProcessor = new \Ginger\Processor\WorkflowProcessor(
        $eventStore,
        $processRepository,
        $workflowEngine,
        $processFactory
    );

    $eventBus = $workflowEngine->getEventBusFor(\Ginger\Processor\Definition::SERVICE_WORKFLOW_PROCESSOR);

    $eventBus->utilize(new \Ginger\Processor\ProophPlugin\SingleTargetMessageRouter($workflowProcessor));

    $eventBus->utilize(new \Ginger\Processor\ProophPlugin\WorkflowProcessorInvokeStrategy());

    return $workflowProcessor;
}

/**
 * @return \Prooph\EventStore\EventStore
 */
function _set_up_event_store() {
    $inMemoryAdapter = new \Prooph\EventStore\Adapter\InMemoryAdapter();

    $config = new \Prooph\EventStore\Configuration\Configuration();

    $config->setAdapter($inMemoryAdapter);

    $es = new \Prooph\EventStore\EventStore($config);

    //Attach an output logger which prints information about recorded events
    $es->getPersistenceEvents()->attach("commit.post", function (\Prooph\EventStore\PersistenceEvent\PostCommitEvent $event) {
        foreach ($event->getRecordedEvents() as $recordedEvent) {
            echo sprintf(
                "Event %s recorded with payload: \n\n%s\n\n",
                $recordedEvent->eventName()->toString(),
                print_r($recordedEvent->payload(), true)
            );
        }
    });

    return $es;
}

/**
 * @return \Ginger\Processor\RegistryWorkflowEngine
 */
function _set_up_workflow_engine() {
    $commandBus = new \Prooph\ServiceBus\CommandBus();

    $eventBus = new \Prooph\ServiceBus\EventBus();

    $commandRouter = new \Prooph\ServiceBus\Router\CommandRouter();

    $commandRouter->route(\Ginger\Message\MessageNameUtils::getProcessDataCommandName('GingerExample\Type\SourceUser'))
        ->to(function (\Ginger\Message\WorkflowMessage $message) use ($eventBus) {

            $dataAsJsonString = json_encode($message->getPayload());

            $answer = $message->answerWithDataProcessingCompleted();

            try {
                \Zend\Stdlib\ErrorHandler::start();

                if (!file_put_contents('data/target-data.txt', $dataAsJsonString)) {
                    \Zend\Stdlib\ErrorHandler::stop(true);
                }

            } catch (\Exception $ex) {
                $answer = \Ginger\Message\LogMessage::logException($ex, $message->getProcessTaskListPosition());
            }

            $eventBus->dispatch($answer);
        });

    $commandBus->utilize($commandRouter);

    $commandBus->utilize(new \Prooph\ServiceBus\InvokeStrategy\CallbackStrategy());

    $workflowEngine = new \Ginger\Processor\RegistryWorkflowEngine();

    $workflowEngine->registerCommandBus($commandBus, ['target-file-writer']);

    $workflowEngine->registerEventBus($eventBus, [\Ginger\Processor\Definition::SERVICE_WORKFLOW_PROCESSOR]);

    return $workflowEngine;
}

//Let's start the example
$workflowProcessor = set_up_workflow_environment();

//Step 1: Read source data
$userData = include('data/user-source-data.php');

//Step 2: Use implementation of Ginger\Type\Type to validate source data
$user = \GingerExample\Type\SourceUser::fromNativeValue($userData);

//Step 3: Prepare WorkflowMessage
$userDataCollected = \Ginger\Message\WorkflowMessage::newDataCollected($user);

//Step 4: Start processing by sending a "data-collected" event to the WorkflowProcessor (simplified step without using an EventBus)
$workflowProcessor->receiveMessage($userDataCollected);

//Done: Check examples/data/target-data.txt. You should see the json representation of user data. If not please check
//output of your console window. The script prints the log of the process on the screen

