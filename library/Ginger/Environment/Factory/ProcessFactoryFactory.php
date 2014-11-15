<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 22:46
 */

namespace Ginger\Environment\Factory;

use Ginger\Environment\Environment;
use Ginger\Processor\ProcessFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ProcessFactoryFactory
 *
 * @package Ginger\Environment\Factory
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
        $env = $serviceLocator->get('ginger.env');

        return new ProcessFactory($env->getConfig()->arrayValue('processes'));
    }
}
 