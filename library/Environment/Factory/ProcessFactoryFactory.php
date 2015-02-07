<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 22:46
 */

namespace Prooph\Processing\Environment\Factory;

use Prooph\Processing\Environment\Environment;
use Prooph\Processing\Processor\ProcessFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ProcessFactoryFactory
 *
 * @package Prooph\Processing\Environment\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessFactoryFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var $env Environment */
        $env = $serviceLocator->get('processing.env');

        return new ProcessFactory($env->getConfig()->arrayValue('processes'));
    }
}
 