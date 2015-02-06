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
use Ginger\Environment\ServicesAwareWorkflowEngine;
use Ginger\Message\ProophPlugin\FromGingerMessageTranslator;
use Ginger\Message\ProophPlugin\HandleWorkflowMessageInvokeStrategy;
use Ginger\Message\ProophPlugin\ToGingerMessageTranslator;
use Ginger\Processor\Definition;
use Ginger\Processor\ProophPlugin\SingleTargetMessageRouter;
use Ginger\Processor\ProophPlugin\WorkflowProcessorInvokeStrategy;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\ForwardToMessageDispatcherStrategy;
use Prooph\ServiceBus\ServiceLocator\Zf2ServiceLocatorProxy;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractChannelFactory
 *
 * This class is the fallback factory for channels. It uses the channel config of the environment
 * to select a channel for a given service name with the format ginger.(command_bus|event_bus).<target>(:::<origin>)(:::<sender>)
 *
 * @package Ginger\Environment\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AbstractChannelFactory implements AbstractFactoryInterface
{
    const CHANNEL_NAME_DELIMITER = ":::";

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

        $address = implode('.', $nameParts);

        $busConfig = $this->getChannelConfigFor($env, $address);
        $target    = $this->getTargetFromAddress($address);

        $bus = ($busType === "command_bus")? new CommandBus() : new EventBus();

        $bus->utilize($this->getForwardToMessageDispatcher());

        $bus->utilize($this->getToMessageTranslator());

        $bus->utilize($this->getHandleWorkflowMessageStrategy());

        $bus->utilize($this->getInvokeProcessorStrategy());

        $bus->utilize(new Zf2ServiceLocatorProxy($serviceLocator));

        $messageHandler = $busConfig->stringValue('message_dispatcher', $target);

        if (! empty($messageHandler)) {
            $bus->utilize(new SingleTargetMessageRouter($messageHandler));
        } else {
            throw new \LogicException("Missing a message handler for the bus " . $requestedName);
        }

        foreach ($busConfig->arrayValue('utils') as $busUtil) {
            if (is_string($busUtil)) {
                $busUtil = $serviceLocator->get($busUtil);
            }

            $bus->utilize($busUtil);
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
     * @param string $address
     * @throws \InvalidArgumentException
     * @return \Codeliner\ArrayReader\ArrayReader
     */
    private function getChannelConfigFor(Environment $env, $address)
    {
        $addressParts = explode(AbstractChannelFactory::CHANNEL_NAME_DELIMITER, $address);

        $target = $addressParts[0];
        $origin = null;
        $sender = null;
        $partsCount = count($addressParts);

        if ($partsCount == 2) {
            $origin = $addressParts[1];
            $sender = $addressParts[1];
        } elseif ($partsCount == 3) {
            $origin = $addressParts[1];
            $sender = $addressParts[2];
        } elseif ($partsCount > 3) {
            throw new \InvalidArgumentException(
                "Address part of channel name is invalid. Address can not be split into valid parts: " . $address
            );
        }

        $matchForTarget = null;
        $matchForTargetAndOrigin = null;
        $matchForTargetAndSender = null;

        foreach ($env->getConfig()->arrayValue('channels') as $channelConfig) {

            if (! is_array($channelConfig)
                || ! array_key_exists('targets', $channelConfig)
                || ! is_array($channelConfig['targets'])
                || ! in_array($target, $channelConfig['targets']))
            { continue; }

            $originShouldMatch = isset($channelConfig['origin']);
            $originDidMatch = false;

            $senderShouldMatch = isset($channelConfig['sender']);
            $senderDidMatch = false;

            if ($originShouldMatch) {
                $originDidMatch = $origin && $origin === $channelConfig['origin'];
            }

            if ($senderShouldMatch) {
                $senderDidMatch = $sender && $sender === $channelConfig['sender'];
            }

            if ($originShouldMatch && $senderShouldMatch) {
                if ($originDidMatch && $senderDidMatch) {
                    //All criteria match, so we can stop and return config
                    return new ArrayReader($channelConfig);
                }

                continue;
            }

            if ($originShouldMatch) {
                if ($originDidMatch) {
                    $matchForTargetAndOrigin = $channelConfig;
                }

                continue;
            }

            if ($senderShouldMatch) {
                if ($senderDidMatch) {
                    $matchForTargetAndSender = $channelConfig;
                }

                continue;
            }

            //Origin and sender criteria was not set for this channel config, so we can
            //memorize it for the target, if other channels don't have a better match
            $matchForTarget = $channelConfig;
        }


        //Not all criteria matched, so we need to check how good the matching was
        if ($matchForTargetAndOrigin) {
            return new ArrayReader($matchForTargetAndOrigin);
        }

        if ($matchForTargetAndSender) {
            return new ArrayReader($matchForTargetAndSender);
        }

        if ($matchForTarget) {
            return new ArrayReader($matchForTarget);
        }

        //The local channel is the default one, so if we did not find a channel for an address we assume that the target is locally available
        return new ArrayReader($env->getConfig()->arrayValue('channels.local'));
    }

    /**
     * @param string $address
     */
    private function getTargetFromAddress($address)
    {
        $addressParts = explode(self::CHANNEL_NAME_DELIMITER, $address);

        return $addressParts[0];
    }
}
 