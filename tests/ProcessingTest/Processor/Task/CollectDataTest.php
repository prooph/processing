<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 02:10
 */

namespace Prooph\ProcessingTest\Processor\Task;

use Prooph\Processing\Processor\Task\CollectData;
use Prooph\ProcessingTest\Mock\UserDictionary;
use Prooph\ProcessingTest\TestCase;

/**
 * Class CollectDataTest
 *
 * @package Prooph\ProcessingTest\Processor\Task
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
        $this->assertInstanceOf('Prooph\Processing\Type\Prototype', $task->prototype());
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
 