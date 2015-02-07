<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 04.12.14 - 21:58
 */

namespace Prooph\ProcessingTest\Processor\Event;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Processor\Event\SubProcessFinished;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\ProcessingTest\TestCase;

/**
 * Class SubProcessFinishedTest
 *
 * @package Prooph\ProcessingTest\Processor\Event
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

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $wfMessage->connectToProcessTask(TaskListPosition::at(TaskListId::linkWith($nodeName, $subProcessId), 1));


        $message = LogMessage::logDebugMsg(
            "Processing finished",
            $wfMessage
        );

        $event = SubProcessFinished::record(
            $nodeName,
            $subProcessId,
            true,
            $message,
            $parentTaskListPosition
        );

        $this->assertInstanceOf('Prooph\Processing\Processor\Event\SubProcessFinished', $event);

        $this->assertTrue($nodeName->equals($event->processorNodeName()));
        $this->assertTrue($parentTaskListPosition->equals($event->parentTaskListPosition()));
        $this->assertEquals($parentTaskListPosition->taskListId()->nodeName()->toString(), $event->target());
        $this->assertTrue($subProcessId->equals($event->subProcessId()));
        $this->assertTrue($event->succeed());
        $this->assertEquals($message->technicalMsg(), $event->lastMessage()->technicalMsg());
    }

    /**
     * @test
     */
    public function it_translates_to_service_bus_message_and_back()
    {
        $nodeName = NodeName::fromString('other_machine');
        $subProcessId = ProcessId::generate();
        $parentTaskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfMessage = $this->getUserDataCollectedTestMessage();

        $wfMessage->connectToProcessTask(TaskListPosition::at(TaskListId::linkWith($nodeName, $subProcessId), 1));

        $message = LogMessage::logDebugMsg(
            "Processing finished",
            $wfMessage
        );

        $event = SubProcessFinished::record(
            $nodeName,
            $subProcessId,
            true,
            $message,
            $parentTaskListPosition
        );

        $sbMessage = $event->toServiceBusMessage();

        $this->assertInstanceOf('Prooph\ServiceBus\Message\StandardMessage', $sbMessage);

        $copyOfEvent = SubProcessFinished::fromServiceBusMessage($sbMessage);

        $this->assertInstanceOf('Prooph\Processing\Processor\Event\SubProcessFinished', $copyOfEvent);

        $this->assertTrue($nodeName->equals($copyOfEvent->processorNodeName()));
        $this->assertTrue($parentTaskListPosition->equals($copyOfEvent->parentTaskListPosition()));
        $this->assertEquals($parentTaskListPosition->taskListId()->nodeName()->toString(), $copyOfEvent->target());
        $this->assertTrue($subProcessId->equals($copyOfEvent->subProcessId()));
        $this->assertTrue($copyOfEvent->succeed());
        $this->assertEquals($message->technicalMsg(), $copyOfEvent->lastMessage()->technicalMsg());
    }
}
 