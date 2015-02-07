<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 20:16
 */

namespace Prooph\Processing\Message\ProophPlugin;

use Prooph\Processing\Message\ProcessingMessage;
use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Command\StartSubProcess;
use Prooph\Processing\Processor\Event\SubProcessFinished;
use Prooph\ServiceBus\Message\MessageInterface;
use Prooph\ServiceBus\Process\CommandDispatch;
use Prooph\ServiceBus\Process\EventDispatch;
use Prooph\ServiceBus\Process\MessageDispatch;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

/**
 * Class ToProcessingMessageTranslator
 *
 * @package Prooph\Processing\Message\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ToProcessingMessageTranslator extends AbstractListenerAggregate
{
    public function __invoke(MessageDispatch $messageDispatch)
    {
        $message = $messageDispatch->getMessage();

        if (! $message instanceof MessageInterface) return;

        $messageDispatch->setMessage($this->translateToProcessingMessage($message));
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
    public function translateToProcessingMessage(MessageInterface $message)
    {
        if (MessageNameUtils::isWorkflowMessage($message->name())) {
            return WorkflowMessage::fromServiceBusMessage($message);
        } else if (MessageNameUtils::isProcessingLogMessage($message->name())) {
            return LogMessage::fromServiceBusMessage($message);
        } else if (StartSubProcess::MSG_NAME === $message->name()) {
            return StartSubProcess::fromServiceBusMessage($message);
        } else if (SubProcessFinished::MSG_NAME === $message->name()) {
            return SubProcessFinished::fromServiceBusMessage($message);
        }

        throw new \InvalidArgumentException(sprintf('Message with name %s can not be translated. Unknown type provided.', $message->name()));
    }
}
 