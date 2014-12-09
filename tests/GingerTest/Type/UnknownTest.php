<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 09.12.14 - 18:29
 */

namespace GingerTest\Type;

use Ginger\Type\Unknown;
use GingerTest\TestCase;
use Zend\Filter\Null;

/**
 * Class UnknownTest
 *
 * @package GingerTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class UnknownTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideValidValues
     */
    public function it_can_be_initialized_with_valid_values_and_can_be_converted_to_string_or_json_and_back($value)
    {
        $unknown = Unknown::fromNativeValue($value);

        $unknownString = $unknown->toString();

        $unknownFromString = Unknown::fromString($unknownString);

        $this->assertTrue($unknown->sameAs($unknownFromString));

        $unknownJson = json_encode($unknown);

        $unknownFromJson = Unknown::fromJsonDecodedData(json_decode($unknownJson, true));

        $this->assertTrue($unknown->sameAs($unknownFromJson));
    }

    /**
     * @return array
     */
    public function provideValidValues()
    {
        return [
            ["TestString"],
            [10],
            [99.99],
            [false],
            [true],
            [[10, 11, 12]],
            ["a", "b", "c"],
            ["a" => "b", "c" => [30.4, 33.5]],
            ["very" => ["deep" => ["array" => ["with" => [1, 2, 3, 4]]]]]
        ];
    }

    /**
     * @test
     * @expectedException \Ginger\Type\Exception\InvalidTypeException
     * @dataProvider provideInvalidTypes
     */
    public function it_can_not_be_initialized_with_invalid_types($value)
    {
        Unknown::fromNativeValue($value);
    }

    /**
     * @return array
     */
    public function provideInvalidTypes()
    {

        return [
            [new \stdClass()],
            [fopen(__FILE__, 'r')],
            [Null],
            [["null" => Null]],
            [["object" => ["in" => ["array" => new \stdClass()]]]]
        ];
    }
}
 