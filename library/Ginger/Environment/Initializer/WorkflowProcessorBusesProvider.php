<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.11.14 - 19:53
 */

namespace Ginger\Environment\Initializer;

use Ginger\Message\WorkflowMessageHandler;
use Ginger\Processor\Definition;
use Zend\ServiceManager\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class WorkflowProcessorBusesProvider
 *
 * @package GingerTest\Environment\Initializer
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowProcessorBusesProvider implements InitializerInterface
{
    /**
     * Initialize
     *
     * @param $instance
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function initialize($instance, ServiceLocatorInterface $serviceLocator)
    {
        if ($instance instanceof WorkflowMessageHandler) {
            $instance->useCommandBus(
                $serviceLocator->get(Definition::SERVICE_ENVIRONMENT)
                    ->getWorkflowEngine()->getCommandBusFor(Definition::SERVICE_WORKFLOW_PROCESSOR)
            );

            $instance->useEventBus(
                $serviceLocator->get(Definition::SERVICE_ENVIRONMENT)
                    ->getWorkflowEngine()->getEventBusFor(Definition::SERVICE_WORKFLOW_PROCESSOR)
            );
        }
    }
}
 