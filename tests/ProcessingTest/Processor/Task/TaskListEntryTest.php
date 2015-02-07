<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 01:46
 */

namespace Prooph\ProcessingTest\Processor\Task;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\CollectData;
use Prooph\Processing\Processor\Task\TaskListEntry;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\ProcessingTest\Mock\UserDictionary;
use Prooph\ProcessingTest\TestCase;

/**
 * Class TaskListEntryTest
 *
 * @package Prooph\ProcessingTest\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TaskListEntryTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_with_a_task_list_postion_and_a_task()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $this->assertInstanceOf('Prooph\Processing\Processor\Task\TaskListEntry', $taskListEntry);
    }

    /**
     * @test
     */
    public function it_is_not_started_after_creation()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $this->assertFalse($taskListEntry->isStarted());
        $this->assertFalse($taskListEntry->isFinished());
        $this->assertFalse($taskListEntry->isRunning());
        $this->assertFalse($taskListEntry->isDone());
        $this->assertFalse($taskListEntry->isFailed());

        $this->assertNull($taskListEntry->startedOn());
        $this->assertNull($taskListEntry->finishedOn());
    }

    /**
     * @test
     */
    public function it_can_be_marked_as_running()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $taskListEntry->markAsRunning();

        $this->assertTrue($taskListEntry->isStarted());
        $this->assertFalse($taskListEntry->isFinished());
        $this->assertTrue($taskListEntry->isRunning());
        $this->assertFalse($taskListEntry->isDone());
        $this->assertFalse($taskListEntry->isFailed());

        $this->assertInstanceOf('\DateTime', $taskListEntry->startedOn());
        $this->assertNull($taskListEntry->finishedOn());
    }

    /**
     * @test
     */
    public function it_can_be_marked_as_successful_done()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $taskListEntry->markAsRunning();

        $taskListEntry->markAsSuccessfulDone();

        $this->assertTrue($taskListEntry->isStarted());
        $this->assertTrue($taskListEntry->isFinished());
        $this->assertFalse($taskListEntry->isRunning());
        $this->assertTrue($taskListEntry->isDone());
        $this->assertFalse($taskListEntry->isFailed());

        $this->assertInstanceOf('\DateTime', $taskListEntry->startedOn());
        $this->assertInstanceOf('\DateTime', $taskListEntry->finishedOn());
    }

    /**
     * @test
     */
    public function it_can_be_marked_as_failed()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $taskListEntry->markAsRunning();

        $taskListEntry->markAsFailed();

        $this->assertTrue($taskListEntry->isStarted());
        $this->assertTrue($taskListEntry->isFinished());
        $this->assertFalse($taskListEntry->isRunning());
        $this->assertFalse($taskListEntry->isDone());
        $this->assertTrue($taskListEntry->isFailed());

        $this->assertInstanceOf('\DateTime', $taskListEntry->startedOn());
        $this->assertInstanceOf('\DateTime', $taskListEntry->finishedOn());
    }

    /**
     * @test
     */
    public function it_has_a_task_list_position()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $this->assertInstanceOf('Prooph\Processing\Processor\Task\TaskListPosition', $taskListEntry->taskListPosition());
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_array_and_back_after_creation()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $arrCopy = $taskListEntry->getArrayCopy();

        $equalTaskListEntry = TaskListEntry::fromArray($arrCopy);

        $this->assertEqualTaskListEntries($taskListEntry, $equalTaskListEntry);
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_array_and_back_after_changing_status()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $taskListEntry->markAsRunning();

        $arrCopy = $taskListEntry->getArrayCopy();

        $equalTaskListEntry = TaskListEntry::fromArray($arrCopy);

        $this->assertEqualTaskListEntries($taskListEntry, $equalTaskListEntry);
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_array_and_back_after_changing_status_and_logging_messages()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $wfMessage->connectToProcessTask($taskListEntry->taskListPosition());

        $taskListEntry->markAsRunning();

        $taskListEntry->logMessage(LogMessage::logDebugMsg("A debug msg", $wfMessage));
        $taskListEntry->logMessage(LogMessage::logInfoDataProcessingStarted($wfMessage));

        $arrCopy = $taskListEntry->getArrayCopy();

        $equalTaskListEntry = TaskListEntry::fromArray($arrCopy);

        $this->assertEqualTaskListEntries($taskListEntry, $equalTaskListEntry);
    }

    /**
     * @test
     */
    public function it_is_capable_of_logging_messages()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $wfMessage->connectToProcessTask($taskListEntry->taskListPosition());

        $info = LogMessage::logInfoDataProcessingStarted($wfMessage);

        $taskListEntry->logMessage($info);

        $warning = LogMessage::logWarningMsg("Just a warning", $wfMessage);

        $taskListEntry->logMessage($warning);

        $log = $taskListEntry->messageLog();

        $this->assertEquals(2, count($log));

        $this->assertSame($info, $log[0]);
        $this->assertSame($warning, $log[1]);
    }

    protected function getTestTaskListEntry()
    {
        $processId = ProcessId::generate();

        $taskListId = TaskListId::linkWith(NodeName::defaultName(), $processId);

        $taskListPosition = TaskListPosition::at($taskListId, 1);

        $task = CollectData::from('test-crm', UserDictionary::prototype());

        return TaskListEntry::newEntryAt($taskListPosition, $task);
    }

    protected function assertEqualTaskListEntries(TaskListEntry $a, TaskListEntry $b)
    {
        $this->assertTrue($a->taskListPosition()->equals($b->taskListPosition()));

        $this->assertTrue($a->task()->equals($b->task()));

        $this->assertEquals($a->isStarted(), $b->isStarted());
        $this->assertEquals($a->isRunning(), $b->isRunning());
        $this->assertEquals($a->isDone(), $b->isDone());
        $this->assertEquals($a->isFailed(), $b->isFailed());

        $this->assertEquals(count($a->messageLog()), count($b->messageLog()));

        $this->assertEquals($a->getArrayCopy()['log'], $b->getArrayCopy()['log']);
    }
}
 