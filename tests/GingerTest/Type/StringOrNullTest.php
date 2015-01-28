<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 28.01.15 - 23:32
 */

namespace GingerTest\Type;

use Ginger\Type\StringOrNull;
use GingerTest\TestCase;

/**
 * Class StringOrNullTest
 *
 * @package GingerTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class StringOrNullTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideValues
     */
    public function it_constructs_new_instance_correctly($possibleValue, $shouldBeNull)
    {
        $valOrNull = StringOrNull::fromNativeValue($possibleValue);

        $this->assertInstanceOf('Ginger\Type\StringOrNull', $valOrNull);
        $this->assertEquals($possibleValue, $valOrNull->value());

        $asString = $valOrNull->toString();

        $fromString = StringOrNull::fromString($asString);

        $this->assertTrue($valOrNull->sameAs($fromString));

        $asJson = json_encode($valOrNull);

        $fromJson = StringOrNull::fromJsonDecodedData(json_decode($asJson));

        $this->assertTrue($valOrNull->sameAs($fromJson));
        $this->assertSame($shouldBeNull, $valOrNull->isNull());
    }

    public function provideValues()
    {
        return [
            [
                "I am a string",
                false,
            ],
            [
                "Ähm ich auch",
                false,
            ],
            [
                "",
                false,
            ],
            [
                "Multi line\nstring",
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
        $string = StringOrNull::fromNativeValue("Hello World");

        $description = $string->description();

        $this->assertEquals('String', $description->label());
        $this->assertEquals('string', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }

    /**
     * @test
     */
    public function it_constructs_a_prototype()
    {
        $stringPrototype = StringOrNull::prototype();

        $this->assertEquals('Ginger\Type\StringOrNull', $stringPrototype->of());

        $description = $stringPrototype->typeDescription();

        $this->assertEquals('String', $description->label());
        $this->assertEquals('string', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }
}
 