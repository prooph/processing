<?php
/*
 * This file is part of the prooph processing framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 28.01.15 - 22:14
 */

namespace Prooph\ProcessingTest\Type;

use Prooph\Processing\Type\BooleanOrNull;
use Prooph\Processing\Type\Exception\InvalidTypeException;
use Prooph\ProcessingTest\TestCase;

/**
 * Class BooleanOrNullTest
 *
 * @package Prooph\ProcessingTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class BooleanOrNullTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideValues
     */
    public function it_constructs_new_instance_correctly($possibleValue, $shouldBeNull)
    {
        $boolOrNull = BooleanOrNull::fromNativeValue($possibleValue);

        $this->assertInstanceOf('Prooph\Processing\Type\BooleanOrNull', $boolOrNull);
        $this->assertSame($possibleValue, $boolOrNull->value());

        $asString = $boolOrNull->toString();

        $fromString = BooleanOrNull::fromString($asString);

        $this->assertTrue($boolOrNull->sameAs($fromString));

        $asJson = json_encode($boolOrNull);

        $fromJson = BooleanOrNull::fromJsonDecodedData(json_decode($asJson));

        $this->assertTrue($boolOrNull->sameAs($fromJson));
        $this->assertSame($shouldBeNull, $boolOrNull->isNull());
    }

    public function provideValues()
    {
        return [
            [
                true,
                false,
            ],
            [
                false,
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
    public function it_rejects_value_if_it_is_not_an_boolean()
    {
        $prototype = null;

        try {
            BooleanOrNull::fromNativeValue(1);
        } catch (InvalidTypeException $invalidTypeException) {
            $prototype = $invalidTypeException->getPrototypeOfRelatedType();
        }

        $this->assertInstanceOf('Prooph\Processing\Type\Prototype', $prototype);

        $this->assertEquals('Prooph\Processing\Type\BooleanOrNull', $prototype->of());

    }

    /**
     * @test
     */
    public function it_has_a_convenient_description()
    {
        $bool = BooleanOrNull::fromNativeValue(true);

        $description = $bool->description();

        $this->assertEquals('Boolean', $description->label());
        $this->assertEquals('boolean', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }

    /**
     * @test
     */
    public function it_constructs_a_prototype()
    {
        $boolPrototype = BooleanOrNull::prototype();

        $this->assertEquals('Prooph\Processing\Type\BooleanOrNull', $boolPrototype->of());

        $description = $boolPrototype->typeDescription();

        $this->assertEquals('Boolean', $description->label());
        $this->assertEquals('boolean', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }
}
 