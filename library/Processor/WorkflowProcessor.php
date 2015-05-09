<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 16:40
 */

namespace Prooph\Processing\Processor;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Command\StartSubProcess;
use Prooph\Processing\Processor\Event\SubProcessFinished;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\EventStore\EventStore;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;

/**
 * Class WorkflowProcessor
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowProcessor
{
    /**
     * @var NodeName
     */
    private $nodeName;

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
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var bool
     */
    private $inTransaction = false;

    /**
     * @var \SplQueue
     */
    private $messageQueue;

    /**
     * @var \SplQueue
     */
    private $processorEventQueue;

    private $receivedMessageNestingLevel = 0;

    /**
     * @var EventManagerInterface
     */
    private $events;

    /**
     * @param NodeName $nodeName
     * @param EventStore $eventStore
     * @param ProcessRepository $processRepository
     * @param WorkflowEngine $workflowEngine
     * @param ProcessFactory $processFactory
     */
    public function __construct(
        NodeName $nodeName,
        EventStore $eventStore,
        ProcessRepository $processRepository,
        WorkflowEngine $workflowEngine,
        ProcessFactory $processFactory
    )
    {
        $this->nodeName            = $nodeName;
        $this->eventStore          = $eventStore;
        $this->processRepository   = $processRepository;
        $this->workflowEngine      = $workflowEngine;
        $this->processFactory      = $processFactory;
        $this->messageQueue        = new \SplQueue();
        $this->processorEventQueue = new \SplQueue();
    }

    /**
     * @param WorkflowMessage|LogMessage $message
     * @throws \RuntimeException
     */
    public function receiveMessage($message)
    {
        if ($this->inTransaction) {
            $this->messageQueue->enqueue($message);
            return;
        }

        $this->receivedMessageNestingLevel++;

        if ($message instanceof WorkflowMessage) {
            if ($processTaskListPosition = $message->processTaskListPosition()) {
                $this->continueProcessAt($processTaskListPosition, $message);
            } else {
                $this->startProcessFromMessage($message);
            }
        } elseif ($message instanceof LogMessage) {
            $this->continueProcessAt($message->processTaskListPosition(), $message);
        } elseif ($message instanceof StartSubProcess) {
            $this->startSubProcess($message);
        }elseif ($message instanceof SubProcessFinished) {
            $this->continueParentProcess($message);
        } else {
            throw new \RuntimeException(sprintf(
                "Unknown message type received: %s",
                (is_object($message))? get_class($message) : gettype($message)
            ));
        }

        $this->receivedMessageNestingLevel--;

        if (! $this->receivedMessageNestingLevel) {
            while (! $this->processorEventQueue->isEmpty()) {
                $this->events()->trigger($this->processorEventQueue->dequeue());
            }
        }
    }

    /**
     * @param WorkflowMessage $workflowMessage
     * @throws \Exception
     */
    private function startProcessFromMessage(WorkflowMessage $workflowMessage)
    {
        $process = $this->processFactory->deriveProcessFromMessage($workflowMessage, $this->nodeName);

        $this->beginTransaction();

        try {
            $process->perform($this->workflowEngine, $workflowMessage);

            $this->processRepository->add($process);

            $this->commitTransaction();

        } catch (\Exception $ex) {
            $this->rollbackTransaction();

            throw $ex;
        }

        $this->processorEventQueue->enqueue(
            new Event(
                "process_was_started_by_message",
                $this,
                [
                    "message_id" => $workflowMessage->uuid()->toString(),
                    "message_name" => $workflowMessage->messageName(),
                    "process_id" => $process->processId()->toString(),
                ]
            )
        );
    }

    /**
     * @param StartSubProcess $command
     * @throws \Exception
     */
    private function startSubProcess(StartSubProcess $command)
    {
        $subProcess = $this->processFactory->createProcessFromDefinition(
            $command->subProcessDefinition(),
            $this->nodeName,
            $command->parentTaskListPosition()
        );

        $this->beginTransaction();

        try {
            $subProcess->perform($this->workflowEngine, $command->previousWorkflowMessage());

            $this->processRepository->add($subProcess);

            $this->commitTransaction();
        } catch (\Exception $ex) {
            $this->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * @param TaskListPosition $taskListPosition
     * @param WorkflowMessage|LogMessage $lastAnswer
     * @throws \RuntimeException If process cannot be found
     * @throws \Exception If error occurs during processing
     */
    private function continueProcessAt(TaskListPosition $taskListPosition, $lastAnswer)
    {
        $process = $this->processRepository->get($taskListPosition->taskListId()->processId());

        if (is_null($process)) {
            throw new \RuntimeException(sprintf(
                "Last received message %s (%s) contains unknown processId. A process with id %s cannot be found!",
                $lastAnswer->getMessageName(),
                $lastAnswer->uuid()->toString(),
                $taskListPosition->taskListId()->processId()->toString()
            ));
        }

        $this->beginTransaction();

        try {
            $process->receiveMessage($lastAnswer, $this->workflowEngine);

            $this->commitTransaction();
        } catch (\Exception $ex) {
            $this->rollbackTransaction();

            throw $ex;
        }

        if ($process->isFinished()) {
            $this->processorEventQueue->enqueue(
                new Event(
                    'process_did_finish',
                    $this,
                    [
                        'process_id' => $process->processId()->toString(),
                        'finished_at' => $lastAnswer->createdAt()->format(\DateTime::ISO8601),
                        'succeed' => $process->isSuccessfulDone()
                    ]
                )
            );
        }

        if ($process->isSubProcess() && $process->isFinished()) {
            if ($process->isSuccessfulDone()) {
                $this->informParentProcessAboutSubProcess($process, true, $lastAnswer);
            } else {
                if (! $lastAnswer instanceof LogMessage) {
                    $lastAnswer = LogMessage::logException(
                        new \RuntimeException("Sub process failed but last message was not a LogMessage"),
                        $process->parentTaskListPosition()
                    );
                }

                if (! $lastAnswer->isError()) {
                    $lastAnswer = LogMessage::logErrorMsg($lastAnswer->technicalMsg(), $lastAnswer);
                }

                $this->informParentProcessAboutSubProcess($process, false, $lastAnswer);
            }
        }
    }

    private function informParentProcessAboutSubProcess(Process $subProcess, $succeed, $lastAnswerReceivedForSubProcess)
    {
        $event = SubProcessFinished::record(
            $this->nodeName,
            $subProcess->processId(),
            $succeed,
            $lastAnswerReceivedForSubProcess,
            $subProcess->parentTaskListPosition()
        );

        $this->workflowEngine->dispatch($event);
    }

    /**
     * @param \Prooph\Processing\Processor\Event\SubProcessFinished $subProcessFinished
     * @throws \RuntimeException
     */
    private function continueParentProcess(SubProcessFinished $subProcessFinished)
    {
        $parentProcess = $this->processRepository->get($subProcessFinished->parentTaskListPosition()->taskListId()->processId());

        if (is_null($parentProcess)) {
            throw new \RuntimeException(sprintf(
                "Sub process %s contains unknown parent processId. A process with id %s cannot be found!",
                $subProcessFinished->subProcessId()->toString(),
                $subProcessFinished->parentTaskListPosition()->taskListId()->processId()->toString()
            ));
        }

        $lastAnswerReceivedForSubProcess = $subProcessFinished->lastMessage()
            ->reconnectToProcessTask($subProcessFinished->parentTaskListPosition());

        $this->continueProcessAt($subProcessFinished->parentTaskListPosition(), $lastAnswerReceivedForSubProcess);
    }

    /**
     * @return EventManagerInterface
     */
    public function events()
    {
        if (is_null($this->events)) {
            $this->events = new EventManager([
                __CLASS__,
                'processing_workflow_processor'
            ]);
        }

        return $this->events;
    }

    private function beginTransaction()
    {
        $this->eventStore->beginTransaction();

        $this->inTransaction = true;
    }

    private function commitTransaction()
    {
        $this->eventStore->commit();

        $this->inTransaction = false;

        $this->checkMessageQueue();
    }

    private function rollbackTransaction()
    {
        if ($this->inTransaction) {
            $this->eventStore->rollback();
        }

        $this->inTransaction = false;

        $this->checkMessageQueue();
    }

    private function checkMessageQueue()
    {
        if (! $this->messageQueue->isEmpty()) {
            $this->receiveMessage($this->messageQueue->dequeue());
        }
    }
}
 
