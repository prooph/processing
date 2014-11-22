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
use Ginger\Processor\Definition;
use Ginger\Processor\ProophPlugin\SingleTargetMessageRouter;
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
        return (preg_match('/^ginger\.(command|event)_bus\..+/', $requestedName))? true : false;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @throws \LogicException
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        /** @var $env Environment */
        $env = $serviceLocator->get(Definition::SERVICE_ENVIRONMENT);

        $nameParts = explode('.', $requestedName);

        Assertion::min(count($nameParts), 3, sprintf("Given service bus alias %s is invalid. Format should be ginger.(command|event)_bus.[target]", $requestedName));

        $busType = $nameParts[1];

        unset($nameParts[0]);
        unset($nameParts[1]);

        $target = implode('.', $nameParts);

        $busConfig = $this->getBusConfigFor($env, $target, $busType);

        $bus = ($busType === "command_bus")? new CommandBus() : new EventBus();

        $bus->utilize($this->getForwardToMessageDispatcher());

        $bus->utilize($this->getToMessageTranslator());

        $bus->utilize($this->getHandleWorkflowMessageStrategy());

        $bus->utilize($this->getInvokeProcessorStrategy());

        $bus->utilize(new Zf2ServiceLocatorProxy($serviceLocator));

        $messageHandler = $busConfig->stringValue('message_handler', $target);

        if (! empty($messageHandler)) {
            $bus->utilize(new SingleTargetMessageRouter($messageHandler));
        } else {
            throw new \LogicException("Missing a message handler for the bus " . $requestedName);
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
     * @param string $busType
     * @return \Codeliner\ArrayReader\ArrayReader
     */
    private function getBusConfigFor(Environment $env, $target, $busType)
    {
        foreach ($env->getConfig()->arrayValue('buses') as $busConfig) {
            if (is_array($busConfig)
                && array_key_exists('targets', $busConfig)
                && is_array($busConfig['targets'])
                && array_key_exists('type', $busConfig)) {

                if (!$busConfig['type'] === $busType) continue;

                if (in_array($target, $busConfig['targets'])) {
                    return new ArrayReader($busConfig);
                }
            }
        }

        return new ArrayReader([]);
    }
}
 