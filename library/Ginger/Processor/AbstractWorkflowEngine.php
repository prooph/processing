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
     * The workflow engine automatically detects the change channel for the message.
     *
     * It uses GingerMessage::target() to detect the right channel.
     * If GingerMessage::target() returns null the "local" channel is used to dispatch the message.
     * If a service bus message is given the workflow engine translates it to a ginger message first.
     * If translation is not possible it should throw a InvalidArgumentException
     *
     * @param MessageInterface|GingerMessage $message
     * @return void
     * @throws \InvalidArgumentException
     */
    public function dispatch($message)
    {
        if ($message instanceof MessageInterface) {
            $message = $this->getToGingerMessageTranslator()->translateToGingerMessage($message);
        }

        $channelGetter = null;

        if (MessageNameUtils::isGingerCommand($message->messageName()))    $channelGetter = "getCommandChannelFor";
        if (MessageNameUtils::isGingerEvent($message->messageName()))      $channelGetter = "getEventChannelFor";
        if (MessageNameUtils::isGingerLogMessage($message->messageName())) $channelGetter = "getEventChannelFor";
        if (StartSubProcess::MSG_NAME === $message->messageName())         $channelGetter = "getCommandChannelFor";
        if (SubProcessFinished::MSG_NAME === $message->messageName())      $channelGetter = "getEventChannelFor";

        if (is_null($channelGetter)) {
            throw new \InvalidArgumentException(sprintf('Channel detection for message %s was not possible', $message->messageName()));
        }

        /** @var $channelBus CommandBus|EventBus */
        $channelBus = $this->{$channelGetter}($message->target());

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
 