<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 05.10.14 - 21:53
 */

namespace GingerTest\Processor\Task;
use Ginger\Processor\Task\ProcessData;
use GingerTest\TestCase;

/**
 * Class ProcessDataTest
 *
 * @package GingerTest\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessDataTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_from_target_and_allowed_types_list()
    {
        $task = ProcessData::address('online-shop', ['GingerTest\Mock\UserDictionary', 'GingerTest\Mock\AddressDictionary']);

        $this->assertInstanceOf('Ginger\Processor\Task\ProcessData', $task);

        $this->assertEquals('online-shop', $task->target());

        $this->assertEquals(['GingerTest\Mock\UserDictionary', 'GingerTest\Mock\AddressDictionary'], $task->allowedTypes());
    }

    /**
     * @test
     */
    public function it_uses_first_type_of_allowed_types_list_if_no_type_is_preferred_explicit()
    {
        $task = ProcessData::address('online-shop', ['GingerTest\Mock\UserDictionary', 'GingerTest\Mock\AddressDictionary']);

        $this->assertEquals('GingerTest\Mock\UserDictionary', $task->preferredType());
    }

    /**
     * @test
     */
    public function it_uses_preferred_type_if_one_is_specified()
    {
        $task = ProcessData::address('online-shop', ['GingerTest\Mock\UserDictionary', 'GingerTest\Mock\AddressDictionary'], 'GingerTest\Mock\AddressDictionary');

        $this->assertEquals('GingerTest\Mock\AddressDictionary', $task->preferredType());
    }

    /**
     * @test
     */
    public function it_checks_if_preferred_type_is_also_an_allowed_type()
    {
        $this->setExpectedException('\InvalidArgumentException');

        ProcessData::address('online-shop', ['GingerTest\Mock\UserDictionary'], 'GingerTest\Mock\AddressDictionary');
    }

    /**
     * @test
     */
    public function it_checks_if_type_class_exists()
    {
        $this->setExpectedException('\InvalidArgumentException');

        ProcessData::address('online-shop', ['GingerTest\Mock\UnknownDictionary']);
    }

    /**
     * @test
     */
    public function it_checks_if_given_types_implement_ginger_type_interface()
    {
        $this->setExpectedException('\InvalidArgumentException');

        ProcessData::address('online-shop', ['GingerTest\Mock\TestWorkflowMessageHandler']);
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_array_and_back()
    {
        $task = ProcessData::address(
            'online-shop',
            ['GingerTest\Mock\UserDictionary', 'GingerTest\Mock\AddressDictionary'],
            'GingerTest\Mock\AddressDictionary'
        );

        $taskData = $task->getArrayCopy();

        $this->assertTrue(is_array($taskData));

        $copiedTask = ProcessData::reconstituteFromArray($taskData);

        $this->assertEquals('online-shop', $copiedTask->target());
        $this->assertEquals(['GingerTest\Mock\UserDictionary', 'GingerTest\Mock\AddressDictionary'], $copiedTask->allowedTypes());
        $this->assertEquals('GingerTest\Mock\AddressDictionary', $copiedTask->preferredType());
    }
}
 