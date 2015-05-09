<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.11.14 - 19:45
 */

namespace Prooph\Processing\Console;

use Prooph\Processing\Message\WorkflowMessage;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * Class CollectCommand
 *
 * @package Prooph\Processing\Console
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class CollectCommand extends AbstractCommand
{
    const INVALID_PROCESSING_TYPE = 1;

    public function __invoke(Route $route, AdapterInterface $console)
    {
        $consoleWriter = new ConsoleWriter($console);

        $consoleWriter->deriveVerbosityLevelFrom($route);

        $processingType = $route->getMatchedParam('type');

        if (! class_exists($processingType)) {
            $consoleWriter->writeError(sprintf('Class %s not found', $processingType));
            exit(self::INVALID_PROCESSING_TYPE);
        }

        try {
            $env = $this->loadEnvironment($route, $consoleWriter);

            $message = WorkflowMessage::collectDataOf($processingType::prototype(), __CLASS__, $env->getNodeName());

            $consoleWriter->writeInfo('Start workflow with message: ' . $message->messageName());

            $env->getWorkflowProcessor()->receiveMessage($message);

            $consoleWriter->writeSuccess('Message successfully processed');

            return 0;
        } catch (\Exception $ex) {
            $consoleWriter->writeException($ex);
            return self::MESSAGE_PROCESSING_FAILED;
        }
    }
}
 