<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.11.14 - 20:49
 */

namespace Prooph\Processing\Environment\Factory;

use Prooph\Processing\Environment\Environment;
use Prooph\Processing\Processor\Definition;
use Prooph\Processing\Processor\WorkflowProcessor;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class WorkflowProcessorFactory
 *
 * @package Prooph\Processing\Environment\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowProcessorFactory implements FactoryInterface
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
        $env = $serviceLocator->get(Definition::SERVICE_ENVIRONMENT);

        return new WorkflowProcessor(
            $env->getNodeName(),
            $env->getEventStore(),
            $env->getProcessRepository(),
            $env->getWorkflowEngine(),
            $env->getProcessFactory()
        );
    }
}
 