<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.11.14 - 19:12
 */

namespace Prooph\Processing\Console;

use Prooph\Processing\Environment\Environment;
use Zend\Stdlib\ArrayUtils;
use ZF\Console\Route;

/**
 * Class AbstractCommand
 *
 * @package Prooph\Processing\Console
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AbstractCommand 
{
    const MESSAGE_PROCESSING_FAILED = 500;

    /**
     * @param \ZF\Console\Route $route
     * @param ConsoleWriter $consoleWriter
     * @return Environment
     */
    protected function loadEnvironment(Route $route, ConsoleWriter $consoleWriter)
    {
        $configPath = $route->getMatchedParam('config-file', getcwd() . DIRECTORY_SEPARATOR . 'processing.config.php');

        $additionalConfig = $route->getMatchedParam('config', json_encode([]));

        $additionalConfig = json_decode($additionalConfig, true);

        if (is_null($additionalConfig)) {
            $consoleWriter->writeError("Provided config is not a valid json string");
            $consoleWriter->writeError(json_last_error_msg());
            return self::MESSAGE_PROCESSING_FAILED;
        }

        if (file_exists($configPath)) {

            $config = include $configPath;

            $consoleWriter->writeInfo('Config loaded from ' . $configPath);

        } elseif (file_exists($configPath . '.dist')) {

            $config = include $configPath . '.dist';

            $consoleWriter->writeInfo('Config loaded from ' . $configPath);

        } else {

            $consoleWriter->writeInfo('No config file specified.');

            if (empty($additionalConfig)) {
                $consoleWriter->writeInfo('Falling back to default config');
            } else {
                $consoleWriter->writeInfo('Using config from argument');
            }

            return $additionalConfig;
        }

        $config = ArrayUtils::merge($config, $additionalConfig);

        $env = Environment::setUp($config);

        $env->getEventStore()->getActionEventDispatcher()->attachListenerAggregate(new PersistedEventsConsoleWriter($consoleWriter));

        return $env;
    }
}
 