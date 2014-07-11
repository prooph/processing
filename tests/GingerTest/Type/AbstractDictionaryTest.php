<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.07.14 - 20:35
 */

namespace GingerTest\Type;

use Ginger\Type\Exception\InvalidTypeException;
use GingerTest\TestCase;
use GingerTest\Type\Mock\AddressDictionary;
use GingerTest\Type\Mock\UserDictionary;

/**
 * Class AbstractDictionaryTest
 *
 * @package GingerTest\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AbstractDictionaryTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_user_from_native_value()
    {
        $user = UserDictionary::fromNativeValue(array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        ));

        $this->assertInstanceOf('GingerTest\Type\Mock\UserDictionary', $user);

        $this->assertInstanceOf('Ginger\Type\Integer', $user->property("id")->type());

        $this->assertEquals(1, $user->property("id")->value());

        $this->assertInstanceOf('Ginger\Type\String', $user->property("name")->type());

        $this->assertEquals("Alex", $user->property("name")->value());

        $this->assertInstanceOf('GingerTest\Type\Mock\AddressDictionary', $user->property("address")->type());

        $this->assertInstanceOf('Ginger\Type\String', $user->property("address")->type()->property("street")->type());

        $this->assertEquals("Main Street", $user->property("address")->type()->property("street")->value());

        $this->assertInstanceOf('Ginger\Type\Integer', $user->property("address")->type()->property("streetNumber")->type());

        $this->assertEquals(10, $user->property("address")->type()->property("streetNumber")->value());

        $this->assertInstanceOf('Ginger\Type\String', $user->property("address")->type()->property("zip")->type());

        $this->assertEquals("12345", $user->property("address")->type()->property("zip")->value());

        $this->assertInstanceOf('Ginger\Type\String', $user->property("address")->type()->property("city")->type());

        $this->assertEquals("Test City", $user->property("address")->type()->property("city")->value());
    }

    /**
     * @test
     */
    public function it_converts_to_string_and_back()
    {
        $user = UserDictionary::fromNativeValue(array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        ));

        $userString = $user->toString();

        $sameUser = UserDictionary::fromString($userString);

        $this->assertInstanceOf('GingerTest\Type\Mock\UserDictionary', $sameUser);

        $this->assertInstanceOf('Ginger\Type\Integer', $sameUser->property("id")->type());

        $this->assertEquals(1, $sameUser->property("id")->value());

        $this->assertInstanceOf('Ginger\Type\String', $sameUser->property("name")->type());

        $this->assertEquals("Alex", $sameUser->property("name")->value());

        $this->assertInstanceOf('GingerTest\Type\Mock\AddressDictionary', $sameUser->property("address")->type());

        $this->assertInstanceOf('Ginger\Type\String', $sameUser->property("address")->type()->property("street")->type());

        $this->assertEquals("Main Street", $sameUser->property("address")->type()->property("street")->value());

        $this->assertInstanceOf('Ginger\Type\Integer', $sameUser->property("address")->type()->property("streetNumber")->type());

        $this->assertEquals(10, $sameUser->property("address")->type()->property("streetNumber")->value());

        $this->assertInstanceOf('Ginger\Type\String', $sameUser->property("address")->type()->property("zip")->type());

        $this->assertEquals("12345", $sameUser->property("address")->type()->property("zip")->value());

        $this->assertInstanceOf('Ginger\Type\String', $sameUser->property("address")->type()->property("city")->type());

        $this->assertEquals("Test City", $sameUser->property("address")->type()->property("city")->value());
    }

    /**
     * @test
     */
    public function it_converts_to_json_and_back()
    {
        $user = UserDictionary::fromNativeValue(array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        ));

        $jsonUserString = json_encode($user);

        $encodedUserData = json_decode($jsonUserString, true);

        $sameUser = UserDictionary::jsonDecode($encodedUserData);

        $this->assertInstanceOf('GingerTest\Type\Mock\UserDictionary', $sameUser);

        $this->assertInstanceOf('Ginger\Type\Integer', $sameUser->property("id")->type());

        $this->assertEquals(1, $sameUser->property("id")->value());

        $this->assertInstanceOf('Ginger\Type\String', $sameUser->property("name")->type());

        $this->assertEquals("Alex", $sameUser->property("name")->value());

        $this->assertInstanceOf('GingerTest\Type\Mock\AddressDictionary', $sameUser->property("address")->type());

        $this->assertInstanceOf('Ginger\Type\String', $sameUser->property("address")->type()->property("street")->type());

        $this->assertEquals("Main Street", $sameUser->property("address")->type()->property("street")->value());

        $this->assertInstanceOf('Ginger\Type\Integer', $sameUser->property("address")->type()->property("streetNumber")->type());

        $this->assertEquals(10, $sameUser->property("address")->type()->property("streetNumber")->value());

        $this->assertInstanceOf('Ginger\Type\String', $sameUser->property("address")->type()->property("zip")->type());

        $this->assertEquals("12345", $sameUser->property("address")->type()->property("zip")->value());

        $this->assertInstanceOf('Ginger\Type\String', $sameUser->property("address")->type()->property("city")->type());

        $this->assertEquals("Test City", $sameUser->property("address")->type()->property("city")->value());
    }

    /**
     * @test
     */
    public function it_rejects_value_if_it_is_not_an_array()
    {
        $prototype = null;

        try {
            UserDictionary::fromNativeValue("not an array");
        } catch (InvalidTypeException $invalidTypeException) {
            $prototype = $invalidTypeException->getPrototypeOfRelatedType();
        }

        $this->assertInstanceOf('Ginger\Type\Prototype', $prototype);

        $this->assertEquals('GingerTest\Type\Mock\UserDictionary', $prototype->of());
    }

    /**
     * @test
     */
    public function it_rejects_value_if_a_property_name_is_missing()
    {
        $prototype = null;

        try {
            UserDictionary::fromNativeValue(array(
                'id' => 1,
                'address' => array(
                    'street' => 'Main Street',
                    'streetNumber' => 10,
                    'zip' => '12345',
                    'city' => 'Test City'
                )
            ));
        } catch (InvalidTypeException $invalidTypeException) {
            $prototype = $invalidTypeException->getPrototypeOfRelatedType();
        }

        $this->assertInstanceOf('Ginger\Type\Prototype', $prototype);

        $this->assertEquals('GingerTest\Type\Mock\UserDictionary', $prototype->of());
    }

    /**
     * @test
     */
    public function it_rejects_value_if_unknown_property_is_given()
    {
        $prototype = null;

        try {
            UserDictionary::fromNativeValue(array(
                'id' => 1,
                'name' => 'Alex',
                'email' => 'contact@prooph.de',
                'address' => array(
                    'street' => 'Main Street',
                    'streetNumber' => 10,
                    'zip' => '12345',
                    'city' => 'Test City'
                )
            ));
        } catch (InvalidTypeException $invalidTypeException) {
            $prototype = $invalidTypeException->getPrototypeOfRelatedType();
        }

        $this->assertInstanceOf('Ginger\Type\Prototype', $prototype);

        $this->assertEquals('GingerTest\Type\Mock\UserDictionary', $prototype->of());
    }

    /**
     * @test
     */
    public function it_rejects_value_if_property_in_child_dictionary_is_missing()
    {
        $prototype = null;

        try {
            UserDictionary::fromNativeValue(array(
                'id' => 1,
                'name' => 'Alex',
                'address' => array(
                    'streetNumber' => 10,
                    'zip' => '12345',
                    'city' => 'Test City'
                )
            ));
        } catch (InvalidTypeException $invalidTypeException) {
            $prototype = $invalidTypeException->getPrototypeOfRelatedType();
        }

        $this->assertInstanceOf('Ginger\Type\Prototype', $prototype);

        $this->assertEquals('GingerTest\Type\Mock\UserDictionary', $prototype->of());
    }

    /**
     * @test
     */
    public function it_has_a_convenient_description()
    {
        $user = UserDictionary::fromNativeValue(array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        ));

        $description = $user->description();

        $this->assertEquals('User', $description->label());
        $this->assertEquals('dictionary', $description->nativeType());
        $this->assertTrue($description->hasIdentifier());
        $this->assertEquals("id", $description->identifierName());

        $addressDescription = $user->property('address')->type()->description();

        $this->assertEquals('Address', $addressDescription->label());
        $this->assertEquals('dictionary', $addressDescription->nativeType());
        $this->assertFalse($addressDescription->hasIdentifier());
    }

    /**
     * @test
     */
    public function it_compares_dictionaries_with_identifier_by_identifier_value()
    {
        $user = UserDictionary::fromNativeValue(array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        ));

        $sameUserWithChangedName = UserDictionary::fromNativeValue(array(
            'id' => 1,
            'name' => 'Alexander',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        ));

        $otherUser = UserDictionary::fromNativeValue(array(
            'id' => 2,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        ));

        $this->assertTrue($user->sameAs($sameUserWithChangedName));
        $this->assertFalse($user->sameAs($otherUser));
    }

    /**
     * @test
     */
    public function it_compares_dictionaries_without_identifier_by_their_properties()
    {
        $address = AddressDictionary::fromNativeValue(array(
            'street' => 'Main Street',
            'streetNumber' => 10,
            'zip' => '12345',
            'city' => 'Test City'
        ));

        $sameAddress = AddressDictionary::fromNativeValue(array(
            'street' => 'Main Street',
            'streetNumber' => 10,
            'zip' => '12345',
            'city' => 'Test City'
        ));

        $otherAddress = AddressDictionary::fromNativeValue(array(
            'street' => 'Main Street',
            'streetNumber' => 10,
            'zip' => '12345',
            'city' => 'New York'
        ));

        $this->assertTrue($address->sameAs($sameAddress));
        $this->assertFalse($address->sameAs($otherAddress));
    }
}
 