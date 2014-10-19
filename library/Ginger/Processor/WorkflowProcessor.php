<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 16:40
 */

namespace Ginger\Processor;

use Ginger\Message\WorkflowMessage;
use Prooph\EventStore\EventStore;

/**
 * Class WorkflowProcessor
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowProcessor 
{
    /**
     * @var ProcessRepository
     */
    private $processRepository;

    /**
     * @var WorkflowEngine
     */
    private $workflowEngine;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @param EventStore $eventStore
     * @param ProcessRepository $processRepository
     * @param WorkflowEngine $workflowEngine
     */
    public function __construct(EventStore $eventStore, ProcessRepository $processRepository, WorkflowEngine $workflowEngine)
    {
        $this->eventStore        = $eventStore;
        $this->processRepository = $processRepository;
        $this->workflowEngine    = $workflowEngine;
    }

    public function receiveMessage(WorkflowMessage $workflowMessage)
    {

    }

    private function startProcessFromMessage(WorkflowMessage $workflowMessage)
    {

    }
}
 