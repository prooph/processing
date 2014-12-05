<?php
/*
 * This file is part of Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12/5/14 - 10:52 PM
 */
namespace GingerTest\Processor\Task;

use Ginger\Message\Payload;
use Ginger\Processor\Task\ManipulatePayload;
use Ginger\Type\String;
use GingerTest\TestCase;

/**
 * Class ManipulatePayloadTest
 *
 * @package GingerTest\Processor\Task
 * @author Alexander Miertsch <alexander.miertsch.extern@sixt.com>
 */
class ManipulatePayloadTest extends TestCase
{
    /**
     * @test
     */
    function it_uses_a_script_to_manipulate_payload()
    {
        $task = ManipulatePayload::with(__DIR__ . '/../../Mock/manipulation/append_world.php');

        $payload = Payload::fromType(String::fromNativeValue('Hello'));

        $task->performManipulationOn($payload);

        $this->assertEquals('Hello World', $payload->getData());
    }

    /**
     * @test
     */
    function it_can_be_set_up_from_array()
    {
        $task = ManipulatePayload::with(__DIR__ . '/../../Mock/manipulation/append_world.php');

        $taskData = $task->getArrayCopy();

        $copiedTask = ManipulatePayload::reconstituteFromArray($taskData);

        $payload = Payload::fromType(String::fromNativeValue('Hello'));

        $copiedTask->performManipulationOn($payload);

        $this->assertEquals('Hello World', $payload->getData());
    }
} 