<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 21:49
 */

namespace Ginger\Environment\Factory;

use Ginger\Processor\ProcessRepository;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ProcessRepositoryFactory
 *
 * @package Ginger\Environment\ProophEventStore
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessRepositoryFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new ProcessRepository($serviceLocator->get('prooph.event_store'));
    }
}
 