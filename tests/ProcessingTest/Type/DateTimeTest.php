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

use Prooph\Processing\Type\DateTime;
use Prooph\Processing\Type\Integer;
use Prooph\ProcessingTest\TestCase;

/**
 * Class DateTimeTest
 *
 * @package Prooph\ProcessingTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class DateTimeTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_new_instance_from_native_datetime()
    {
        $nativeDateTime = new \DateTime('2014-07-09 20:50:10');

        $dateTime = DateTime::fromNativeValue($nativeDateTime);

        $this->assertInstanceOf('Prooph\Processing\Type\DateTime', $dateTime);
        $this->assertEquals($nativeDateTime->format('Y-m-d H:i:s'), $dateTime->value()->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function its_value_is_immutable()
    {
        $nativeDateTime = new \DateTime('2014-07-09 20:50:10');

        $dateTime = DateTime::fromNativeValue($nativeDateTime);

        $nativeDateTime->sub(new \DateInterval('P2D'));

        $this->assertEquals('2014-07-09 20:50:10', $dateTime->value()->format('Y-m-d H:i:s'));

        $dateTime->value()->sub(new \DateInterval('P2D'));

        $this->assertEquals('2014-07-09 20:50:10', $dateTime->value()->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function it_constructs_new_instance_from_string_representing_of_an_datetime()
    {
        $dateTime = DateTime::fromString('2014-07-09 20:50:10');

        $this->assertInstanceOf('Prooph\Processing\Type\DateTime', $dateTime);
        $this->assertSame('2014-07-09 20:50:10', $dateTime->value()->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function it_rejects_value_if_it_is_not_a_date_time()
    {
        $this->setExpectedException('Prooph\Processing\Type\Exception\InvalidTypeException');

        DateTime::fromNativeValue('2014-07-09 20:50:10');
    }

    /**
     * @test
     */
    public function it_converts_datetime_to_string()
    {
        $dateTime = DateTime::fromNativeValue(new \DateTime('2014-07-09T20:50:10+0200'));

        $this->assertSame("2014-07-09T20:50:10+0200", $dateTime->toString());
    }

    /**
     * @test
     */
    public function it_has_a_convenient_description()
    {
        $dateTime = DateTime::fromNativeValue(new \DateTime('2014-07-09 20:50:10'));

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
        $dateTimePrototype = DateTime::prototype();

        $this->assertEquals('Prooph\Processing\Type\DateTime', $dateTimePrototype->of());

        $description = $dateTimePrototype->typeDescription();

        $this->assertEquals('DateTime', $description->label());
        $this->assertEquals('datetime', $description->nativeType());
        $this->assertFalse($description->hasIdentifier());
    }

    /**
     * @test
     */
    public function it_is_same_value_as_equal_datetime()
    {
        $dateTime1 = DateTime::fromNativeValue(new \DateTime('2014-07-09 20:50:10'));

        $dateTime2 = DateTime::fromNativeValue(new \DateTime('2014-07-09 20:50:10'));

        $dateTime3 = DateTime::fromNativeValue(new \DateTime('2014-07-09 20:51:10'));

        $this->assertTrue($dateTime1->sameAs($dateTime2));

        $this->assertFalse($dateTime1->sameAs($dateTime3));
    }

    /**
     * @test
     */
    public function it_uses_the_toString_method_when_it_is_passed_to_json_encode()
    {
        $dateTime1 = DateTime::fromNativeValue(new \DateTime('2014-07-09 20:50:10'));

        $jsonString = json_encode(array("datetime" => $dateTime1));

        $jsonStringCheck = json_encode(array("datetime" => $dateTime1->value()->format(\DateTime::ISO8601)));

        $this->assertEquals($jsonStringCheck, $jsonString);

        $decodedJson = json_decode($jsonString, true);

        $dateTimeDecoded = DateTime::fromJsonDecodedData($decodedJson["datetime"]);

        $this->assertTrue($dateTime1->sameAs($dateTimeDecoded));
    }
}
 