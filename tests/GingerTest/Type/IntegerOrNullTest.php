<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 28.01.15 - 23:27
 */

namespace GingerTest\Type;

use Ginger\Type\IntegerOrNull;
use GingerTest\TestCase;

final class IntegerOrNullTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideValues
     */
    public function it_constructs_new_instance_correctly($possibleValue, $shouldBeNull)
    {
        $valOrNull = IntegerOrNull::fromNativeValue($possibleValue);

        $this->assertInstanceOf('Ginger\Type\IntegerOrNull', $valOrNull);
        $this->assertEquals($possibleValue, $valOrNull->value());

        $asString = $valOrNull->toString();

        $fromString = IntegerOrNull::fromString($asString);

        $this->assertTrue($valOrNull->sameAs($fromString));

        $asJson = json_encode($valOrNull);

        $fromJson = IntegerOrNull::fromJsonDecodedData(json_decode($asJson));

        $this->assertTrue($valOrNull->sameAs($fromJson));
        $this->assertSame($shouldBeNull, $valOrNull->isNull());
    }

    public function provideValues()
    {
        return [
            [
                1,
                false,
            ],
            [
                22,
                false,
            ],
            [
                0,
                false,
            ],
            [
                -1,
                false,
            ],
            [
                -10,
                false,
            ],
            [
                null,
                true,
            ]
        ];
    }

    /**
     * @test
     */
    public function it_has_a_convenient_description()
    {
        $int = IntegerOrNull::fromNativeValue(10);

        $description = $int->description();

        $this->assertEquals('Integer', $description->label());
        $this->assertEquals('integer', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }

    /**
     * @test
     */
    public function it_constructs_a_prototype()
    {
        $intPrototype = IntegerOrNull::prototype();

        $this->assertInstanceOf('Ginger\Type\Prototype', $intPrototype);

        $description = $intPrototype->typeDescription();

        $this->assertEquals('Ginger\Type\IntegerOrNull', $intPrototype->of());
        $this->assertEquals('Integer', $description->label());
        $this->assertEquals('integer', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }
}
 