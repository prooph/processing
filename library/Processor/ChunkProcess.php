<?php
/*
 * This file is part of the prooph processing framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 13.03.15 - 22:48
 */

namespace Prooph\Processing\Processor;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\Task\Event\LogMessageReceived;
use Prooph\Processing\Processor\Task\Event\MultiPerformTaskFailed;
use Prooph\Processing\Processor\Task\Event\MultiPerformTaskSucceed;
use Prooph\Processing\Processor\Task\Event\MultiPerformTaskWasStarted;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsDone;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsFailed;
use Prooph\Processing\Processor\Task\Event\TaskEntryMarkedAsRunning;
use Prooph\Processing\Processor\Task\RunSubProcess;
use Prooph\Processing\Processor\Task\TaskListEntry;
use Prooph\Processing\Type\CollectionType;

/**
 * Class ChunkProcess
 *
 * The chunk process can handle collecting data in chunks if some pre conditions are met.
 * In the first step a collect data message should be sent to a workflow message handler which either
 * loads the first chunk or performs a count on the requested collection (see constants for details).
 * The latter should be preferred if the handler is able to handle such a metadata instruction, otherwise
 * the first chunk will be requested twice. This is related to the internal perform of the chunk process, because
 * for each chunk a separate sub process is started.
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class ChunkProcess extends Process
{
    /**
     * Message Metadata key
     *
     * Defines the start of a chunk.
     * This key needs to be set on the start message.
     */
    const META_OFFSET = "offset";

    /**
     * Message Metadata key
     *
     * Defines the end of a chunk.
     * This key needs to be set on the start message.
     */
    const META_LIMIT = "limit";

    /**
     * Message Metadata key
     *
     * Defines the total size of the collection
     * This key needs to be set by the workflow message handler which is responsible for collecting the data.
     */
    const META_TOTAL_ITEMS = "total_items";

    /**
     * Message Metadata key
     *
     * Can be used to tell a workflow message handler that it should only perform a count on the collection
     * but actually load no data.
     * This key is automatically removed by the chunk process when starting the collection of chunks.
     */
    const META_COUNT_ONLY = "count_only";

    /**
     * @var bool
     */
    private $processingCollection = false;

    private $performedTasks = 0;

    private $performedSuccessful = 0;

    private $performedWithError = 0;

    /**
     * @param LogMessage|WorkflowMessage $message
     * @param WorkflowEngine $workflowEngine
     */
    public function receiveMessage($message, WorkflowEngine $workflowEngine)
    {
        if (! $this->taskList->isStarted()) {
            parent::receiveMessage($message, $workflowEngine);
            return;
        }

        $this->assertTaskEntryExists($message->processTaskListPosition());

        $taskListEntry = $this->taskList->getTaskListEntryAtPosition($message->processTaskListPosition());

        if ($message instanceof WorkflowMessage) {
            if (! MessageNameUtils::isProcessingEvent($message->messageName())) {

                $this->receiveMessage(
                    LogMessage::logWrongMessageReceivedFor(
                        $taskListEntry->task(),
                        $taskListEntry->taskListPosition(),
                        $message
                    ),
                    $workflowEngine
                );

                return;
            }

            $this->recordThat(MultiPerformTaskSucceed::at($taskListEntry->taskListPosition()));
        }

        if ($message instanceof LogMessage) {

            $this->recordThat(LogMessageReceived::record($message));

            if ($message->isError()) {
                $this->recordThat(MultiPerformTaskFailed::at($taskListEntry->taskListPosition()));
            }
        }

        $this->checkFinished($taskListEntry);
    }

    /**
     * Start or continue the process with the help of given WorkflowEngine and optionally with given WorkflowMessage
     *
     * @param WorkflowEngine $workflowEngine
     * @param WorkflowMessage $workflowMessage
     * @throws \RuntimeException
     * @return void
     */
    public function perform(WorkflowEngine $workflowEngine, WorkflowMessage $workflowMessage = null)
    {
        $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();

        if (is_null($taskListEntry)) {
            throw new \RuntimeException('ChunkProcess::perform was called but there are no tasks configured!');
        }

        if (is_null($workflowMessage)) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
            $this->receiveMessage(
                LogMessage::logNoMessageReceivedFor($taskListEntry->task(), $taskListEntry->taskListPosition()),
                $workflowEngine
            );
            return;
        }

        $workflowMessage = $workflowMessage->reconnectToProcessTask($taskListEntry->taskListPosition());

        if (count($this->taskList->getAllTaskListEntries()) > 1) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
            $this->logErrorMsg(
                'The ChunkProcess can only handle a single RunSubProcess task but there are more tasks configured',
                $workflowMessage,
                $workflowEngine
            );
            return;
        }

        if (! $taskListEntry->task() instanceof RunSubProcess) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
            $this->logErrorMsg(
                sprintf(
                    'The ChunkProcess can only handle a RunSubProcess task but there is a %s task configured',
                    get_class($taskListEntry->task())
                ),
                $workflowMessage,
                $workflowEngine
            );
            return;
        }

        if (! MessageNameUtils::isProcessingEvent($workflowMessage->messageName())) {
            $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));
            $this->receiveMessage(
                LogMessage::logWrongMessageReceivedFor($taskListEntry->task(), $taskListEntry->taskListPosition(), $workflowMessage),
                $workflowEngine
            );
            return;
        }

        $collection = $workflowMessage->payload()->toType();

        if (! $collection instanceof CollectionType) {
            $this->logErrorMsg(
                sprintf(
                    'The ChunkProcess requires a Prooph\ProcessingType\CollectionType as payload of the incoming message, but it is a %s type given',
                    $workflowMessage->payload()->getTypeClass()
                ),
                $workflowMessage,
                $workflowEngine
            );
            return;
        }

        $metadata = $workflowMessage->metadata();

        if (! isset($metadata[self::META_OFFSET])) {
            $this->logErrorMsg(
                'The ChunkProcess requires a offset key in the message metadata.',
                $workflowMessage,
                $workflowEngine
            );
            return;
        }

        if ((int)$metadata[self::META_OFFSET] !== 0) {
            $this->logErrorMsg(
                'The ChunkProcess requires that the first chunk starts with a offset of 0.',
                $workflowMessage,
                $workflowEngine
            );
            return;
        }

        if (! isset($metadata[self::META_LIMIT])) {
            $this->logErrorMsg(
                'The ChunkProcess requires a limit key in the message metadata.',
                $workflowMessage,
                $workflowEngine
            );
            return;
        }

        if ((int)$metadata[self::META_LIMIT] <= 0) {
            $this->logErrorMsg(
                'The ChunkProcess requires that metadata.limit is greater than 0.',
                $workflowMessage,
                $workflowEngine
            );
            return;
        }

        if (! isset($metadata[self::META_TOTAL_ITEMS])) {
            $this->logErrorMsg(
                'The ChunkProcess requires a total_items key in the message metadata.',
                $workflowMessage,
                $workflowEngine
            );
            return;
        }

        $this->startSubProcessForEachChunk($workflowMessage, $workflowEngine);
    }

    /**
     * @param WorkflowMessage $workflowMessage
     * @param WorkflowEngine $workflowEngine
     */
    private function startSubProcessForEachChunk(WorkflowMessage $workflowMessage, WorkflowEngine $workflowEngine)
    {
        $taskListEntry = $this->taskList->getNextNotStartedTaskListEntry();

        $this->recordThat(TaskEntryMarkedAsRunning::at($taskListEntry->taskListPosition()));

        $this->processingCollection = true;

        /** @var $task RunSubProcess */
        $task = $taskListEntry->task();

        $metadata = $workflowMessage->metadata();

        $currentOffset = 0;
        $currentLimit = (int)$metadata[self::META_LIMIT];
        $totalItems = (int)$metadata[self::META_TOTAL_ITEMS];

        //May start message was performed as a count only message so we unset this instruction to tell
        //the workflow message handler that it should collect the data now.
        unset($metadata[self::META_COUNT_ONLY]);

        do {
            $typeClass = $workflowMessage->payload()->getTypeClass();

            $metadata[self::META_OFFSET] = $currentOffset;
            $metadata[self::META_LIMIT] = $currentLimit;

            $collectChunk = WorkflowMessage::collectDataOf(
                $typeClass::prototype(),
                $this->taskList->taskListId()->nodeName(),
                $task->targetNodeName(),
                $metadata
            );

            $collectChunk->connectToProcessTask($taskListEntry->taskListPosition());

            $this->recordThat(MultiPerformTaskWasStarted::at($taskListEntry->taskListPosition()));

            $this->performRunSubProcess(
                $task,
                $taskListEntry->taskListPosition(),
                $workflowEngine,
                $collectChunk
            );

            $currentOffset = $currentOffset + $currentLimit;
        } while (($currentOffset + $currentLimit) <= $totalItems);

        $this->processingCollection = false;
    }

    /**
     * @param MultiPerformTaskWasStarted $event
     */
    public function whenMultiPerformTaskWasStarted(MultiPerformTaskWasStarted $event)
    {
        $this->performedTasks++;
    }

    /**
     * @param MultiPerformTaskSucceed $event
     */
    public function whenMultiPerformTaskSucceed(MultiPerformTaskSucceed $event)
    {
        $this->performedSuccessful++;
    }

    /**
     * @param MultiPerformTaskFailed $event
     */
    public function whenMultiPerformTaskFailed(MultiPerformTaskFailed $event)
    {
        $this->performedWithError++;
    }

    /**
     * @param TaskListEntry $taskListEntry
     */
    private function checkFinished(TaskListEntry $taskListEntry)
    {
        if ($this->processingCollection) return;

        if ($this->performedTasks == ($this->performedSuccessful + $this->performedWithError)) {

            if ($this->performedWithError > 0) {
                $this->recordThat(
                    TaskEntryMarkedAsFailed::at(
                        $taskListEntry->taskListPosition()
                    )
                );
            } else {
                $this->recordThat(
                    TaskEntryMarkedAsDone::at(
                        $taskListEntry->taskListPosition()
                    )
                );
            }
        }
    }
}
 