<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 15:54
 */

namespace Prooph\ProcessingTest\Message;

use Prooph\Processing\Message\MessageNameUtils;
use Prooph\ProcessingTest\TestCase;

/**
 * Class MessageNameUtilsTest
 *
 * @package Prooph\ProcessingTest\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class MessageNameUtilsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_a_collect_data_command_name_including_the_type_class_normalized()
    {
        $commandName = MessageNameUtils::getCollectDataCommandName('Prooph\ProcessingTest\Mock\UserDictionary');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmockuserdictionary-collect-data',
            $commandName
        );
    }

    /**
     * @test
     */
    public function it_returns_a_data_collected_event_name_including_the_type_class_normalized()
    {
        $eventName = MessageNameUtils::getDataCollectedEventName('Prooph\ProcessingTest\Mock\UserDictionary');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmockuserdictionary-data-collected',
            $eventName
        );
    }

    /**
     * @test
     */
    public function it_returns_a_process_data_command_name_including_the_type_class_normalized()
    {
        $commandName = MessageNameUtils::getProcessDataCommandName('Prooph\ProcessingTest\Mock\UserDictionary');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmockuserdictionary-process-data',
            $commandName
        );
    }

    /**
     * @test
     */
    public function it_returns_a_data_processed_event_name_including_the_type_class_normalized()
    {
        $eventName = MessageNameUtils::getDataProcessedEventName('Prooph\ProcessingTest\Mock\UserDictionary');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'proophprocessingtestmockuserdictionary-data-processed',
            $eventName
        );
    }

    /**
     * @test
     */
    public function it_returns_the_type_part_of_the_message_name()
    {
        $typePart = MessageNameUtils::getTypePartOfMessageName('processing-message-proophprocessingtestmockuserdictionary-data-processed');

        $this->assertEquals('proophprocessingtestmockuserdictionary', $typePart);
    }

    /**
     * @test
     */
    public function it_returns_null_when_type_part_could_not_be_detected()
    {
        $typePart = MessageNameUtils::getTypePartOfMessageName('processing-message--data-processed');

        $this->assertNull($typePart);
    }

    /**
     * @test
     */
    public function it_detects_processing_messages_by_name()
    {
        $this->assertTrue(MessageNameUtils::isWorkflowMessage('processing-message-proophprocessingTestmockuserdictionary-collect-data'));
        $this->assertTrue(MessageNameUtils::isProcessingCommand('processing-message-proophprocessingTestmockuserdictionary-collect-data'));
        $this->assertFalse(MessageNameUtils::isProcessingEvent('processing-message-proophprocessingTestmockuserdictionary-collect-data'));

        $this->assertTrue(MessageNameUtils::isWorkflowMessage('processing-message-proophprocessingTestmockuserdictionary-data-collected'));
        $this->assertFalse(MessageNameUtils::isProcessingCommand('processing-message-proophprocessingTestmockuserdictionary-data-collected'));
        $this->assertTrue(MessageNameUtils::isProcessingEvent('processing-message-proophprocessingTestmockuserdictionary-data-collected'));

        $this->assertTrue(MessageNameUtils::isWorkflowMessage('processing-message-proophprocessingTestmockuserdictionary-process-data'));
        $this->assertTrue(MessageNameUtils::isProcessingCommand('processing-message-proophprocessingTestmockuserdictionary-process-data'));
        $this->assertFalse(MessageNameUtils::isProcessingEvent('processing-message-proophprocessingTesttypemockuserdictionary-process-data'));

        $this->assertTrue(MessageNameUtils::isWorkflowMessage('processing-message-proophprocessingTestmockuserdictionary-data-processed'));
        $this->assertFalse(MessageNameUtils::isProcessingCommand('processing-message-proophprocessingTestmockuserdictionary-data-processed'));
        $this->assertTrue(MessageNameUtils::isProcessingEvent('processing-message-proophprocessingTestmockuserdictionary-data-processed'));
    }

    /**
     * @test
     */
    public function it_detects_processing_log_message_by_name()
    {
        $this->assertTrue(MessageNameUtils::isProcessingLogMessage(MessageNameUtils::LOG_MESSAGE_NAME));
        $this->assertFalse(MessageNameUtils::isProcessingLogMessage('processing-message-proophprocessingTestmockuserdictionary-process-data'));
    }
}
 