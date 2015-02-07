<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 04.10.14 - 20:59
 */

namespace Prooph\Processing\Processor\Task\Event;

use Prooph\Processing\Message\LogMessage;

class LogMessageReceived extends TaskEntryChanged
{
    public static function record(LogMessage $logMessage)
    {
        return self::at($logMessage->processTaskListPosition(), array(
            'message' => $logMessage->toServiceBusMessage()->toArray()
        ));
    }
}
 