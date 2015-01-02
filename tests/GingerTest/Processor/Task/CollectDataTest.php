<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 02:10
 */

namespace GingerTest\Processor\Task;

use Ginger\Processor\Task\CollectData;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;

/**
 * Class CollectDataTest
 *
 * @package GingerTest\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class CollectDataTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_from_source_and_prototype()
    {
        $task = CollectData::from('online-shop', UserDictionary::prototype(), ['metadata' => true]);

        $this->assertEquals('online-shop', $task->source());
        $this->assertInstanceOf('Ginger\Type\Prototype', $task->prototype());
        $this->assertEquals(['metadata' => true], $task->metadata());
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_array_and_back()
    {
        $task = CollectData::from('online-shop', UserDictionary::prototype(), ['metadata' => true]);

        $arrayCopy = $task->getArrayCopy();

        $task2 = CollectData::reconstituteFromArray($arrayCopy);

        $this->assertNotSame($task, $task2);

        $this->assertTrue($task->equals($task2));
    }
}
 