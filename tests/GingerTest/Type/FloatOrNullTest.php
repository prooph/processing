<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 28.01.15 - 23:05
 */

namespace GingerTest\Type;

use Ginger\Type\FloatOrNull;
use GingerTest\TestCase;

/**
 * Class FloatOrNullTest
 *
 * @package GingerTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class FloatOrNullTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideValues
     */
    public function it_constructs_new_instance_correctly($possibleValue, $shouldBeNull)
    {
        $valOrNull = FloatOrNull::fromNativeValue($possibleValue);

        $this->assertInstanceOf('Ginger\Type\FloatOrNull', $valOrNull);
        $this->assertEquals($possibleValue, $valOrNull->value());

        $asString = $valOrNull->toString();

        $fromString = FloatOrNull::fromString($asString);

        $this->assertTrue($valOrNull->sameAs($fromString));

        $asJson = json_encode($valOrNull);

        $fromJson = FloatOrNull::fromJsonDecodedData(json_decode($asJson));

        $this->assertTrue($valOrNull->sameAs($fromJson));
        $this->assertSame($shouldBeNull, $valOrNull->isNull());
    }

    public function provideValues()
    {
        return [
            [
                1.0,
                false,
            ],
            [
                22.4,
                false,
            ],
            [
                0.0,
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
        $float = FloatOrNull::fromNativeValue(10.1);

        $description = $float->description();

        $this->assertEquals('Float', $description->label());
        $this->assertEquals('float', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }

    /**
     * @test
     */
    public function it_constructs_a_prototype()
    {
        $floatPrototype = FloatOrNull::prototype();

        $this->assertEquals('Ginger\Type\FloatOrNull', $floatPrototype->of());

        $description = $floatPrototype->typeDescription();

        $this->assertEquals('Float', $description->label());
        $this->assertEquals('float', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }
}
 