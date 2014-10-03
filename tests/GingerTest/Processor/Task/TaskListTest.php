<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 22:59
 */

namespace GingerTest\Processor\Task;

use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\CollectData;
use Ginger\Processor\Task\TaskList;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;

/**
 * Class TaskListTest
 *
 * @package GingerTest\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TaskListTest extends TestCase
{
    /**
     * @test
     */
    public function it_provides_a_list_of_entries()
    {
        $taskList = $this->getTestTaskList();

        $entries = $taskList->getAllTaskListEntries();

        $this->assertEquals(3, count($entries));

        foreach($entries as $entry) {
            $this->assertInstanceOf('Ginger\Processor\Task\TaskListEntry', $entry);
        }
    }

    /**
     * @test
     */
    public function it_provides_next_not_started_task_list_entry()
    {
        $taskList = $this->getTestTaskList();

        $task1 = $taskList->getTaskListEntryAtPosition(TaskListPosition::at($taskList->taskListId(), 1));

        $task1->markAsRunning();

        $task1->markAsSuccessfulDone();

        $task2 = $taskList->getTaskListEntryAtPosition(TaskListPosition::at($taskList->taskListId(), 2));

        $task2->markAsRunning();

        $task3 = $taskList->getNextNotStartedTaskListEntry();

        $this->assertEquals(3, $task3->taskListPosition()->position());
    }

    /**
     * @test
     */
    public function it_provides_all_not_started_task_list_entries()
    {
        $taskList = $this->getTestTaskList();

        $task2 = $taskList->getTaskListEntryAtPosition(TaskListPosition::at($taskList->taskListId(), 2));

        $task2->markAsRunning();

        $notStartedTaskListEntries = $taskList->getAllNotStartedTaskListEntries();

        $this->assertEquals(2, count($notStartedTaskListEntries));

        $this->assertEquals(1, $notStartedTaskListEntries[0]->taskListPosition()->position());
        $this->assertEquals(3, $notStartedTaskListEntries[1]->taskListPosition()->position());
    }

    /**
     * @test
     */
    public function it_provides_all_task_list_entries()
    {
        $taskList = $this->getTestTaskList();

        $task1 = $taskList->getTaskListEntryAtPosition(TaskListPosition::at($taskList->taskListId(), 1));

        $task1->markAsRunning();

        $task1->markAsSuccessfulDone();

        $task2 = $taskList->getTaskListEntryAtPosition(TaskListPosition::at($taskList->taskListId(), 2));

        $task2->markAsRunning();

        $taskListEntries = $taskList->getAllTaskListEntries();

        $this->assertEquals(3, count($taskListEntries));
    }

    /**
     * @test
     */
    public function it_is_only_completed_when_all_tasks_are_finished()
    {
        $taskList = $this->getTestTaskList();

        $this->assertFalse($taskList->isCompleted());

        $task1 = $taskList->getTaskListEntryAtPosition(TaskListPosition::at($taskList->taskListId(), 1));

        $task1->markAsRunning();

        $task1->markAsSuccessfulDone();

        $task2 = $taskList->getTaskListEntryAtPosition(TaskListPosition::at($taskList->taskListId(), 2));

        $task2->markAsRunning();

        $this->assertFalse($taskList->isCompleted());

        $task2->markAsSuccessfulDone();

        $task3 = $taskList->getTaskListEntryAtPosition(TaskListPosition::at($taskList->taskListId(), 3));

        $task3->markAsRunning();

        $task3->markAsFailed();

        $this->assertTrue($taskList->isCompleted());
    }

    /**
     * @return TaskList
     */
    protected function getTestTaskList()
    {
        $task1 = CollectData::from('crm', UserDictionary::prototype());
        $task2 = CollectData::from('online-shop', UserDictionary::prototype());
        $task3 = CollectData::from('address-book', UserDictionary::prototype());

        $processId = ProcessId::generate();

        $taskListId = TaskListId::linkWith($processId);

        return TaskList::scheduleTasks($taskListId, [$task1, $task2, $task3]);
    }
}
 