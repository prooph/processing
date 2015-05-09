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

use Prooph\Common\Messaging\RemoteMessage;
use Prooph\ServiceBus\Message\RemoteMessageDispatcher;

/**
 * Class StupidMessageDispatcher
 *
 * @package Prooph\ProcessingTest\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StupidMessageDispatcher implements RemoteMessageDispatcher
{
    private $lastReceivedMessage;

    /**
     * @param RemoteMessage $message
     * @return void
     */
    public function dispatch(RemoteMessage $message)
    {
        $this->lastReceivedMessage = $message;
    }

    /**
     * @return RemoteMessage
     */
    public function getLastReceivedMessage()
    {
        return $this->lastReceivedMessage;
    }
}
 