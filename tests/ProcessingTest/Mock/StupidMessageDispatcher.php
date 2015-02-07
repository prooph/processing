<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.11.14 - 00:36
 */

namespace Prooph\ProcessingTest\Mock;

use Prooph\ServiceBus\Message\MessageDispatcherInterface;
use Prooph\ServiceBus\Message\MessageInterface;

/**
 * Class StupidMessageDispatcher
 *
 * @package Prooph\ProcessingTest\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StupidMessageDispatcher implements MessageDispatcherInterface
{
    private $lastReceivedMessage;

    /**
     * @param MessageInterface $message
     * @return void
     */
    public function dispatch(MessageInterface $message)
    {
        $this->lastReceivedMessage = $message;
    }

    /**
     * @return MessageInterface
     */
    public function getLastReceivedMessage()
    {
        return $this->lastReceivedMessage;
    }
}
 