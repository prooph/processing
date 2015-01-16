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
use Prooph\ServiceBus\Process\MessageDispatch;
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
    public function __invoke(MessageDispatch $messageDispatch)
    {
        $message = $messageDispatch->getMessage();

        if (! $message instanceof MessageInterface) return;

        $messageDispatch->setMessage($this->translateToGingerMessage($message));
    }

    /**
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MessageDispatch::INITIALIZE, $this);
    }

    /**
     * @param MessageInterface $message
     * @return LogMessage|WorkflowMessage|StartSubProcess|SubProcessFinished
     * @throws \InvalidArgumentException
     */
    public function translateToGingerMessage(MessageInterface $message)
    {
        if (MessageNameUtils::isWorkflowMessage($message->name())) {
            return WorkflowMessage::fromServiceBusMessage($message);
        } else if (MessageNameUtils::isGingerLogMessage($message->name())) {
            return LogMessage::fromServiceBusMessage($message);
        } else if (StartSubProcess::MSG_NAME === $message->name()) {
            return StartSubProcess::fromServiceBusMessage($message);
        } else if (SubProcessFinished::MSG_NAME === $message->name()) {
            return SubProcessFinished::fromServiceBusMessage($message);
        }

        throw new \InvalidArgumentException(sprintf('Message with name %s can not be translated. Unknown type provided.', $message->name()));
    }
}
 