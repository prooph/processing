<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.07.14 - 15:35
 */

namespace Ginger\Message;
use Ginger\Processor\WorkflowEngine;

/**
 * Interface WorkflowMessageHandler
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface WorkflowMessageHandler 
{
    /**
     * @param WorkflowMessage $workflowMessage
     * @return void
     */
    public function handleWorkflowMessage(WorkflowMessage $workflowMessage);

    /**
     * @param WorkflowEngine $workflowEngine
     * @return void
     */
    public function useWorkflowEngine(WorkflowEngine $workflowEngine);
}
 