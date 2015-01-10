The WorkflowMessage
===================

[Back to index](../README.md#index)

# API

```php
class WorkflowMessage implements MessageNameProvider
{
    /**
     * @param \Ginger\Type\Prototype $aPrototype
     * @return WorkflowMessage
     */
    public static function collectDataOf(\Ginger\Type\Prototype $aPrototype);

    /**
     * @param \Ginger\Type\Type $data
     * @return WorkflowMessage
     */
    public static function newDataCollected(Type $data);

    /**
     * @param Prooph\ServiceBus\Message\MessageInterface $aMessage
     * @return WorkflowMessage
     * @throws \RuntimeException
     */
    public static function fromServiceBusMessage(Prooph\ServiceBus\Message\MessageInterface $aMessage);

    /**
     * Transforms current message to a data collected event and replaces payload data with collected data
     *
     * @param \Ginger\Type\Type $collectedData
     * @return WorkflowMessage
     * @throws \Ginger\Type\Exception\InvalidTypeException If answer type does not match with the previous requested type
     */
    public function answerWith(Type $collectedData);

    /**
     * Transforms current message to a process data command
     *
     * @return WorkflowMessage
     */
    public function prepareDataProcessing();

    /**
     * Transforms current message to a data processed event
     *
     * @return WorkflowMessage
     */
    public function answerWithDataProcessingCompleted();

    /**
     * @param \Ginger\Processor\ProcessId $processId
     * @throws \RuntimeException If message is already connected to process
     */
    public function connectToProcess(\Ginger\Processor\ProcessId $processId);

    /**
     * @return string Name of the message
     */
    public function messageName();

    /**
     * @return \Ginger\Message\Payload
     */
    public function payload();

    /**
     * @return ProcessId|null
     */
    public function getProcessId();

    /**
     * @return \Rhumsaa\Uuid\Uuid
     */
    public function uuid();

    /**
     * @return int
     */
    public function version();

    /**
     * @return \DateTime
     */
    public function createdOn();
}
```

## Static Factories

As you can see the [Ginger\Message\WorkflowMessage](https://github.com/gingerframework/gingerframework/blob/master/library/Ginger/Message/WorkflowMessage.php) provides three factory methods:

- `collectDataOf` can be used to send a command to a [Ginger\Message\WorkflowMessageHandler](https://github.com/gingerframework/gingerframework/blob/master/library/Ginger/Message/WorkflowMessageHandler.php)
to trigger the collection of some data described by the
given [Ginger\Type\Prototype](https://github.com/gingerframework/gingerframework/blob/master/library/Ginger/Type/Prototype.php).
When the WorkflowMessageHandler has collected the data it can call the `answerWith` method with the filled [Ginger\Type\Type](https://github.com/gingerframework/gingerframework/blob/master/library/Ginger/Type/Type.php)
and send the new message back via an event bus.

- `newDataCollected` can be used by WorkflowMessageHandlers to trigger a new workflow by publishing the event on an event bus.

- `fromServiceBusMessage` is mainly used by the [Ginger\Message\ProophPlugin\ToGingerMessageTranslator](https://github.com/gingerframework/gingerframework/blob/master/library/Ginger/Message/ProophPlugin/ToGingerMessageTranslator.php)
to transform a PSB message into a WorkflowMessage.

## Message Type Transformation

Like mentioned in the introduction the WorkflowMessage is a universal class that can be at one time a command and at another time an event.
The static factory methods provide the possibilities to initialize the WorkflowMessage with both states. A workflow can start with a command like "collect data of a user" or with an event like "user data was collected".
The appropriate WorkflowMessageHandler can then respond with the a copy of the message but it needs to transform it:

- `answerWith` method transforms a "collect data" command into a "data collected" event
- `prepareDataProcessing` method transforms a "data collected" event into a "process data" command
- `answerWithDataProcessingCompleted` method transforms a "process data" command into a "data processing completed" event

## Interacting With the WorkflowMessage

The WorkflowMessage is a container for [Payload](payload.md) that is exchanged between different WorkflowMessageHandlers.
Three additional information add some meta data to each WorkflowMessage:

- `uuid` is the global unique identifier of the WorkflowMessage
- `messageName` is required for the PSBs to route the WorkflowMessage to it's appropriate WorkflowMessageHandler. This information changes each time the WorkflowMessage is transformed to another type
- `processId` is the UUID of the connected process if one exists already
- `version` is the counter of how often the type of the WorkflowMessage has changed

## Connecting A Message To A Process

If the WorkflowMessage is created or handled by a Ginger\Processor\Process it is connected to it via the ProcessId referencing
the Process. A WorkflowMessage can only be connected to Process once with the method `connectToProcess`. If this method is called twice
it will throw an exception. Normally the connection is made by the WorkflowProcessor and you don't need to connect it yourself.
When the WorkflowMessage is converted to an answer event or follow up command the ProcessId is passed to that new message so that
the converted message automatically belongs to the same process.

