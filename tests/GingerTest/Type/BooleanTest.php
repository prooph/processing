<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 08.07.14 - 21:33
 */

namespace GingerTest\Type;

use Ginger\Type\Boolean;
use GingerTest\TestCase;

/**
 * Class BooleanTest
 *
 * @package GingerTest\Type
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

        $this->assertInstanceOf('Ginger\Type\Boolean', $bool);
        $this->assertEquals(10, $bool->value());
    }

    /**
     * @test
     */
    public function it_constructs_new_instance_from_string_representing_of_a_boolean()
    {
        $boolTrue = Boolean::fromString("1");

        $this->assertInstanceOf('Ginger\Type\Boolean', $boolTrue);
        $this->assertSame(true, $boolTrue->value());

        $boolFalse = Boolean::fromString("");

        $this->assertInstanceOf('Ginger\Type\Boolean', $boolFalse);
        $this->assertSame(false, $boolFalse->value());
    }

    /**
     * @test
     */
    public function it_rejects_value_if_it_is_not_an_boolean()
    {
        $this->setExpectedException('\InvalidArgumentException');

        Boolean::fromNativeValue(1);
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

        $this->assertEquals('Ginger\Type\Boolean', $boolPrototype->of());

        $description = $boolPrototype->typeDescription();

        $this->assertEquals('Boolean', $description->label());
        $this->assertEquals('boolean', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }
}
 