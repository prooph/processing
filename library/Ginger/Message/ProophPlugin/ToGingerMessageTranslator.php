<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 20:16
 */

namespace Ginger\Message\ProophPlugin;

use Ginger\Message\GingerMessage;
use Ginger\Message\LogMessage;
use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Command\StartSubProcess;
use Ginger\Processor\Event\SubProcessFinished;
use Prooph\ServiceBus\Message\MessageInterface;
use Prooph\ServiceBus\Process\CommandDispatch;
use Prooph\ServiceBus\Process\EventDispatch;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

/**
 * Class ToGingerMessageTranslator
 *
 * @package Ginger\Message\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ToGingerMessageTranslator extends AbstractListenerAggregate
{
    /**
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $identifiers = $events->getIdentifiers();

        if (in_array('command_bus', $identifiers)) {
            $this->listeners[] = $events->attach(CommandDispatch::INITIALIZE, array($this, 'onInitializeCommandDispatch'), 100);
        }

        if (in_array('event_bus', $identifiers)) {
            $this->listeners[] = $events->attach(EventDispatch::INITIALIZE, array($this, 'onInitializeEventDispatch'), 100);
        }
    }

    public function onInitializeCommandDispatch(CommandDispatch $commandDispatch)
    {
        $command = $commandDispatch->getCommand();

        if (! $command instanceof MessageInterface) return;

        if (MessageNameUtils::isGingerCommand($command->name())) {
            $commandDispatch->setCommand($this->translateToGingerMessage($command));
        }

        if ($command->name() === StartSubProcess::MSG_NAME) {
            $commandDispatch->setCommand($this->translateToGingerMessage($command));
        }
    }

    public function onInitializeEventDispatch(EventDispatch $eventDispatch)
    {
        $event = $eventDispatch->getEvent();

        if (! $event instanceof MessageInterface) return;

        if (MessageNameUtils::isGingerEvent($event->name())) {
            $eventDispatch->setEvent($this->translateToGingerMessage($event));
        } else if (MessageNameUtils::isGingerLogMessage($event->name())) {
            $eventDispatch->setEvent($this->translateToGingerMessage($event));
        }

        if ($event->name() === SubProcessFinished::MSG_NAME) {
            $eventDispatch->setEvent($this->translateToGingerMessage($event));
        }
    }

    /**
     * @param MessageInterface $message
     * @return GingerMessage
     * @throws \InvalidArgumentException
     */
    public function translateToGingerMessage(MessageInterface $message)
    {
        if (MessageNameUtils::isWorkflowMessage($message->name())) {
            return WorkflowMessage::fromServiceBusMessage($message);
        }

        if (MessageNameUtils::isGingerLogMessage($message->name())) {
            return LogMessage::fromServiceBusMessage($message);
        }

        if (StartSubProcess::MSG_NAME === $message->name()) {
            return StartSubProcess::fromServiceBusMessage($message);
        }

        if (SubProcessFinished::MSG_NAME === $message->name()) {
            return SubProcessFinished::fromServiceBusMessage($message);
        }

        throw new \InvalidArgumentException(sprintf('Provided message %s can not be translated to a ginger message', $message->name()));
    }
}
 