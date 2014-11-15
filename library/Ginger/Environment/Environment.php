<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 16:58
 */

namespace Ginger\Environment;

use Codeliner\ArrayReader\ArrayReader;
use Prooph\EventStore\EventStore;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;

/**
 * Class Environment
 *
 * This is a static facade which is capable of setting up a GingerNode on basis of a configuration or
 * existing Zend\ServiceManager.
 *
 * @package Ginger\Environment
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Environment 
{
    /**
     * @var ServiceManager
     */
    private $services;

    /**
     * @var array
     */
    private static $defaultServicesConfig = [
        'factories' => [
            //Ginger specific services
            'ginger.process_repository' => 'Ginger\Environment\Factory\ProcessRepositoryFactory',
            'ginger.process_factory'    => 'Ginger\Environment\Factory\ProcessFactoryFactory',

            //ProophEventStore section
            'prooph.event_store' => 'ProophEventStoreModule\Factory\EventStoreFactory',
            'prooph.event_store.aggregate_stream_strategy'      => 'ProophEventStoreModule\Factory\AggregateStreamStrategyFactory',
            'prooph.event_store.aggregate_type_stream_strategy' => 'ProophEventStoreModule\Factory\AggregateTypeStreamStrategyFactory',
            'prooph.event_store.single_stream_strategy'         => 'ProophEventStoreModule\Factory\SingleStreamStrategyFactory',
        ],
        'abstract_factories' => [
            //ProophEventStore section
            'ProophEventStoreModule\Factory\AbstractRepositoryFactory'
        ],
    ];

    private static $defaultEnvConfig = [
        //Ginger specific env config
        'ginger' => [
            'plugins' => [
                //Plugins can either be objects or aliases
            ],
            'processes' => [
                //Process definitions @see @TODO add link to documentation
            ]
        ],
        //Global env config
        'prooph.event_store' => [
            'adapter' => [
                'type' => 'Prooph\EventStore\Adapter\InMemoryAdapter',
            ],
            'repository_map' => [
                //See ProophEventStoreModule\Factory\AbstractRepositoryFactory for details about the repository map
            ],
            'aggregate_type_stream_map' => [
                //Define a custom mapping for aggregate classes and stream names (when using a Aggregate(Type)StreamStrategy)
                //'My\Aggregate' => 'my_aggregate_stream'
            ],
            'features' => [
                //List of feature aliases
            ],
            'feature_manager' => [
                //Services config for Prooph\EventStore\Feature\FeatureManager
            ]
        ]
    ];

    /**
     * @var array[plugin_name => instance]
     */
    private $registeredPlugins = [];

    /**
     * @var ArrayReader
     */
    private $config;

    /**
     * @param null|array|ServiceManager $configurationOrServices
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return \Ginger\Environment\Environment
     */
    public static function setUp($configurationOrServices = null)
    {
        $env = null;

        if (is_null($configurationOrServices)) {
            $configurationOrServices = [];
        }

        if ($configurationOrServices instanceof ServiceManager) {
            $env = new self($configurationOrServices);
        }

        if (is_array($configurationOrServices))  {

            $servicesConfig = [];

            if (isset($configurationOrServices['services'])) {
                $servicesConfig = $configurationOrServices['services'];
                unset($configurationOrServices['services']);
            }

            $servicesConfig = new Config(ArrayUtils::merge(self::$defaultServicesConfig, $servicesConfig));

            $services = new ServiceManager($servicesConfig);

            $envConfig = ArrayUtils::merge(self::$defaultEnvConfig, $configurationOrServices);

            $services->setService('configuration', $envConfig);

            $services->setAlias('config', 'configuration');

            $env = new self($services);
        }

        if (is_null($env)) throw new \InvalidArgumentException("Ginger set up requires either a config array or a ready to use Zend\\ServiceManager");

        $env->services()->setService('ginger.env', $env);

        foreach ($env->getConfig()->arrayValue('plugins') as $plugin) {
            if (! $plugin instanceof Plugin && ! is_string($plugin)) {
                throw new \RuntimeException(sprintf(
                    "Invalid plugin detected: %s. Plugins should be instance of Ginger\\Environment\\Plugin or a ServiceManager alias that resolves to a plugin",
                    (is_object($plugin))? get_class($plugin) : gettype($plugin)
                ));
            }

            if (is_string($plugin)) {
                $plugin = $env->services->get($plugin);

                if (! $plugin instanceof Plugin) {
                    throw new \RuntimeException(sprintf(
                        "Resolved plugin for alias %s does not implement Ginger\\Environment\\Plugin"
                    ));
                }
            }

            $env->register($plugin);
        }

        return $env;
    }

    /**
     * @param ServiceManager $services
     */
    private function __construct(ServiceManager $services)
    {
        $this->services = $services;
    }

    /**
     * @return ArrayReader
     */
    public function getConfig()
    {
        if (is_null($this->config)) {
            if ($this->services->has('configuration')) {
                $config = $this->services->get('configuration');

                if (is_array($config) && array_key_exists('ginger', $config) && is_array($config['ginger'])) {
                    $this->config = new ArrayReader($config['ginger']);
                }
            }
        }

        if (is_null($this->config)) {
            $this->config = new ArrayReader(self::$defaultEnvConfig['ginger']);
        }

        return $this->config;
    }

    /**
     * @param Plugin $plugin
     * @throws \RuntimeException
     */
    public function register(Plugin $plugin)
    {
        if ($this->isRegistered($plugin->getName())) {
            throw new \RuntimeException(sprintf(
                "A plugin with name %s is already registered",
                (string)$plugin->getName()
            ));
        }

        $plugin->registerOn($this);

        $this->registeredPlugins[$plugin->getName()] = $plugin;
    }

    /**
     * @return array
     */
    public function getRegisteredPlugins()
    {
        return $this->registeredPlugins;
    }

    /**
     * @param string $pluginName
     * @return bool
     */
    public function isRegistered($pluginName)
    {
        return isset($this->registeredPlugins[(string)$pluginName]);
    }

    /**
     * @param string $pluginName
     * @throws \InvalidArgumentException
     */
    public function getPlugin($pluginName)
    {
        if (! $this->isRegistered($pluginName)) throw new \InvalidArgumentException(sprintf(
            "Unknown plugin %s requested",
            (string)$pluginName
        ));

        return $this->registeredPlugins[(string)$pluginName];
    }

    /**
     * @return EventStore
     */
    public function getEventStore()
    {
        return $this->services->get('prooph.event_store');
    }

    /**
     * @return ServiceManager
     */
    public function services()
    {
        return $this->services;
    }
}
 