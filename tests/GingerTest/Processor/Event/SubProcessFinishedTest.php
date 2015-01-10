<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 04.12.14 - 21:58
 */

namespace GingerTest\Processor\Event;

use Ginger\Message\LogMessage;
use Ginger\Processor\Event\SubProcessFinished;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\TestCase;

/**
 * Class SubProcessFinishedTest
 *
 * @package GingerTest\Processor\Event
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SubProcessFinishedTest extends TestCase
{
    /**
     * @test
     */
    public function it_records_that_a_sub_process_has_finished()
    {
        $nodeName = NodeName::fromString('other_machine');
        $subProcessId = ProcessId::generate();
        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $message = LogMessage::logDebugMsg(
            "Processing finished",
            TaskListPosition::at(TaskListId::linkWith($nodeName, $subProcessId), 1)
        );

        $event = SubProcessFinished::record(
            $nodeName,
            $subProcessId,
            true,
            $message,
            $parentTaskListPosition
        );

        $this->assertInstanceOf('Ginger\Processor\Event\SubProcessFinished', $event);

        $this->assertTrue($nodeName->equals($event->processorNodeName()));
        $this->assertTrue($parentTaskListPosition->equals($event->parentTaskListPosition()));
        $this->assertTrue($subProcessId->equals($event->subProcessId()));
        $this->assertTrue($event->succeed());
        $this->assertEquals($message->technicalMsg(), $event->lastMessage()->technicalMsg());
    }
}
 