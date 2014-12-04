<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 16:50
 */

namespace GingerTest\Message;

use Ginger\Message\MessageNameUtils;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\TestCase;
use GingerTest\Mock\AddressDictionary;
use GingerTest\Mock\UserDictionary;

/**
 * Class WorkflowMessageTest
 *
 * @package GingerTest\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowMessageTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_a_collect_data_of_prototype_command()
    {
        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype());

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $wfMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-collect-data',
            $wfMessage->getMessageName()
        );

        $this->assertEquals(array(), $wfMessage->getPayload()->getData());
    }

    /**
     * @test
     */
    public function it_constructs_a_data_collected_event()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $wfMessage = WorkflowMessage::newDataCollected($user);

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $wfMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-data-collected',
            $wfMessage->getMessageName()
        );

        $this->assertEquals($userData, $wfMessage->getPayload()->getData());
    }

    /**
     * @test
     */
    public function it_transforms_a_collect_data_command_to_data_collected_event()
    {
        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype());

        $wfMessage->connectToProcessTask(TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1));

        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $wfAnswer = $wfMessage->answerWith($user);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-data-collected',
            $wfAnswer->getMessageName()
        );

        $this->assertEquals($userData, $wfAnswer->getPayload()->getData());

        $this->assertFalse($wfMessage->getUuid()->equals($wfAnswer->getUuid()));

        $this->assertEquals(1, $wfMessage->getVersion());
        $this->assertEquals(2, $wfAnswer->getVersion());

        $this->assertTrue($wfMessage->getProcessTaskListPosition()->equals($wfAnswer->getProcessTaskListPosition()));
    }

    /**
     * @test
     */
    public function it_throws_invalid_type_exception_if_answer_type_does_not_match_with_requested_type()
    {
        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype());

        $address = AddressDictionary::fromNativeValue(array(
            'street' => 'Main Street',
            'streetNumber' => 10,
            'zip' => '12345',
            'city' => 'Test City'
        ));

        $this->setExpectedException('Ginger\Type\Exception\InvalidTypeException');

        $wfMessage->answerWith($address);
    }

    /**
     * @test
     */
    public function it_transforms_a_data_collected_event_to_a_process_data_command()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $wfMessage = WorkflowMessage::newDataCollected($user);

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfCommand = $wfMessage->prepareDataProcessing($taskListPosition);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-process-data',
            $wfCommand->getMessageName()
        );

        $this->assertEquals($userData, $wfCommand->getPayload()->getData());

        $this->assertFalse($wfMessage->getUuid()->equals($wfCommand->getUuid()));

        $this->assertEquals(1, $wfMessage->getVersion());
        $this->assertEquals(2, $wfCommand->getVersion());

        $this->assertTrue($taskListPosition->equals($wfCommand->getProcessTaskListPosition()));
    }

    /**
     * @test
     */
    public function it_transforms_a_process_data_command_to_a_data_processed_event()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $wfMessage = WorkflowMessage::newDataCollected($user);

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfCommand = $wfMessage->prepareDataProcessing($taskListPosition);

        $wfAnswer = $wfCommand->answerWithDataProcessingCompleted();

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-data-processed',
            $wfAnswer->getMessageName()
        );

        $this->assertEquals($userData, $wfAnswer->getPayload()->getData());

        $this->assertFalse($wfCommand->getUuid()->equals($wfAnswer->getUuid()));

        $this->assertEquals(1, $wfMessage->getVersion());
        $this->assertEquals(2, $wfCommand->getVersion());
        $this->assertEquals(3, $wfAnswer->getVersion());

        $this->assertTrue($taskListPosition->equals($wfCommand->getProcessTaskListPosition()));
        $this->assertTrue($wfCommand->getProcessTaskListPosition()->equals($wfAnswer->getProcessTaskListPosition()));
    }

    /**
     * @test
     */
    public function it_changes_ginger_type_in_message_name_and_payload()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $wfMessage = WorkflowMessage::newDataCollected($user);

        $wfMessage->changeGingerType('GingerTest\Mock\TargetUserDictionary');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmocktargetuserdictionary-data-collected',
            $wfMessage->getMessageName()
        );

        $this->assertEquals('GingerTest\Mock\TargetUserDictionary', $wfMessage->getPayload()->getTypeClass());
    }
}
 