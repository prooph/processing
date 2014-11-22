<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.11.14 - 19:45
 */

namespace Ginger\Console;

use Ginger\Message\WorkflowMessage;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * Class CollectCommand
 *
 * @package Ginger\Console
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class CollectCommand extends AbstractCommand
{
    const INVALID_GINGER_TYPE = 1;

    public function __invoke(Route $route, AdapterInterface $console)
    {
        $consoleWriter = new ConsoleWriter($console);

        $consoleWriter->deriveVerbosityLevelFrom($route);

        $gingerType = $route->getMatchedParam('type');

        if (! class_exists($gingerType)) {
            $consoleWriter->writeError(sprintf('Class %s not found', $gingerType));
            exit(self::INVALID_GINGER_TYPE);
        }

        try {
            $env = $this->loadEnvironment($route, $consoleWriter);

            $message = WorkflowMessage::collectDataOf($gingerType::prototype());

            $consoleWriter->writeInfo('Start workflow with message: ' . $message->getMessageName());

            $env->getWorkflowProcessor()->receiveMessage($message);

            $consoleWriter->writeSuccess('Message successfully processed');

            return 0;
        } catch (\Exception $ex) {
            $consoleWriter->writeException($ex);
            return self::MESSAGE_PROCESSING_FAILED;
        }
    }
}
 