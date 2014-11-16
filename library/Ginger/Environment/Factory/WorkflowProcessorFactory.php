<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.11.14 - 20:49
 */

namespace Ginger\Environment\Factory;

use Ginger\Environment\Environment;
use Ginger\Processor\Definition;
use Ginger\Processor\WorkflowProcessor;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class WorkflowProcessorFactory
 *
 * @package Ginger\Environment\Factory
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
            $env->getEventStore(),
            $env->getProcessRepository(),
            $env->getWorkflowEngine(),
            $env->getProcessFactory()
        );
    }
}
 