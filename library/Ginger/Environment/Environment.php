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
use Ginger\Processor\Definition;
use Ginger\Processor\ProcessFactory;
use Ginger\Processor\ProcessRepository;
use Ginger\Processor\WorkflowEngine;
use Ginger\Processor\WorkflowProcessor;
use GingerTest\Environment\Initializer\WorkflowProcessorBusesProvider;
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
            Definition::SERVICE_WORKFLOW_PROCESSOR       => 'Ginger\Environment\Factory\WorkflowProcessorFactory',
            Definition::SERVICE_PROCESS_FACTORY          => 'Ginger\Environment\Factory\ProcessFactoryFactory',
            Definition::SERVICE_PROCESS_REPOSITORY       => 'Ginger\Environment\Factory\ProcessRepositoryFactory',

            //ProophEventStore section
            'prooph.event_store' => 'ProophEventStoreModule\Factory\EventStoreFactory',
            'prooph.event_store.aggregate_stream_strategy'      => 'ProophEventStoreModule\Factory\AggregateStreamStrategyFactory',
            'prooph.event_store.aggregate_type_stream_strategy' => 'ProophEventStoreModule\Factory\AggregateTypeStreamStrategyFactory',
            'prooph.event_store.single_stream_strategy'         => 'ProophEventStoreModule\Factory\SingleStreamStrategyFactory',
        ],
        'abstract_factories' => [
            //ProophEventStore section
            'ProophEventStoreModule\Factory\AbstractRepositoryFactory',
            //ProophServiceBus section
            'Ginger\Environment\Factory\AbstractServiceBusFactory'
        ],
    ];

    private static $defaultEnvConfig = [
        //Ginger specific env config
        'ginger' => [
            'plugins' => [
                //Plugins can either be objects or aliases resolvable by the ServiceManager
            ],
            'processes' => [
                //Process definitions @see @TODO add link to documentation
            ],
            'buses' => [
                //You can provide different configurations for buses responsible for different targets
                'workflow_processor_command_bus' => [
                    'type' => Definition::ENV_CONFIG_TYPE_COMMAND_BUS, //Defines the type of the bus either command_bus or event_bus
                    'targets' => [
                        //List of targets for which the bus is responsible for
                        Definition::SERVICE_WORKFLOW_PROCESSOR
                    ],
                    'message_handler' => Definition::SERVICE_WORKFLOW_PROCESSOR, //Set the alias of the responsible message handler for all messages dispatched with this bus
                                                                                       //If the target is located on the same node this will be the same alias as for the target itself
                                                                                       //If the target is located on a remote node this will be an alias for a message dispatcher
                    'utils' => [
                        //List of additional bus plugins, can be aliases resolvable by the ServiceManager
                    ]
                ],
                'workflow_processor_event_bus' => [
                    'type' => Definition::ENV_CONFIG_TYPE_EVENT_BUS,
                    'targets' => [
                        Definition::SERVICE_WORKFLOW_PROCESSOR
                    ],
                    'message_handler' => Definition::SERVICE_WORKFLOW_PROCESSOR,
                    'utils' => []
                ]
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
                //List of event store features,must be aliases resolvable by Prooph\EventStore\Feature\FeatureManager
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
     * @var WorkflowEngine
     */
    private $workflowEngine;

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

            if (! $configurationOrServices->has(Definition::SERVICE_WORKFLOW_PROCESSOR)) {
                $servicesConfig = new Config(self::$defaultServicesConfig);

                $servicesConfig->configureServiceManager($configurationOrServices);
            }

            $env = new self($configurationOrServices);

            if ($env->services()->has('configuration')) {
                $configurationOrServices = $env->services()->get('configuration');
            } else {
                $configurationOrServices = [];
            }
        } elseif (is_array($configurationOrServices))  {

            $servicesConfig = [];

            if (isset($configurationOrServices['services'])) {
                $servicesConfig = $configurationOrServices['services'];
                unset($configurationOrServices['services']);
            }

            $servicesConfig = new Config(ArrayUtils::merge(self::$defaultServicesConfig, $servicesConfig));

            $env = new self(new ServiceManager($servicesConfig));
        }

        if (is_null($env)) throw new \InvalidArgumentException("Ginger set up requires either a config array or a ready to use Zend\\ServiceManager");

        $envConfig = ArrayUtils::merge(self::$defaultEnvConfig, $configurationOrServices);

        $orgAllowOverride = $env->services()->getAllowOverride();

        $env->services()->setAllowOverride(true);

        $env->services()->setService('configuration', $envConfig);

        $env->services()->setAlias('config', 'configuration');

        $env->services()->setService(Definition::SERVICE_ENVIRONMENT, $env);

        $env->services()->setAllowOverride($orgAllowOverride);

        $env->workflowEngine = new ServicesAwareWorkflowEngine($env->services());

        $env->services()->addInitializer(new WorkflowProcessorBusesProvider());

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
     * @return WorkflowEngine
     */
    public function getWorkflowEngine()
    {
        return $this->workflowEngine;
    }

    /**
     * @return WorkflowProcessor
     */
    public function getWorkflowProcessor()
    {
        return $this->services()->get(Definition::SERVICE_WORKFLOW_PROCESSOR);
    }

    /**
     * @return ProcessFactory
     */
    public function getProcessFactory()
    {
        return $this->services()->get(Definition::SERVICE_PROCESS_FACTORY);
    }

    /**
     * @return ProcessRepository
     */
    public function getProcessRepository()
    {
        return $this->services()->get(Definition::SERVICE_PROCESS_REPOSITORY);
    }

    /**
     * @return ServiceManager
     */
    public function services()
    {
        return $this->services;
    }
}
 