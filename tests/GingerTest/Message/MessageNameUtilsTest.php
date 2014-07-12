<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 15:54
 */

namespace GingerTest\Message;

use Ginger\Message\MessageNameUtils;
use GingerTest\TestCase;

/**
 * Class MessageNameUtilsTest
 *
 * @package GingerTest\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class MessageNameUtilsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_a_collect_data_command_name_including_the_type_class_normalized()
    {
        $commandName = MessageNameUtils::getCollectDataCommandName('GingerTest\Type\Mock\UserDictionary');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertesttypemockuserdictionary-collect-data',
            $commandName
        );
    }

    /**
     * @test
     */
    public function it_returns_a_data_collected_event_name_including_the_type_class_normalized()
    {
        $eventName = MessageNameUtils::getDataCollectedEventName('GingerTest\Type\Mock\UserDictionary');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertesttypemockuserdictionary-data-collected',
            $eventName
        );
    }

    /**
     * @test
     */
    public function it_returns_a_process_data_command_name_including_the_type_class_normalized()
    {
        $commandName = MessageNameUtils::getProcessDataCommandName('GingerTest\Type\Mock\UserDictionary');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertesttypemockuserdictionary-process-data',
            $commandName
        );
    }

    /**
     * @test
     */
    public function it_returns_a_data_processed_event_name_including_the_type_class_normalized()
    {
        $eventName = MessageNameUtils::getDataProcessedEventName('GingerTest\Type\Mock\UserDictionary');

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertesttypemockuserdictionary-data-processed',
            $eventName
        );
    }

    /**
     * @test
     */
    public function it_returns_the_type_part_of_the_message_name()
    {
        $typePart = MessageNameUtils::getTypePartOfMessageName('ginger-message-gingertesttypemockuserdictionary-data-processed');

        $this->assertEquals('gingertesttypemockuserdictionary', $typePart);
    }

    /**
     * @test
     */
    public function it_returns_null_when_type_part_could_not_be_detected()
    {
        $typePart = MessageNameUtils::getTypePartOfMessageName('ginger-message--data-processed');

        $this->assertNull($typePart);
    }
}
 