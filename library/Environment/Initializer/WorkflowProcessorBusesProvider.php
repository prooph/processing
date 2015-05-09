<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.11.14 - 19:53
 */

namespace Prooph\Processing\Environment\Initializer;

use Prooph\Common\ServiceLocator\ServiceInitializer;
use Prooph\Common\ServiceLocator\ServiceLocator;
use Prooph\Processing\Environment\Environment;
use Prooph\Processing\Message\WorkflowMessageHandler;
use Prooph\Processing\Processor\Definition;

/**
 * Class WorkflowProcessorBusesProvider
 * The workflow processor buses provider automatically injects the local workflow processor message buses
 * when it detects a workflow message handler.
 *
 * @package Prooph\ProcessingTest\Environment\Initializer
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowProcessorBusesProvider implements ServiceInitializer
{
    /**
     * Initialize
     *
     * @param $instance
     * @param ServiceLocator $serviceLocator
     * @return mixed
     */
    public function initialize($instance, ServiceLocator $serviceLocator)
    {
        if ($instance instanceof WorkflowMessageHandler) {
            /** @var $env Environment */
            $env = $serviceLocator->get(Definition::SERVICE_ENVIRONMENT);

            $instance->useWorkflowEngine($env->getWorkflowEngine());
        }
    }
}
 