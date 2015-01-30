<?php
/**
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GingerTest\Functional;

use Ginger\Functional\Func;
use Ginger\Message\Payload;
use Ginger\Type\String;
use Ginger\Type\StringCollection;
use GingerTest\TestCase;

/**
 * Class FuncTest
 *
 * @package GingerTest\Functional
 * @author Alexander Miertsch <alexander.miertsch.extern@sixt.com>
 */
class FuncTest extends TestCase
{
    /**
     * @test
     */
    function elements_of_collection_are_mapped_to_callback()
    {
        $numbers = [1,2,3];

        $squareNumbers = Func::map($numbers, function($number) { return $number * $number; });

        $this->assertEquals([1,4,9], $squareNumbers);
    }

    /**
     * @test
     */
    function it_returns_a_callable_method()
    {
        $map = Func::get('map');

        $numbers = [1,2,3];

        $squareNumbers = $map($numbers, function($number) { return $number * $number; });

        $this->assertEquals([1,4,9], $squareNumbers);
    }

    /**
     * @test
     */
    function it_prepares_a_function_skipping_the_first_argument()
    {
        $calculateSquareNumbers = Func::prepare('map', null, function($number) { return $number * $number; });

        $numbers = [1,2,3];

        $squareNumbers = $calculateSquareNumbers($numbers);

        $this->assertEquals([1,4,9], $squareNumbers);
    }

    /**
     * @test
     */
    function it_prepares_a_function_skipping_the_last_argument()
    {
        $mappableNumbers = Func::prepare('map', [1,2,3], null);

        $this->assertEquals([1,4,9], $mappableNumbers(function($number) { return $number * $number; }));
    }

    /**
     * @test
     */
    function it_converts_payload_to_data()
    {
        $data = String::fromNativeValue('Hello');

        $to_data = Func::get('to_data');

        $payload = Payload::fromType($data);

        $this->assertEquals('Hello', $to_data($payload));
    }

    /**
     * @test
     */
    function it_manipulates_data_of_payload_with_callback()
    {
        $addWorld = Func::prepare('manipulate', null, function ($string) { return $string . ' World'; });

        $payload = Payload::fromType(String::fromNativeValue('Hello'));

        $addWorld($payload);

        $this->assertEquals('Hello World', $payload->extractTypeData());
    }

    /**
     * @test
     */
    public function it_applies_premap_callback_to_payload_collection()
    {
        $stringCollection = [
            "a string",
            100,
            "yet another string"
        ];

        $collection = StringCollection::fromNativeValue($stringCollection);

        $payload = Payload::fromType($collection);

        $string_cast = Func::prepare('premap', null, function($item, $key, \Iterator $collection) {
            return (string)$item;
        });

        $string_cast($payload);

        $this->assertEquals([
            "a string",
            "100",
            "yet another string"
        ], $payload->extractTypeData());
    }
} 