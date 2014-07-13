<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.07.14 - 18:57
 */

namespace GingerTest\Type\Mock;

use Ginger\Message\WorkflowMessage;
use Ginger\Message\WorkflowMessageHandler;

/**
 * Class TestWorkflowMessageHandler
 *
 * @package GingerTest\Type\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TestWorkflowMessageHandler implements WorkflowMessageHandler
{
    /**
     * @var WorkflowMessage
     */
    protected $lastWorkflowMessage;

    /**
     * @param WorkflowMessage $aWorkflowMessage
     * @return void
     */
    public function handleWorkflowMessage(WorkflowMessage $aWorkflowMessage)
    {
        $this->lastWorkflowMessage = $aWorkflowMessage;
    }

    /**
     * @return WorkflowMessage
     */
    public function lastWorkflowMessage()
    {
        return $this->lastWorkflowMessage;
    }
}
 