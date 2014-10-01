<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.09.14 - 21:22
 */

namespace Ginger\Processor;

use Ginger\Message\WorkflowMessage;
use Ginger\Message\WorkflowMessageHandler;

/**
 * Interface Process
 *
 * Defines basic methods each process should provide
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface Process extends WorkflowMessageHandler
{
    /**
     * Creates new process from given config
     *
     * @param array $config
     * @return static
     */
    public static function setUpNew(array $config);

    /**
     * The ProcessId must be derived from the uuid of the initial WorkflowMessage
     *
     * The initial message is either received by method receiveMessage or generated internally if
     * the first message is a command.
     *
     * As long as the process has not received a message it has no ProcessId
     *
     * @return null|ProcessId
     */
    public function processId();

    /**
     * A Process can start or continue with the next step after it has received a message
     *
     * @param WorkflowMessage $workflowMessage
     * @return void
     */
    public function receiveMessage(WorkflowMessage $workflowMessage);

    /**
     * Perform next step of the process with the help of given WorkflowEngine
     *
     * @param WorkflowEngine $workflowEngine
     * @return void
     */
    public function performNextStep(WorkflowEngine $workflowEngine);

    /**
     * @return bool
     */
    public function isFinished();

    /**
     * @return bool
     */
    public function isChildProcess();

    /**
     * @return null|ProcessId
     */
    public function getParentProcessId();
}
 