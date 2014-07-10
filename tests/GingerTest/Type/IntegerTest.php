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

use Ginger\Type\Integer;
use GingerTest\TestCase;

/**
 * Class IntegerTest
 *
 * @package GingerTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class IntegerTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_new_instance_from_integer_value()
    {
        $int = Integer::fromNativeValue(10);

        $this->assertInstanceOf('Ginger\Type\Integer', $int);
        $this->assertEquals(10, $int->value());
    }

    /**
     * @test
     */
    public function it_constructs_new_instance_from_string_representing_of_an_integer()
    {
        $int = Integer::fromString("10");

        $this->assertInstanceOf('Ginger\Type\Integer', $int);
        $this->assertSame(10, $int->value());
    }

    /**
     * @test
     */
    public function it_rejects_value_if_it_is_not_an_integer()
    {
        $this->setExpectedException('\InvalidArgumentException');

        Integer::fromNativeValue(array(10));
    }

    /**
     * @test
     */
    public function it_accepts_zero_as_value()
    {
        $int = Integer::fromNativeValue(0);

        $this->assertSame(0, $int->value());
    }

    /**
     * @test
     */
    public function it_converts_integer_to_string()
    {
        $int = Integer::fromNativeValue(10);

        $this->assertSame("10", $int->toString());
    }

    /**
     * @test
     */
    public function it_has_a_convenient_description()
    {
        $int = Integer::fromNativeValue(10);

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
        $intPrototype = Integer::prototype();

        $this->assertInstanceOf('Ginger\Type\Prototype', $intPrototype);

        $description = $intPrototype->typeDescription();

        $this->assertEquals('Ginger\Type\Integer', $intPrototype->of());
        $this->assertEquals('Integer', $description->label());
        $this->assertEquals('integer', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }

    /**
     * @test
     */
    public function it_is_same_value_as_equal_integer()
    {
        $int1 = Integer::fromNativeValue(10);

        $int2 = Integer::fromNativeValue(10);

        $int3 = Integer::fromNativeValue(20);

        $this->assertTrue($int1->sameAs($int2));

        $this->assertFalse($int1->sameAs($int3));
    }
}
 