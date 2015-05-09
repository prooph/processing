<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 16:58
 */

namespace Prooph\Processing\Environment;

use Codeliner\ArrayReader\ArrayReader;
use Prooph\Common\ServiceLocator\ServiceLocator;
use Prooph\Common\ServiceLocator\ZF2\Zf2ServiceManagerProxy;
use Prooph\Processing\Processor\Definition;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessFactory;
use Prooph\Processing\Processor\ProcessRepository;
use Prooph\Processing\Processor\WorkflowEngine;
use Prooph\Processing\Processor\WorkflowProcessor;
use Prooph\Processing\Environment\Initializer\WorkflowProcessorBusesProvider;
use Prooph\EventStore\EventStore;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;

/**
 * Class Environment
 *
 * This is a static facade which is capable of setting up a ProcessingNode on basis of a configuration or
 * existing Zend\ServiceManager.
 *
 * @package Prooph\Processing\Environment
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Environment 
{
    /**
     * @var ServiceLocator
     */
    private $services;

    /**
     * @var array
     */
    private static $defaultServicesConfig = [
        'factories' => [
            //Processing specific services
            Definition::SERVICE_WORKFLOW_PROCESSOR       => Factory\WorkflowProcessorFactory::class,
            Definition::SERVICE_PROCESS_FACTORY          => Factory\ProcessFactoryFactory::class,
            Definition::SERVICE_PROCESS_REPOSITORY       => Factory\ProcessRepositoryFactory::class,

            //ProophEventStore section
            'prooph.event_store' => Factory\EventStoreFactory::class,
        ],
        'abstract_factories' => [
            //ProophServiceBus section
            Factory\AbstractChannelFactory::class
        ],
    ];

    private static $defaultEnvConfig = [
        //Processing specific env config
        'processing' => [
            'node_name' => Definition::DEFAULT_NODE_NAME, //The node name identifies the system running a processing processor and is used as target for the local workflow processor
            'plugins' => [
                //Plugins can either be objects or aliases resolvable by the ServiceManager
            ],
            'processes' => [
                //Process definitions @see @TODO add link to documentation
            ],
            'channels' => [
                //You can provide different configurations for channels responsible for different targets
                //A channel is automatically split into a command bus and an event bus and some standard utilities are attached to
                //each bus {@see Prooph\Processing\Environment\Factory\AbstractChannelFactory} for details
                'local' => [
                    'targets' => [
                        //List of targets for which the channel is responsible for
                        //Note: The Environment sets the defined node name as target for the local channel in the set up routine
                        //'target_1_alias', 'target_2_alias', ...
                    ],
                    'utils' => [
                        //List of additional channel or ProophServiceBus plugins, can be aliases resolvable by the ServiceManager
                    ],
                    //Set the alias of a message dispatcher that dispatches all messages send over the channel to a remote system
                    //This is an optional config, because it is only required for remote channels
                    //'message_dispatcher' => 'alias_of_message_dispatcher',

                    //Optional filter criteria. If it is present it needs to match so that the workflow engine selects
                    //this channel. The origin is provided by the processing message and the sender
                    //can be given as optional argument to the WorkflowEngine::dispatch() method
                    //'origin' => '<unique name of origin>',
                    //'sender' => '<unique name of sender>',
                ],
            ],
        ],
        //Global env config
        'prooph.event_store' => [
            'adapter' => [
                'type' => 'Prooph\EventStore\Adapter\InMemoryAdapter',
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
     * @return \Prooph\Processing\Environment\Environment
     */
    public static function setUp($configurationOrServices = null)
    {
        $env = null;

        //If neither a config array nor a ServiceManager is given, we initialize the variable with an empty array
        //Later in the set up the default config is merged with this empty array
        if (is_null($configurationOrServices)) {
            $configurationOrServices = [];
        }

        //Initialize the Environment
        if ($configurationOrServices instanceof ServiceManager) {

            //We assume that when the workflow processor service definition is missing
            //then the other services related to the workflow environment are also missing
            if (! $configurationOrServices->has(Definition::SERVICE_WORKFLOW_PROCESSOR)) {
                $servicesConfig = new Config(self::$defaultServicesConfig);

                $servicesConfig->configureServiceManager($configurationOrServices);
            }

            $env = new self(Zf2ServiceManagerProxy::proxy($configurationOrServices));

            //Check if the provided ServiceManager already has a configuration service available
            //so we make sure that we won't override it later but just merge it with the default environment config
            if ($env->services()->has('configuration')) {
                $configurationOrServices = $env->services()->get('configuration');
            } else {
                $configurationOrServices = [];
            }
        } elseif (is_array($configurationOrServices))  {

            //No external ServiceManager given, so we set up a new one
            $servicesConfig = [];

            if (isset($configurationOrServices['services'])) {
                $servicesConfig = $configurationOrServices['services'];
                unset($configurationOrServices['services']);
            }

            $servicesConfig = new Config(ArrayUtils::merge(self::$defaultServicesConfig, $servicesConfig));

            $env = new self(Zf2ServiceManagerProxy::proxy(new ServiceManager($servicesConfig)));
        }

        //This should never happen, but if for whatever reason a wrong $configurationOrServices was passed to the set up
        //we stop the process here
        if (is_null($env)) throw new \InvalidArgumentException("Processing set up requires either a config array or a ready to use Zend\\ServiceManager");

        //We proceed with merging and preparing the environment configuration
        $envConfig = ArrayUtils::merge(self::$defaultEnvConfig, $configurationOrServices);

        //The environment node name is used as target for the local channel, the config needs to be adapted accordingly.
        $envConfigReader = new ArrayReader($envConfig);

        $nodeName = $envConfigReader->stringValue('processing.node_name', Definition::DEFAULT_NODE_NAME);

        $localChannelTargets = $envConfigReader->arrayValue('processing.channels.local.targets');

        if (! in_array($nodeName, $localChannelTargets)) {
            $envConfig = ArrayUtils::merge(
                $envConfig,
                [
                    'processing' => [
                        'channels' => [
                            'local' => [
                                'targets' => [$nodeName]
                            ]
                        ]
                    ]
                ]
            );
        }

        $env->services()->set('configuration', $envConfig, true);

        $env->services()->setAlias('config', 'configuration');

        $env->services()->set(Definition::SERVICE_ENVIRONMENT, $env, true);

        //The node name is used as message bus target to address the workflow processor of the current environment
        //We alias the workflow processor service with the node name to ensure that the target can be resolved
        $env->services()->setAlias($nodeName, Definition::SERVICE_WORKFLOW_PROCESSOR);

        //The environment component ships with an own workflow engine implementation that uses the ServiceManager
        //to resolve the required message buses for the various targets
        $env->workflowEngine = new ServicesAwareWorkflowEngine($env->services());

        //Add an initializer which injects the local processor message buses whenever a workflow message handler
        //is requested from the ServiceManager
        $env->services()->addInitializer(new WorkflowProcessorBusesProvider());

        //After the set up routine is finished the plugin mechanism can be triggered
        foreach ($env->getConfig()->arrayValue('plugins') as $plugin) {
            if (! $plugin instanceof Plugin && ! is_string($plugin)) {
                throw new \RuntimeException(sprintf(
                    "Invalid plugin detected: %s. Plugins should be instance of Prooph\\Processing\\Environment\\Plugin or a ServiceManager alias that resolves to a plugin",
                    (is_object($plugin))? get_class($plugin) : gettype($plugin)
                ));
            }

            if (is_string($plugin)) {
                $plugin = $env->services->get($plugin);

                if (! $plugin instanceof Plugin) {
                    throw new \RuntimeException(sprintf(
                        "Resolved plugin for alias %s does not implement Prooph\\Processing\\Environment\\Plugin"
                    ));
                }
            }

            $env->register($plugin);
        }

        return $env;
    }

    /**
     * @param ServiceLocator $services
     */
    private function __construct(ServiceLocator $services)
    {
        $this->services = $services;
    }

    /**
     * @return NodeName
     */
    public function getNodeName()
    {
        return NodeName::fromString($this->getConfig()->stringValue('node_name'));
    }

    /**
     * @return ArrayReader
     */
    public function getConfig()
    {
        if (is_null($this->config)) {
            if ($this->services->has('configuration')) {
                $config = $this->services->get('configuration');

                if (is_array($config) && array_key_exists('processing', $config) && is_array($config['processing'])) {
                    $this->config = new ArrayReader($config['processing']);
                }
            }
        }

        if (is_null($this->config)) {
            $this->config = new ArrayReader(self::$defaultEnvConfig['processing']);
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
     * @return ServiceLocator
     */
    public function services()
    {
        return $this->services;
    }
}
 