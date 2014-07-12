<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 20:24
 */

namespace Ginger\Message\Factory;

use Ginger\Message\MessageNameUtils;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class GingerMessageFactoryFactory
 *
 * @package Ginger\Message\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class GingerMessageFactoryFactory implements AbstractFactoryInterface
{
    /**
     * @var GingerMessageFactory
     */
    protected $messageFactory;

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
        return MessageNameUtils::isGingerMessage($requestedName);
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
        if (is_null($this->messageFactory)) {
            $this->messageFactory = new GingerMessageFactory();
        }

        return $this->messageFactory;
    }
}
 