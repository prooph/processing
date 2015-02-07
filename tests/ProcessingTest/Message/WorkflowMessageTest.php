<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 16:50
 */

namespace Prooph\ProcessingTest\Message;

use Prooph\Processing\Message\MessageNameUtils;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\ProcessingTest\TestCase;
use Prooph\ProcessingTest\Mock\AddressDictionary;
use Prooph\ProcessingTest\Mock\UserDictionary;

/**
 * Class WorkflowMessageTest
 *
 * @package Prooph\ProcessingTest\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowMessageTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_a_collect_data_of_prototype_command()
    {
        $wfMessage = WorkflowMessage::collectDataOf(
            UserDictionary::prototype(),
            'test-case',
            NodeName::defaultName()->toString(),
            array('metadata' => true)
        );

        $this->assertInstanceOf('Prooph\Processing\Message\WorkflowMessage', $wfMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmockuserdictionary-collect-data',
            $wfMessage->getMessageName()
        );

        $this->assertNull($wfMessage->payload()->extractTypeData());
        $this->assertEquals(array('metadata' => true), $wfMessage->metadata());
        $this->assertEquals(MessageNameUtils::COLLECT_DATA, $wfMessage->messageType());
        $this->assertEquals('test-case', $wfMessage->origin());
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

        $wfMessage = WorkflowMessage::newDataCollected(
            $user,
            'test-case',
            NodeName::defaultName()->toString(),
            array('metadata' => true)
        );

        $this->assertInstanceOf('Prooph\Processing\Message\WorkflowMessage', $wfMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmockuserdictionary-data-collected',
            $wfMessage->getMessageName()
        );

        $this->assertEquals($userData, $wfMessage->payload()->extractTypeData());
        $this->assertEquals(array('metadata' => true), $wfMessage->metadata());
        $this->assertEquals(MessageNameUtils::DATA_COLLECTED, $wfMessage->messageType());
        $this->assertEquals('test-case', $wfMessage->origin());
        $this->assertEquals(NodeName::defaultName()->toString(), $wfMessage->target());
    }

    /**
     * @test
     */
    public function it_transforms_a_collect_data_command_to_data_collected_event()
    {
        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype(), 'test-case', 'user-data-connector', array('metadata' => true));

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
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmockuserdictionary-data-collected',
            $wfAnswer->getMessageName()
        );

        $this->assertEquals($userData, $wfAnswer->payload()->extractTypeData());

        $this->assertFalse($wfMessage->uuid()->equals($wfAnswer->uuid()));

        $this->assertEquals(1, $wfMessage->version());
        $this->assertEquals(2, $wfAnswer->version());

        $this->assertTrue($wfMessage->processTaskListPosition()->equals($wfAnswer->processTaskListPosition()));

        $this->assertEquals(array('metadata' => true, 'success' => true), $wfAnswer->metadata());

        $this->assertEquals('test-case', $wfMessage->origin());
        $this->assertEquals('user-data-connector', $wfMessage->target());

        //For the answer origin and target should be switched
        $this->assertEquals('user-data-connector', $wfAnswer->origin());
        $this->assertEquals('test-case', $wfAnswer->target());
    }

    /**
     * @test
     */
    public function it_throws_invalid_type_exception_if_answer_type_does_not_match_with_requested_type()
    {
        $wfMessage = WorkflowMessage::collectDataOf(
            UserDictionary::prototype(),
            'test-case',
            'message-handler'
        );

        $address = AddressDictionary::fromNativeValue(array(
            'street' => 'Main Street',
            'streetNumber' => 10,
            'zip' => '12345',
            'city' => 'Test City'
        ));

        $this->setExpectedException('Prooph\Processing\Type\Exception\InvalidTypeException');

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

        $wfMessage = WorkflowMessage::newDataCollected(
            $user,
            'test-case',
            NodeName::defaultName()->toString(),
            array('metadata' => true)
        );

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfCommand = $wfMessage->prepareDataProcessing(
            $taskListPosition,
            'user-data-processor',
            array('count' => 1)
        );

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmockuserdictionary-process-data',
            $wfCommand->getMessageName()
        );

        $this->assertEquals($userData, $wfCommand->payload()->extractTypeData());

        $this->assertFalse($wfMessage->uuid()->equals($wfCommand->uuid()));

        $this->assertEquals(1, $wfMessage->version());
        $this->assertEquals(2, $wfCommand->version());

        $this->assertTrue($taskListPosition->equals($wfCommand->processTaskListPosition()));

        $this->assertEquals(array('metadata' => true, 'count' => 1), $wfCommand->metadata());

        //Old target should become new origin and target should be set as new target
        $this->assertEquals(NodeName::defaultName()->toString(), $wfCommand->origin());
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

        $wfMessage = WorkflowMessage::newDataCollected(
            $user,
            'test-case',
            NodeName::defaultName()->toString(),
            array('metadata' => true)
        );

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfCommand = $wfMessage->prepareDataProcessing(
            $taskListPosition,
            'user-data-processor',
            array('prepared' => true)
        );

        $wfAnswer = $wfCommand->answerWithDataProcessingCompleted(array('processed' => true));

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmockuserdictionary-data-processed',
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

        //Target of the new-data-collected event, should also be the target of the answer of the process-data command
        $this->assertEquals(NodeName::defaultName()->toString(), $wfAnswer->target());
        //Origin of the answer should be the addressed message handler
        $this->assertEquals('user-data-processor', $wfAnswer->origin());
    }

    /**
     * @test
     */
    public function it_changes_processing_type_in_message_name_and_payload()
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

        $wfMessage = WorkflowMessage::newDataCollected($user, 'test-case', NodeName::defaultName()->toString());

        $wfMessage->changeProcessingType('Prooph\ProcessingTest\Mock\TargetUserDictionary');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmocktargetuserdictionary-data-collected',
            $wfMessage->getMessageName()
        );

        $this->assertEquals('Prooph\ProcessingTest\Mock\TargetUserDictionary', $wfMessage->payload()->getTypeClass());
    }

    /**
     * @test
     */
    public function it_translates_itself_to_service_bus_message_and_back()
    {
        $wfMessage = WorkflowMessage::collectDataOf(
            UserDictionary::prototype(),
            'test-case',
            NodeName::defaultName()->toString(),
            array('metadata' => true)
        );

        $sbMessage = $wfMessage->toServiceBusMessage();

        $this->assertInstanceOf('Prooph\ServiceBus\Message\StandardMessage', $sbMessage);

        $copyOfWfMessage = WorkflowMessage::fromServiceBusMessage($sbMessage);

        $this->assertInstanceOf('Prooph\Processing\Message\WorkflowMessage', $copyOfWfMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmockuserdictionary-collect-data',
            $copyOfWfMessage->getMessageName()
        );

        $this->assertNull($copyOfWfMessage->payload()->extractTypeData());
        $this->assertEquals(array('metadata' => true), $copyOfWfMessage->metadata());
        $this->assertEquals(MessageNameUtils::COLLECT_DATA, $copyOfWfMessage->messageType());
        $this->assertEquals('test-case', $copyOfWfMessage->origin());
        $this->assertEquals(NodeName::defaultName()->toString(), $copyOfWfMessage->target());
    }
}
 