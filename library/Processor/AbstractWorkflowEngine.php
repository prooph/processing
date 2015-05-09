<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.01.15 - 17:53
 */

namespace Prooph\Processing\Processor;

use Prooph\Common\Messaging\RemoteMessage;
use Prooph\Processing\Message\ProcessingMessage;
use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\ProophPlugin\ToProcessingMessageTranslator;
use Prooph\Processing\Processor\Command\StartSubProcess;
use Prooph\Processing\Processor\Event\SubProcessFinished;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\MessageInterface;

/**
 * Class AbstractWorkflowEngine
 *
 * Provides default behaviour for a WorkflowEngine
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
abstract class AbstractWorkflowEngine implements WorkflowEngine
{
    const LOCAL_CHANNEL = "local";

    /**
     * @var ToProcessingMessageTranslator
     */
    private $toProcessingMessageTranslator = null;

    /**
     * Return command channel for given parameters
     *
     * If multiple channels match, the one with the highest match or highest priority should be returned.
     *
     * @param string $target
     * @param null|string $origin
     * @param null|string $sender
     * @return mixed
     */
    abstract public function getCommandChannelFor($target, $origin = null, $sender = null);

    /**
     * Return event channel for given parameters
     *
     * If multiple channels match, the one with the highest match or highest priority should be returned.
     *
     * @param string $target
     * @param null|string $origin
     * @param null|string $sender
     * @return mixed
     */
    abstract public function getEventChannelFor($target, $origin = null, $sender = null);

    /**
     * @inheritdoc
     */
    public function dispatch($message, $sender = null)
    {
        if ($message instanceof RemoteMessage) {
            $message = $this->getToProcessingMessageTranslator()->translateToProcessingMessage($message);
        }

        if (! $message instanceof ProcessingMessage) throw new \InvalidArgumentException(sprintf('Message can not be dispatched. Unknown type provided: %s', ((is_object($message))? get_class($message) : gettype($message))));

        $channelGetter = null;

        if (MessageNameUtils::isProcessingCommand($message->messageName()))    $channelGetter = "getCommandChannelFor";
        if (MessageNameUtils::isProcessingEvent($message->messageName()))      $channelGetter = "getEventChannelFor";
        if (MessageNameUtils::isProcessingLogMessage($message->messageName())) $channelGetter = "getEventChannelFor";
        if (StartSubProcess::MSG_NAME === $message->messageName())         $channelGetter = "getCommandChannelFor";
        if (SubProcessFinished::MSG_NAME === $message->messageName())      $channelGetter = "getEventChannelFor";

        if (is_null($channelGetter)) {
            throw new \InvalidArgumentException(sprintf('Channel detection for message %s was not possible', $message->messageName()));
        }

        /** @var $channelBus CommandBus|EventBus */
        $channelBus = $this->{$channelGetter}($message->target(), $message->origin(), $sender);

        $channelBus->dispatch($message);
    }

    /**
     * @return ToProcessingMessageTranslator
     */
    protected function getToProcessingMessageTranslator()
    {
        if (is_null($this->toProcessingMessageTranslator)) {
            $this->toProcessingMessageTranslator = new ToProcessingMessageTranslator();
        }

        return $this->toProcessingMessageTranslator;
    }
}
 