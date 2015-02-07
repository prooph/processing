<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 21:49
 */

namespace Prooph\Processing\Environment\Factory;

use Prooph\Processing\Processor\ProcessRepository;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ProcessRepositoryFactory
 *
 * @package Prooph\Processing\Environment\ProophEventStore
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
 