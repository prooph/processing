<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.01.15 - 17:53
 */

namespace Ginger\Processor;

use Ginger\Message\GingerMessage;
use Ginger\Message\MessageNameUtils;
use Ginger\Message\ProophPlugin\ToGingerMessageTranslator;
use Ginger\Processor\Command\StartSubProcess;
use Ginger\Processor\Event\SubProcessFinished;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\MessageInterface;

/**
 * Class AbstractWorkflowEngine
 *
 * Provides default behaviour for a WorkflowEngine
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
abstract class AbstractWorkflowEngine implements WorkflowEngine
{
    const LOCAL_CHANNEL = "local";

    /**
     * @var ToGingerMessageTranslator
     */
    private $toGingerMessageTranslator = null;

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
        if ($message instanceof MessageInterface) {
            $message = $this->getToGingerMessageTranslator()->translateToGingerMessage($message);
        }

        if (! $message instanceof GingerMessage) throw new \InvalidArgumentException(sprintf('Message can not be dispatched. Unknown type provided: %s', ((is_object($message))? get_class($message) : gettype($message))));

        $channelGetter = null;

        if (MessageNameUtils::isGingerCommand($message->messageName()))    $channelGetter = "getCommandChannelFor";
        if (MessageNameUtils::isGingerEvent($message->messageName()))      $channelGetter = "getEventChannelFor";
        if (MessageNameUtils::isGingerLogMessage($message->messageName())) $channelGetter = "getEventChannelFor";
        if (StartSubProcess::MSG_NAME === $message->messageName())         $channelGetter = "getCommandChannelFor";
        if (SubProcessFinished::MSG_NAME === $message->messageName())      $channelGetter = "getEventChannelFor";

        if (is_null($channelGetter)) {
            throw new \InvalidArgumentException(sprintf('Channel detection for message %s was not possible', $messageName));
        }

        /** @var $channelBus CommandBus|EventBus */
        $channelBus = $this->{$channelGetter}($message->target(), $message->origin(), $sender);

        $channelBus->dispatch($message);
    }

    /**
     * @return ToGingerMessageTranslator
     */
    protected function getToGingerMessageTranslator()
    {
        if (is_null($this->toGingerMessageTranslator)) {
            $this->toGingerMessageTranslator = new ToGingerMessageTranslator();
        }

        return $this->toGingerMessageTranslator;
    }
}
 