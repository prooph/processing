<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 23:55
 */

namespace Ginger\Environment\Factory;

use Assert\Assertion;
use Codeliner\ArrayReader\ArrayReader;
use Ginger\Environment\Environment;
use Ginger\Message\ProophPlugin\FromGingerMessageTranslator;
use Ginger\Message\ProophPlugin\HandleWorkflowMessageInvokeStrategy;
use Ginger\Message\ProophPlugin\ToGingerMessageTranslator;
use Ginger\Processor\ProophPlugin\WorkflowEventRouter;
use Ginger\Processor\ProophPlugin\WorkflowProcessorInvokeStrategy;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\ForwardToMessageDispatcherStrategy;
use Prooph\ServiceBus\ServiceLocator\Zf2ServiceLocatorProxy;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractServiceBusFactory
 *
 * @package Ginger\Environment\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AbstractServiceBusFactory implements AbstractFactoryInterface
{

    private $handleWorkflowMessageStrategy;

    private $invokeProcessorStrategy;

    private $toMessageTranslator;

    private $forwardToMessageDispatcher;

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
        if (! preg_match('/^ginger\.(command|event)_bus\..+/', $requestedName)) return false;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @throws \RuntimeException
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        /** @var $env Environment */
        $env = $serviceLocator->get('ginger.env');

        $nameParts = explode('.', $requestedName);

        Assertion::min(count($nameParts), 3, sprintf("Given service bus alias %s is invalid. Format should be ginger.(command|event)_bus.[target]", $requestedName));

        $busType = $nameParts[1];

        unset($nameParts[0]);
        unset($nameParts[1]);

        $target = implode('.', $nameParts);

        $busConfig = $this->getBusConfigFor($env, $target);

        $bus = ($busType === "command_bus")? new CommandBus() : new EventBus();

        $bus->utilize($this->getForwardToMessageDispatcher());

        $bus->utilize($this->getToMessageTranslator());

        $bus->utilize($this->getHandleWorkflowMessageStrategy());

        $bus->utilize($this->getInvokeProcessorStrategy());

        $bus->utilize(new Zf2ServiceLocatorProxy($serviceLocator));

        if ($workflowEventRouterConfig = $busConfig->arrayValue('workflow_event_router', null)) {
            if (! isset($workflowEventRouterConfig['target']) || ! is_string($workflowEventRouterConfig['target'])) {
                throw new \RuntimeException(sprintf(
                    "Missing target alias in WorkflowEventRouter config for service bus %s",
                    $requestedName
                ));
            }

            $bus->utilize(new WorkflowEventRouter($workflowEventRouterConfig['target']));
        }

        foreach ($busConfig->arrayValue('utils') as $utilAlias) {
            $bus->utilize($serviceLocator->get((string)$utilAlias));
        }

        return $bus;
    }

    /**
     * @return mixed
     */
    public function getForwardToMessageDispatcher()
    {
        if (is_null($this->forwardToMessageDispatcher)) {
            $this->forwardToMessageDispatcher = new ForwardToMessageDispatcherStrategy(new FromGingerMessageTranslator());
        }

        return $this->forwardToMessageDispatcher;
    }

    /**
     * @return mixed
     */
    public function getHandleWorkflowMessageStrategy()
    {
        if (is_null($this->handleWorkflowMessageStrategy)) {
            $this->handleWorkflowMessageStrategy = new HandleWorkflowMessageInvokeStrategy();
        }

        return $this->handleWorkflowMessageStrategy;
    }

    /**
     * @return mixed
     */
    public function getInvokeProcessorStrategy()
    {
        if (is_null($this->invokeProcessorStrategy)) {
            $this->invokeProcessorStrategy = new WorkflowProcessorInvokeStrategy();
        }

        return $this->invokeProcessorStrategy;
    }

    /**
     * @return mixed
     */
    public function getToMessageTranslator()
    {
        if (is_null($this->toMessageTranslator)) {
            $this->toMessageTranslator = new ToGingerMessageTranslator();
        }
        return $this->toMessageTranslator;
    }

    /**
     * @param Environment $env
     * @param string $target
     * @return \Codeliner\ArrayReader\ArrayReader
     */
    private function getBusConfigFor(Environment $env, $target)
    {
        foreach ($env->getConfig()->arrayValue('ginger.buses') as $busConfig) {
            if (is_array($busConfig) && array_key_exists('targets', $busConfig) && is_array($busConfig['targets'])) {

                if (in_array($target, $busConfig['targets'])) {
                    return new ArrayReader($busConfig);
                }
            }
        }

        return new ArrayReader([]);
    }
}
 