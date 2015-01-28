<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 28.01.15 - 22:54
 */

namespace GingerTest\Type;

use Ginger\Type\DateTimeOrNull;
use GingerTest\TestCase;

final class DateTimeOrNullTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideValues
     */
    public function it_constructs_new_instance_correctly($possibleValue, $shouldBeNull)
    {
        $valOrNull = DateTimeOrNull::fromNativeValue($possibleValue);

        $this->assertInstanceOf('Ginger\Type\DateTimeOrNull', $valOrNull);
        $this->assertEquals($possibleValue, $valOrNull->value());

        $asString = $valOrNull->toString();

        $fromString = DateTimeOrNull::fromString($asString);

        $this->assertTrue($valOrNull->sameAs($fromString));

        $asJson = json_encode($valOrNull);

        $fromJson = DateTimeOrNull::fromJsonDecodedData(json_decode($asJson));

        $this->assertTrue($valOrNull->sameAs($fromJson));
        $this->assertSame($shouldBeNull, $valOrNull->isNull());
    }

    public function provideValues()
    {
        return [
            [
                new \DateTime(),
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
        $dateTime = DateTimeOrNull::fromNativeValue(new \DateTime('2014-07-09 20:50:10'));

        $description = $dateTime->description();

        $this->assertEquals('DateTime', $description->label());
        $this->assertEquals('datetime', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }

    /**
     * @test
     */
    public function it_constructs_a_prototype()
    {
        $dateTimePrototype = DateTimeOrNull::prototype();

        $this->assertEquals('Ginger\Type\DateTimeOrNull', $dateTimePrototype->of());

        $description = $dateTimePrototype->typeDescription();

        $this->assertEquals('DateTime', $description->label());
        $this->assertEquals('datetime', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }
}
 