<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 01:46
 */

namespace GingerTest\Processor\Task;

use Ginger\Message\LogMessage;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\CollectData;
use Ginger\Processor\Task\TaskListEntry;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;

/**
 * Class TaskListEntryTest
 *
 * @package GingerTest\Processor\Task
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

        $this->assertInstanceOf('Ginger\Processor\Task\TaskListEntry', $taskListEntry);
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

        $taskListEntry->maskAsFailed();

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

        $this->assertInstanceOf('Ginger\Processor\Task\TaskListPosition', $taskListEntry->taskListPosition());
    }

    /**
     * @test
     */
    public function it_is_capable_of_logging_messages()
    {
        $taskListEntry = $this->getTestTaskListEntry();

        $info = LogMessage::logInfoDataProcessingStarted($taskListEntry->taskListPosition());

        $taskListEntry->logMessage($info);

        $warning = LogMessage::logWarningMsg("Just a warning", $taskListEntry->taskListPosition());

        $taskListEntry->logMessage($warning);

        $log = $taskListEntry->messageLog();

        $this->assertEquals(2, count($log));

        $this->assertSame($info, $log[0]);
        $this->assertSame($warning, $log[1]);
    }

    protected function getTestTaskListEntry()
    {
        $processId = ProcessId::generate();

        $taskListId = TaskListId::linkWith($processId);

        $taskListPosition = TaskListPosition::at($taskListId, 1);

        $task = CollectData::from('test-crm', UserDictionary::prototype());

        return TaskListEntry::newEntryAt($taskListPosition, $task);
    }
}
 