<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12/5/14 - 10:52 PM
 */
namespace Prooph\ProcessingTest\Processor\Task;

use Prooph\Processing\Message\Payload;
use Prooph\Processing\Processor\Task\ManipulatePayload;
use Prooph\Processing\Type\String;
use Prooph\ProcessingTest\TestCase;

/**
 * Class ManipulatePayloadTest
 *
 * @package Prooph\ProcessingTest\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
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

        $this->assertEquals('Hello World', $payload->extractTypeData());
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

        $this->assertEquals('Hello World', $payload->extractTypeData());
    }
} 