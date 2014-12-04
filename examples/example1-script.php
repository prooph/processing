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
 *
 * The environment set up is made by hand without the help of Ginger\Environment to show you some insights.
 * Normally most of the heavy set up is made by factories. Ginger\Environment and Zend\ServiceManager are the glue
 * components to connect a workflow processor with workflow message handlers.
 */

/**
 * Change working dir to current dir to simplify the work with paths
 */
chdir(__DIR__);

/**
 * Use composer autoloader
 * If autoloading doesn't work please make sure that you ran the command
 * php composer.phar install before running this script
 */
require_once '../vendor/autoload.php';

/**
 * This function returns a ready to use Ginger\Processor\WorkflowProcessor
 *
 * A workflow in Ginger is the definition of a process. Each Process contains
 * a task list. And each task on the list describes a single action that should be done.
 * The workflow processor manages running processes and logs their progress and status.
 * Ginger makes use of a technique called event sourcing. The model is based on events
 * which are persisted in a stream and used to reconstitute the model for further processing.
 *
 * @return \Ginger\Processor\WorkflowProcessor
 */
function set_up_workflow_environment() {

    //A process definition is a configuration based on a php array
    //We define a linear messaging process here ...
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
    //The process factory is capable of parsing a process definition and build a process object from it
    //which can be processed by a workflow processor
    $processFactory = new \Ginger\Processor\ProcessFactory(
        [
            \Ginger\Message\MessageNameUtils::getDataCollectedEventName('GingerExample\Type\SourceUser') => $processDefinition
        ]
    );

    //Here we set up the processor dependencies. Don't worry!
    //When you set up your own workflow system Ginger\Environment will
    //do the heavy lifting for you. We don't use it here cause you should
    //get an idea of the internal structure.
    //It's always a good thing to know the internals of a system not only the public API.
    //See comments in the set up functions to get more information about the individual components
    $eventStore         = _set_up_event_store();
    $workflowEngine     = _set_up_workflow_engine();
    $processRepository  = new \Ginger\Processor\ProcessRepository($eventStore);

    //We need to create an empty stream for our process events.
    //The example uses in memory persistence
    //so we need to create the stream each time the script is running.
    //A production system should have a set up script and make use of a persistent adapter
    //available for ProophEventStore
    $eventStore->beginTransaction();

    $eventStore->create(
        new \Prooph\EventStore\Stream\Stream(
            new \Prooph\EventStore\Stream\StreamName('Ginger\Processor\Process'),
            []
        )
    );

    $eventStore->commit();

    /**
     * Summary of what we've learned:
     * The WorkflowProcessor is a so called process manager. It triggers and receives messages with the help of
     * a WorkflowEngine, starts and updates processes and manages the persistence of recorded process events with the
     * help of an EventStore and a ProcessRepository.
     * New processes are derived from a ProcessFactory which is capable of parsing process definitions and set up
     * processes with a TaskList.
     * The node name provided as first argument identifies the system which runs the processor. For local processing
     * it is enough to use the default node name defined in the definition class but when working with
     * many ginger nodes you should give each node a unique name and configure the workflow engine to
     * provide the correct bus for each node.
     */
    $workflowProcessor = new \Ginger\Processor\WorkflowProcessor(
        \Ginger\Processor\NodeName::defaultName(),
        $eventStore,
        $processRepository,
        $workflowEngine,
        $processFactory
    );

    $eventBus = $workflowEngine->getEventBusFor(\Ginger\Processor\Definition::SERVICE_WORKFLOW_PROCESSOR);

    //Ginger provides a special ProophServiceBus plugin that can route all incoming messages to a single target
    //in this case we want to route every message to the workflow processor
    //Ginger\Environment attaches such a router to each service bus
    $eventBus->utilize(new \Ginger\Processor\ProophPlugin\SingleTargetMessageRouter($workflowProcessor));

    //Ginger also provides a special invoke strategy for the workflow processor
    $eventBus->utilize(new \Ginger\Processor\ProophPlugin\WorkflowProcessorInvokeStrategy());

    return $workflowProcessor;
}

/**
 * The event store provides access to a data store which is capable of persisting events in streams.
 * See ProophEventStore documentation for details.
 *
 * The workflow processor uses the event store to persist process events
 * so that logging is a first class citizen
 * in the workflow system.
 *
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
                json_encode($recordedEvent->payload())
            );
        }
    });

    return $es;
}

/**
 * The workflow engine provides access to the communication layer of th Ginger system.
 * Communication is based on ProophServiceBus. We use the idea of CQRS to decouple the
 * workflow processor from workflow message handlers which are responsible for processing
 * single tasks. A workflow message handler is normally the glue component which connects
 * Ginger with an external system. It receives commands from the processor like collect data or
 * process data and send events back to tell the processor what's happened.
 *
 * Each target (external system) gets a command bus and an event bus assigned. These are the communication
 * channels between the processor and the target.
 * You can use the full power of ProophServiceBus so a channel can be a local bus, a link to a messaging infrastructure,
 * a link to a worker queue, or a http remote interface.
 *
 * @return \Ginger\Processor\RegistryWorkflowEngine
 */
function _set_up_workflow_engine() {
    $commandBus = new \Prooph\ServiceBus\CommandBus();

    $eventBus = new \Prooph\ServiceBus\EventBus();

    $commandRouter = new \Prooph\ServiceBus\Router\CommandRouter();

    //For our scenario it is enough to use a closure as workflow message handler
    //In a production system this should be a class loadable by Zend\ServiceManager
    //See the more complex scenarios to get an idea how such a set up can be look like.
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

