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
        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype(), array('metadata' => true), NodeName::defaultName()->toString());

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $wfMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-collect-data',
            $wfMessage->getMessageName()
        );

        $this->assertNull($wfMessage->payload()->extractTypeData());
        $this->assertEquals(array('metadata' => true), $wfMessage->metadata());
        $this->assertEquals(MessageNameUtils::COLLECT_DATA, $wfMessage->messageType());
        $this->assertEquals(NodeName::defaultName()->toString(), $wfMessage->target());
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

        $wfMessage = WorkflowMessage::newDataCollected($user, array('metadata' => true), NodeName::defaultName()->toString());

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $wfMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-data-collected',
            $wfMessage->getMessageName()
        );

        $this->assertEquals($userData, $wfMessage->payload()->extractTypeData());
        $this->assertEquals(array('metadata' => true), $wfMessage->metadata());
        $this->assertEquals(MessageNameUtils::DATA_COLLECTED, $wfMessage->messageType());
        $this->assertEquals(NodeName::defaultName()->toString(), $wfMessage->target());
    }

    /**
     * @test
     */
    public function it_transforms_a_collect_data_command_to_data_collected_event()
    {
        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype(), array('metadata' => true), 'user-data-connector');

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

        $wfAnswer = $wfMessage->answerWith($user, array('success' => true));

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-data-collected',
            $wfAnswer->getMessageName()
        );

        $this->assertEquals($userData, $wfAnswer->payload()->extractTypeData());

        $this->assertFalse($wfMessage->uuid()->equals($wfAnswer->uuid()));

        $this->assertEquals(1, $wfMessage->version());
        $this->assertEquals(2, $wfAnswer->version());

        $this->assertTrue($wfMessage->processTaskListPosition()->equals($wfAnswer->processTaskListPosition()));

        $this->assertEquals(array('metadata' => true, 'success' => true), $wfAnswer->metadata());

        $this->assertEquals('user-data-connector', $wfMessage->target());

        //Target of answer was not set so the target should default to node name passed to TaskListId
        $this->assertEquals(NodeName::defaultName()->toString(), $wfAnswer->target());
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

        $wfMessage = WorkflowMessage::newDataCollected($user, array('metadata' => true));

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfCommand = $wfMessage->prepareDataProcessing($taskListPosition, array('count' => 1), 'user-data-processor');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-process-data',
            $wfCommand->getMessageName()
        );

        $this->assertEquals($userData, $wfCommand->payload()->extractTypeData());

        $this->assertFalse($wfMessage->uuid()->equals($wfCommand->uuid()));

        $this->assertEquals(1, $wfMessage->version());
        $this->assertEquals(2, $wfCommand->version());

        $this->assertTrue($taskListPosition->equals($wfCommand->processTaskListPosition()));

        $this->assertEquals(array('metadata' => true, 'count' => 1), $wfCommand->metadata());

        $this->assertEquals('user-data-processor', $wfCommand->target());
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

        $wfMessage = WorkflowMessage::newDataCollected($user, array('metadata' => true));

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfCommand = $wfMessage->prepareDataProcessing($taskListPosition, array('prepared' => true), 'user-data-processor');

        $wfAnswer = $wfCommand->answerWithDataProcessingCompleted(array('processed' => true));

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-data-processed',
            $wfAnswer->getMessageName()
        );

        $this->assertEquals($userData, $wfAnswer->payload()->extractTypeData());

        $this->assertFalse($wfCommand->uuid()->equals($wfAnswer->uuid()));

        $this->assertEquals(1, $wfMessage->version());
        $this->assertEquals(2, $wfCommand->version());
        $this->assertEquals(3, $wfAnswer->version());

        $this->assertTrue($taskListPosition->equals($wfCommand->processTaskListPosition()));
        $this->assertTrue($wfCommand->processTaskListPosition()->equals($wfAnswer->processTaskListPosition()));

        $this->assertEquals(array('metadata' => true, 'prepared' => true, 'processed' => true), $wfAnswer->metadata());

        $this->assertEquals(NodeName::defaultName()->toString(), $wfAnswer->target());
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

        $this->assertEquals('GingerTest\Mock\TargetUserDictionary', $wfMessage->payload()->getTypeClass());
    }

    /**
     * @test
     */
    public function it_translates_itself_to_service_bus_message_and_back()
    {
        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype(), array('metadata' => true), NodeName::defaultName()->toString());

        $sbMessage = $wfMessage->toServiceBusMessage();

        $this->assertInstanceOf('Prooph\ServiceBus\Message\StandardMessage', $sbMessage);

        $copyOfWfMessage = WorkflowMessage::fromServiceBusMessage($sbMessage);

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $copyOfWfMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertestmockuserdictionary-collect-data',
            $copyOfWfMessage->getMessageName()
        );

        $this->assertNull($copyOfWfMessage->payload()->extractTypeData());
        $this->assertEquals(array('metadata' => true), $copyOfWfMessage->metadata());
        $this->assertEquals(MessageNameUtils::COLLECT_DATA, $copyOfWfMessage->messageType());
        $this->assertEquals(NodeName::defaultName()->toString(), $copyOfWfMessage->target());
    }
}
 