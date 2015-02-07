<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.07.14 - 15:35
 */

namespace Prooph\Processing\Message;
use Prooph\Processing\Processor\WorkflowEngine;

/**
 * Interface WorkflowMessageHandler
 *
 * @package Prooph\Processing\Message
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
 