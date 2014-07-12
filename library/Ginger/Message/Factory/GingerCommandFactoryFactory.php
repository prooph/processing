<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 19:35
 */

namespace Ginger\Message\Factory;

use Ginger\Message\MessageNameUtils;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class GingerCommandFactoryFactory
 *
 * @package Ginger\Message\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class GingerCommandFactoryFactory implements AbstractFactoryInterface
{
    /**
     * @var GingerCommandFactory
     */
    protected $commandFactory;

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return MessageNameUtils::isGingerCommand($requestedName);
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (is_null($this->commandFactory)) {
            $this->commandFactory = new GingerCommandFactory();
        }

        return $this->commandFactory;
    }
}
 