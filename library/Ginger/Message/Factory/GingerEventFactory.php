<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 19:34
 */

namespace Ginger\Message\Factory;

use Ginger\Message\WorkflowMessage;
use Prooph\ServiceBus\Event\EventFactoryInterface;
use Prooph\ServiceBus\Message\MessageInterface;

/**
 * Class GingerEventFactory
 *
 * @package Ginger\Message\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class GingerEventFactory implements EventFactoryInterface
{
    /**
     * @param MessageInterface $aMessage
     * @return mixed an Event
     */
    public function fromMessage(MessageInterface $aMessage)
    {
        return WorkflowMessage::fromServiceBusMessage($aMessage);
    }
}
 