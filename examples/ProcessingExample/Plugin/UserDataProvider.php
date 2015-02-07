<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.11.14 - 20:26
 */

namespace Prooph\ProcessingExample\Plugin;

use Prooph\Processing\Environment\Connector;
use Prooph\Processing\Environment\Environment;
use Prooph\Processing\Message\AbstractWorkflowMessageHandler;
use Prooph\Processing\Message\ProcessingMessage;
use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\ProcessingExample\Type\SourceUser;

/**
 * Class UserDataProvider
 *
 * @package Prooph\ProcessingExample\Plugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class UserDataProvider extends AbstractWorkflowMessageHandler implements Connector
{
    /**
     * If workflow message handler receives a collect-data message it forwards the message to this
     * method and uses the returned ProcessingMessage as response
     *
     * @param WorkflowMessage $workflowMessage
     * @return ProcessingMessage
     */
    protected function handleCollectData(WorkflowMessage $workflowMessage)
    {
        if ($workflowMessage->payload()->getTypeClass() === 'Prooph\ProcessingExample\Type\SourceUser') {
            $userData = include __DIR__ . '/../../data/user-source-data.php';

            if (! $userData) {
                return LogMessage::logErrorMsg(
                    "Could not read user data from examples/data/user-source-data.php. Please check the permissions",
                    $workflowMessage
                );
            }

            $sourceUser = SourceUser::fromNativeValue($userData);

            return $workflowMessage->answerWith($sourceUser);
        } else {
            return LogMessage::logErrorMsg(
                sprintf(
                    '%s: Unknown type %s received', __CLASS__, $workflowMessage->payload()->getTypeClass()),
                $workflowMessage
            );
        }
    }

    /**
     * If workflow message handler receives a process-data message it forwards the message to this
     * method and uses the returned ProcessingMessage as response
     *
     * @param WorkflowMessage $workflowMessage
     * @throws \BadMethodCallException
     * @return ProcessingMessage
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
     * for the package that includes the supported Prooph\Processing\Types of the plugin
     *
     * This can be same package as of the plugin itself but be aware that this package will be installed on every node
     *
     * @example: vendor/package:2.*
     *
     * @throws \BadMethodCallException
     * @return string
     */
    public function getSupportedTypesComposerPackage()
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported by ' . __CLASS__);
    }

    /**
     * Return an array containing each supported Prooph\Processing\Type class as key
     * and all supported workflow messages for that Prooph\Processing\Type as value list
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
            'Prooph\ProcessingExample\Type\SourceUser' => ['collect-data']
        ];
    }
}
 