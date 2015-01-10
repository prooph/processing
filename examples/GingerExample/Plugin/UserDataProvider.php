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
use Ginger\Message\AbstractWorkflowMessageHandler;
use Ginger\Message\GingerMessage;
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
class UserDataProvider extends AbstractWorkflowMessageHandler implements Connector
{
    /**
     * If workflow message handler receives a collect-data message it forwards the message to this
     * method and uses the returned GingerMessage as response
     *
     * @param WorkflowMessage $workflowMessage
     * @return GingerMessage
     */
    protected function handleCollectData(WorkflowMessage $workflowMessage)
    {
        if ($workflowMessage->payload()->getTypeClass() === 'GingerExample\Type\SourceUser') {
            $userData = include __DIR__ . '/../../data/user-source-data.php';

            if (! $userData) {
                return LogMessage::logErrorMsg(
                    "Could not read user data from examples/data/user-source-data.php. Please check the permissions",
                    $workflowMessage->processTaskListPosition()
                );
            }

            $sourceUser = SourceUser::fromNativeValue($userData);

            return $workflowMessage->answerWith($sourceUser);
        } else {
            return LogMessage::logErrorMsg(
                sprintf(
                    '%s: Unknown type %s received', __CLASS__, $workflowMessage->payload()->getTypeClass()),
                $workflowMessage->processTaskListPosition()
            );
        }
    }

    /**
     * If workflow message handler receives a process-data message it forwards the message to this
     * method and uses the returned GingerMessage as response
     *
     * @param WorkflowMessage $workflowMessage
     * @throws \BadMethodCallException
     * @return GingerMessage
     */
    protected function handleProcessData(WorkflowMessage $workflowMessage)
    {
        throw new \BadMethodCallException(__METHOD__ . " not supported by " . __CLASS__);
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
 