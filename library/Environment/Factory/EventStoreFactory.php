<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.01.15 - 22:28
 */

namespace Prooph\Processing\Environment\Factory;

use Prooph\Common\ServiceLocator\ZF2\Zf2ServiceManagerProxy;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\FeatureManager;
use Prooph\EventStore\Feature\ZF2FeatureManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class EventStoreFactory
 *
 * @package Prooph\Processing\Environment\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class EventStoreFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        //Set up is ported from https://github.com/prooph/ProophEventStoreModule/blob/master/src/ProophEventStoreModule/Factory/EventStoreFactory.php
        $config = $serviceLocator->get("configuration");
        if (! isset($config['prooph.event_store'])) {
            throw new \InvalidArgumentException("Missing key prooph.event_store in application configuration");
        }
        $config = $config['prooph.event_store'];
        if (! isset($config['adapter'])) {
            throw new \InvalidArgumentException("Missing adapter configuration in prooph.event_store configuration");
        }

        $adapterType = isset($config['adapter']["type"])? $config['adapter']["type"] : 'Prooph\EventStore\Adapter\InMemoryAdapter';
        $adapterOptions = isset($config['adapter']["options"])? $config['adapter']["options"] : [];

        if ( $adapterType == 'Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter'
            && isset($adapterOptions['zend_db_adapter'])
            && is_string($adapterOptions['zend_db_adapter'])) {
            $config['adapter']['options']['zend_db_adapter'] = $serviceLocator->get($adapterOptions['zend_db_adapter']);
        }

        $featureManagerConfig = null;

        if (isset($config['feature_manager'])) {
            $featureManagerConfig = new Config($config['feature_manager']);
            unset($config['feature_manager']);
        }

        $esConfiguration = new Configuration($config);
        $featureManager = new ZF2FeatureManager($featureManagerConfig);
        $featureManager->setServiceLocator($serviceLocator);
        $esConfiguration->setFeatureManager(Zf2ServiceManagerProxy::proxy($featureManager));

        return new EventStore($esConfiguration);
    }
}
 