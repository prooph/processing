<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.11.14 - 20:26
 */

namespace GingerExample\Plugin;

use Ginger\Environment\Connector;
use Ginger\Environment\Environment;
use Ginger\Environment\Plugin;
use Ginger\Message\LogMessage;
use Ginger\Message\WorkflowMessage;
use Ginger\Message\WorkflowMessageHandler;
use GingerExample\Type\SourceUser;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;

/**
 * Class UserDataProvider
 *
 * @package GingerExample\Plugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class UserDataProvider implements WorkflowMessageHandler, Connector
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @param WorkflowMessage $aWorkflowMessage
     * @return void
     */
    public function handleWorkflowMessage(WorkflowMessage $aWorkflowMessage)
    {
        if ($aWorkflowMessage->getPayload()->getTypeClass() === 'GingerExample\Type\SourceUser') {
            $userData = include __DIR__ . '/../../data/user-source-data.php';

            if (! $userData) {
                $this->eventBus->dispatch(
                    LogMessage::logErrorMsg(
                        "Could not read user data from examples/data/user-source-data.php. Please check the permissions",
                        $aWorkflowMessage->getProcessTaskListPosition()
                    )
                );
            }

            $sourceUser = SourceUser::fromNativeValue($userData);

            $this->eventBus->dispatch($aWorkflowMessage->answerWith($sourceUser));
        } else {
            $this->eventBus->dispatch(LogMessage::logErrorMsg(
                sprintf(
                    '%s: Unknown type %s received', __CLASS__, $aWorkflowMessage->getPayload()->getTypeClass()),
                    $aWorkflowMessage->getProcessTaskListPosition()
                )
            );
        }
    }

    /**
     * Register command bus that can be used to send new commands to the workflow processor
     *
     * @param CommandBus $commandBus
     * @return void
     */
    public function useCommandBus(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * Register event bus that can be used to send events to the workflow processor
     *
     * @param EventBus $eventBus
     * @return void
     */
    public function useEventBus(EventBus $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    /**
     * Return the name of the plugin
     *
     * @return string
     */
    public function getName()
    {
        return __CLASS__;
    }

    /**
     * Register the plugin on the workflow environment
     *
     * @param Environment $workflowEnv
     * @return void
     */
    public function registerOn(Environment $workflowEnv)
    {
        //Nothing to do for this plugin
    }

    /**
     * Return a composer cli require package argument string
     * for the package that includes the supported Ginger\Types of the plugin
     *
     * This can be same package as of the plugin itself but be aware that this package will be installed on every node
     *
     * @example: vendor/package:2.*
     *
     * @return string
     */
    public function getSupportedTypesComposerPackage()
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported by ' . __CLASS__);
    }

    /**
     * Return an array containing each supported Ginger\Type class as key
     * and all supported workflow messages for that Ginger\Type as value list
     *
     * You can use the short hand of the workflow messages:
     * - collect-data   -> tells the system that the type can be collected by the plugin
     * - data-collected -> tells the system that the plugin wants to be informed when the type was collected
     * - process-data   -> tells the system that the type can be processed by the plugin
     * - data-processed -> tells the system that the plugin wants to be informed when the type was processed
     *
     * @example
     *
     * ['Vendor\Type\User' => ['collect-data', 'data-processed'], 'Vendor\Type\']
     *
     * @return array
     */
    public function getSupportedMessagesByTypeMap()
    {
        return [
            'GingerExample\Type\SourceUser' => ['collect-data']
        ];
    }
}
 