<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 19:04
 */

namespace Ginger\Message\Factory;

use Ginger\Message\WorkflowMessage;
use Prooph\ServiceBus\Command\CommandFactoryInterface;
use Prooph\ServiceBus\Message\MessageInterface;

/**
 * Class GingerCommandFactory
 *
 * @package Ginger\Message\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class GingerCommandFactory implements CommandFactoryInterface
{
    /**
     * @param MessageInterface $aMessage
     * @return mixed a command
     */
    public function fromMessage(MessageInterface $aMessage)
    {
        return WorkflowMessage::fromServiceBusMessage($aMessage);
    }
}
 