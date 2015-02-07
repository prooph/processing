<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 08.07.14 - 21:33
 */

namespace Prooph\ProcessingTest\Type;

use Prooph\Processing\Type\Boolean;
use Prooph\Processing\Type\Exception\InvalidTypeException;
use Prooph\ProcessingTest\TestCase;

/**
 * Class BooleanTest
 *
 * @package Prooph\ProcessingTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class BooleanTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_new_instance_from_boolean_value()
    {
        $bool = Boolean::fromNativeValue(true);

        $this->assertInstanceOf('Prooph\Processing\Type\Boolean', $bool);
        $this->assertEquals(10, $bool->value());
    }

    /**
     * @test
     */
    public function it_constructs_new_instance_from_string_representing_a_boolean()
    {
        $boolTrue = Boolean::fromString("1");

        $this->assertInstanceOf('Prooph\Processing\Type\Boolean', $boolTrue);
        $this->assertSame(true, $boolTrue->value());

        $boolFalse = Boolean::fromString("");

        $this->assertInstanceOf('Prooph\Processing\Type\Boolean', $boolFalse);
        $this->assertSame(false, $boolFalse->value());
    }

    /**
     * @test
     */
    public function it_rejects_value_if_it_is_not_an_boolean()
    {
        $prototype = null;

        try {
            Boolean::fromNativeValue(1);
        } catch (InvalidTypeException $invalidTypeException) {
            $prototype = $invalidTypeException->getPrototypeOfRelatedType();
        }

        $this->assertInstanceOf('Prooph\Processing\Type\Prototype', $prototype);

        $this->assertEquals('Prooph\Processing\Type\Boolean', $prototype->of());

    }

    /**
     * @test
     */
    public function it_converts_boolean_to_string()
    {
        $boolFalse = Boolean::fromNativeValue(false);

        $this->assertSame("", $boolFalse->toString());

        $boolTrue = Boolean::fromNativeValue(true);

        $this->assertSame("1", $boolTrue->toString());
    }

    /**
     * @test
     */
    public function it_has_a_convenient_description()
    {
        $bool = Boolean::fromNativeValue(true);

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
        $boolPrototype = Boolean::prototype();

        $this->assertEquals('Prooph\Processing\Type\Boolean', $boolPrototype->of());

        $description = $boolPrototype->typeDescription();

        $this->assertEquals('Boolean', $description->label());
        $this->assertEquals('boolean', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }
}
 