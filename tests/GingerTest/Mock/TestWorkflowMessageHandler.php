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

namespace GingerTest\Mock;

use Ginger\Message\AbstractWorkflowMessageHandler;
use Ginger\Message\WorkflowMessage;
use Ginger\Message\WorkflowMessageHandler;
use Ginger\Processor\WorkflowEngine;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;

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
     * @var WorkflowMessage
     */
    protected $nextAnswer;

    /**
     * @var WorkflowEngine
     */
    protected $workflowEngine;


    public function reset()
    {
        $this->lastWorkflowMessage = null;
    }

    /**
     * @param WorkflowMessage $aWorkflowMessage
     * @return void
     */
    public function handleWorkflowMessage(WorkflowMessage $aWorkflowMessage)
    {
        $this->lastWorkflowMessage = $aWorkflowMessage;

        if ($this->nextAnswer && $this->workflowEngine)
        {
            if (is_null($this->nextAnswer->processTaskListPosition())) {
                $this->nextAnswer->connectToProcessTask($aWorkflowMessage->processTaskListPosition());
            }

            $this->workflowEngine->dispatch($this->nextAnswer);

            $this->nextAnswer = null;
        }
    }

    /**
     * @return WorkflowMessage
     */
    public function lastWorkflowMessage()
    {
        return $this->lastWorkflowMessage;
    }

    public function setNextAnswer(WorkflowMessage $workflowMessage)
    {
        $this->nextAnswer = $workflowMessage;
    }

    /**
     * @param WorkflowEngine $workflowEngine
     * @throws \BadMethodCallException
     * @return void
     */
    public function useWorkflowEngine(WorkflowEngine $workflowEngine)
    {
        $this->workflowEngine = $workflowEngine;
    }

    /**
     * @return WorkflowEngine
     */
    public function getWorkflowEngine()
    {
        return $this->workflowEngine;
    }
}
 