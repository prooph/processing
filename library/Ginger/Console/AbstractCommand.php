<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.11.14 - 19:12
 */

namespace Ginger\Console;
use Ginger\Environment\Environment;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Definition;
use Zend\Stdlib\ArrayUtils;
use ZF\Console\Route;

/**
 * Class AbstractCommand
 *
 * @package Ginger\Console
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
        $configPath = $route->getMatchedParam('config-file', getcwd() . DIRECTORY_SEPARATOR . 'ginger.config.php');

        $additionalConfig = $route->getMatchedParam('config', json_encode([]));

        $additionalConfig = json_decode($additionalConfig, true);

        if (is_null($additionalConfig)) {
            $consoleWriter->writeError("Provided config is not a valid json string");
            $consoleWriter->writeError(json_last_error_msg());
            return self::INVALID_CONFIG;
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

        $env->getEventStore()->getPersistenceEvents()->attach(new PersistedEventsConsoleWriter($consoleWriter));

        return $env;
    }
}
 