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

use Ginger\Type\Float;
use GingerTest\TestCase;

/**
 * Class FloatTest
 *
 * @package GingerTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class FloatTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_new_instance_from_float_value()
    {
        $float = Float::fromNativeValue(10.1);

        $this->assertInstanceOf('Ginger\Type\Float', $float);
        $this->assertEquals(10.1, $float->value());
    }

    /**
     * @test
     */
    public function it_constructs_new_instance_from_string_representing_of_a_float()
    {
        $float = Float::fromString("10.1");

        $this->assertInstanceOf('Ginger\Type\Float', $float);
        $this->assertSame(10.1, $float->value());
    }

    /**
     * @test
     */
    public function it_rejects_value_if_it_is_not_a_float_or_integer()
    {
        $this->setExpectedException('\InvalidArgumentException');

        Float::fromNativeValue("10.1");
    }

    /**
     * @test
     */
    public function it_accepts_zero_as_value()
    {
        $float = Float::fromNativeValue(0.0);

        $this->assertSame(0.0, $float->value());
    }

    /**
     * @test
     */
    public function it_converts_float_to_string()
    {
        $float = Float::fromNativeValue(10.1);

        $this->assertSame("10.1", $float->toString());
    }

    /**
     * @test
     */
    public function it_has_a_convenient_description()
    {
        $float = Float::fromNativeValue(10.1);

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
        $float = Float::prototype();

        $this->assertInstanceOf('Ginger\Type\Float', $float);

        $description = $float->description();

        $this->assertEquals('Float', $description->label());
        $this->assertEquals('float', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());

        $this->assertNull($float->value());
    }

    /**
     * @test
     */
    public function it_is_same_value_as_equal_float()
    {
        $float1 = Float::fromNativeValue(10.1);

        $float2 = Float::fromNativeValue(10.1);

        $float3 = Float::fromNativeValue(10.0);

        $this->assertTrue($float1->sameAs($float2));

        $this->assertFalse($float1->sameAs($float3));
    }
}
 