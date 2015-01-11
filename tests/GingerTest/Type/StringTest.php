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

use Ginger\Type\String;
use GingerTest\TestCase;

/**
 * Class StringTest
 *
 * @package GingerTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StringTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_new_instance_from_string_value()
    {
        $string = String::fromNativeValue("Hello world");

        $this->assertInstanceOf('Ginger\Type\String', $string);
        $this->assertEquals("Hello world", $string->value());
    }

    /**
     * @test
     */
    public function it_rejects_value_if_it_is_not_a_sting()
    {
        $this->setExpectedException('Ginger\Type\Exception\InvalidTypeException');

        String::fromNativeValue(10);
    }

    /**
     * @test
     */
    public function it_accepts_empty_sting_as_value()
    {
        $string = String::fromNativeValue("");

        $this->assertSame("", $string->value());
    }

    /**
     * @test
     */
    public function it_also_returns_string_from_toString_method()
    {
        $string = String::fromNativeValue("Hello World");

        $this->assertSame("Hello World", $string->toString());
    }

    /**
     * @test
     */
    public function it_has_a_convenient_description()
    {
        $string = String::fromNativeValue("Hello World");

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
        $stringPrototype = String::prototype();

        $this->assertEquals('Ginger\Type\String', $stringPrototype->of());

        $description = $stringPrototype->typeDescription();

        $this->assertEquals('String', $description->label());
        $this->assertEquals('string', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }

    /**
     * @test
     * @expectedException \Ginger\Type\Exception\InvalidTypeException
     */
    public function it_only_allows_utf8_encoded_string()
    {
        $nonUtf8 = mb_convert_encoding("Ü", "ISO-8859-1", "UTF-8");

        String::fromString($nonUtf8);
    }
}
 