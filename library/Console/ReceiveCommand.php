<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.11.14 - 23:02
 */

namespace Prooph\Processing\Console;

use Prooph\ServiceBus\Message\MessageHeader;
use Prooph\ServiceBus\Message\StandardMessage;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * Class ReceiveCommand
 *
 * @package Prooph\Processing\Console
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ReceiveCommand extends AbstractCommand
{
    const INVALID_MESSAGE = 1;
    const INVALID_CONFIG  = 2;

    public function __invoke(Route $route, AdapterInterface $console)
    {
        $consoleWriter = new ConsoleWriter($console);

        $consoleWriter->deriveVerbosityLevelFrom($route);

        $message = $route->getMatchedParam('message');

        $message = json_decode($message, true);

        if (is_null($message)) {
            $consoleWriter->writeError("Provided message is not a valid json string");
            $consoleWriter->writeError(json_last_error_msg());
            return self::INVALID_MESSAGE;
        }

        try {
            $message = StandardMessage::fromArray($message);
        } catch(\Exception $ex) {
            $consoleWriter->writeError("Invalid message");
            $consoleWriter->writeException($ex);
            return self::INVALID_MESSAGE;
        }

        try {

            $target = $route->getMatchedParam('target');

            $env = $this->loadEnvironment($route, $consoleWriter);

            $consoleWriter->writeInfo('Process PSB message: ' . $message->name());

            if ($message->header()->type() === MessageHeader::TYPE_COMMAND) {
                $env->getWorkflowEngine()->dispatch($message);
            } else {
                $env->getWorkflowEngine()->dispatch($message);
            }

            return 0;

        } catch (\Exception $ex) {
            $consoleWriter->writeException($ex);
            return self::MESSAGE_PROCESSING_FAILED;
        }
    }
}
 