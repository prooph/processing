<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 09.12.14 - 19:15
 */

namespace GingerTest\Type;

use Ginger\Type\UnknownCollection;
use GingerTest\TestCase;

/**
 * Class UnknownCollectionTest
 *
 * @package GingerTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class UnknownCollectionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideValidValues
     */
    public function it_can_be_initialized_with_valid_values_and_can_be_converted_to_string_or_json_and_back($value)
    {
        $unknownCollection = UnknownCollection::fromNativeValue($value);

        $unknownCollectionString = $unknownCollection->toString();

        $unknownCollectionFromString = UnknownCollection::fromString($unknownCollectionString);

        $this->assertTrue($unknownCollection->sameAs($unknownCollectionFromString));

        $unknownCollectionJson = json_encode($unknownCollection);

        $unknownCollectionFromJson = UnknownCollection::fromJsonDecodedData(json_decode($unknownCollectionJson, true));

        $this->assertTrue($unknownCollection->sameAs($unknownCollectionFromJson));
    }

    /**
     * @return array
     */
    public function provideValidValues()
    {
        return [
            [["TestString", "TestString2", "TestString3"]],
            [[10, 11, 12]],
            [[99.99, 100, 100.01]],
            [[false, true]],
            [[true, ["array"], ["mixed" => [1,2,3]]]],
            [[[10, 11, 12], [13, 14, 15]]],
        ];
    }
}
 