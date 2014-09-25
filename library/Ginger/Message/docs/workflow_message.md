The WorkflowMessage
===================

[Back to index](../README.md)

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
     * @throws \Ginger\Type\Exception\InvalidTypeException If answer type does not match with the previous requested type
     */
    public function answerWith(Type $collectedData);

    /**
     * Transforms current message to a process data command
     */
    public function prepareDataProcessing();

    /**
     * Transforms current message to a data processed event
     */
    public function answerWithDataProcessingCompleted();

    /**
     * @return string Name of the message
     */
    public function getMessageName();

    /**
     * @return \Ginger\Message\Payload
     */
    public function getPayload();

    /**
     * @return \Rhumsaa\Uuid\Uuid
     */
    public function getUuid();

    /**
     * @return int
     */
    public function getVersion();

    /**
     * @return \DateTime
     */
    public function getCreatedOn();
}
```

## Static factories

As you can see the [Ginger\Message\WorkflowMessage](https://github.com/gingerframework/gingerframework/blob/master/library/Ginger/Message/WorkflowMessage.php) provides three factory methods:

- `collectDataOf` can be used to send a command to a [Ginger\Message\WorkflowMessageHandler](https://github.com/gingerframework/gingerframework/blob/master/library/Ginger/Message/WorkflowMessageHandler.php)
to trigger the collection of some data described by the
given [Ginger\Type\Prototype](https://github.com/gingerframework/gingerframework/blob/master/library/Ginger/Type/Prototype.php).
When the WorkflowMessageHandler has collected the data it can call the `answerWith` method with the filled [Ginger\Type\Type](https://github.com/gingerframework/gingerframework/blob/master/library/Ginger/Type/Type.php)
and send the message back via an event bus.

- `newDataCollected` can be used by connectors to trigger a new workflow