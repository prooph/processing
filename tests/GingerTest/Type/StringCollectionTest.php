<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.07.14 - 21:40
 */

namespace GingerTest\Type;

use Ginger\Type\String;
use Ginger\Type\StringCollection;
use GingerTest\TestCase;

/**
 * Class StringCollectionTest
 *
 * @package GingerTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StringCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_a_collection_from_array_containing_string_types()
    {
        $fruits = StringCollection::fromNativeValue(array(
            String::fromNativeValue("Apple"),
            String::fromNativeValue("Banana"),
            String::fromNativeValue("Strawberry")
        ));

        $this->assertInstanceOf('Ginger\Type\StringCollection', $fruits);
    }

    //@TODO: Add more tests
}
 