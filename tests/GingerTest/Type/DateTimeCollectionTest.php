<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.07.14 - 19:25
 */

namespace GingerTest\Type;

use Ginger\Type\DateTimeCollection;
use GingerTest\TestCase;

class DateTimeCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_a_collection_from_array_containing_datetimes()
    {
        $dates = DateTimeCollection::fromNativeValue(array(
            new \DateTime("10.02.2014"),
            new \DateTime("2014-07-11 20:55:10"),
        ));

        $this->assertInstanceOf('Ginger\Type\DateTimeCollection', $dates);

        $dateList = array();

        foreach ($dates->value() as $date) {
            $dateList[] = $date->value()->format("Y-m-d");
        }

        $this->assertEquals(array("2014-02-10", "2014-07-11"), $dateList);
    }

    /**
     * @test
     */
    public function it_constructs_collection_from_json_decoded_value()
    {
        $dates = DateTimeCollection::fromNativeValue(array(
            new \DateTime("10.02.2014"),
            new \DateTime("2014-07-11 20:55:10"),
        ));

        $jsonString = json_encode($dates);

        $decodedJson = json_decode($jsonString);

        $decodedDates = DateTimeCollection::fromJsonDecodedData($decodedJson);

        $dateList = array();

        foreach ($decodedDates->value() as $date) {
            $dateList[] = $date->value()->format("Y-m-d");
        }

        $this->assertEquals(array("2014-02-10", "2014-07-11"), $dateList);
    }

}
 