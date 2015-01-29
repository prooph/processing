<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 10:50
 */

namespace GingerTest\Message;

use Codeliner\ArrayReader\ArrayReader;
use Ginger\Message\Payload;
use GingerTest\TestCase;
use GingerTest\Mock\UserDictionary;

/**
 * Class PayloadTest
 *
 * @package GingerTest\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class PayloadTest extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_payload_from_type()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $payload = Payload::fromType($user);

        $this->assertInstanceOf('Ginger\Message\Payload', $payload);

        $this->assertEquals('GingerTest\Mock\UserDictionary', $payload->getTypeClass());

        $this->assertEquals($userData, $payload->extractTypeData());
    }

    /**
     * @test
     */
    public function it_encodes_and_decodes_payload_to_and_from_json()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $payload = Payload::fromType($user);

        $jsonString = json_encode($payload);

        $jsonDecodedData = json_decode($jsonString, true);

        $decodedPayload = Payload::fromJsonDecodedData($jsonDecodedData);

        $this->assertInstanceOf('Ginger\Message\Payload', $decodedPayload);

        $this->assertEquals('GingerTest\Mock\UserDictionary', $decodedPayload->getTypeClass());

        $this->assertEquals($userData, $decodedPayload->extractTypeData());
    }

    /**
     * @test
     */
    public function it_replaces_whole_data()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $payload = Payload::fromType($user);

        $newUserData = array(
            'id' => 2,
            'name' => 'Tom',
            'email' => 'tom@test.com'
        );

        $payload->replaceData($newUserData);

        $this->assertEquals($newUserData, $payload->extractTypeData());
        $this->assertEquals('Tom', (new ArrayReader($payload->extractTypeData()))->stringValue('name'));
    }

    /**
     * @test
     */
    public function it_can_convert_to_payload_reader()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $payload = Payload::fromType($user);

        $this->assertEquals('Main Street', (new ArrayReader($payload->extractTypeData()))->stringValue('address.street'));
    }

    /**
     * @test
     */
    public function it_can_convert_back_to_type()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $payload = Payload::fromType($user);

        $userFromPayload = $payload->toType();

        $this->assertTrue($user->property("address")->sameAs($userFromPayload->property("address")));
    }

    /**
     * @test
     */
    public function it_is_possible_to_change_type_class()
    {
        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $user = UserDictionary::fromNativeValue($userData);

        $payload = Payload::fromType($user);

        $payload->changeTypeClass('GingerTest\Mock\AddressDictionary');

        $payload->replaceData($userData['address']);

        $address = $payload->toType();

        $this->assertInstanceOf('GingerTest\Mock\AddressDictionary', $address);

        $this->assertTrue($user->property("address")->type()->sameAs($address));
    }

    /**
     * @test
     */
    public function it_constructs_payload_from_prototype()
    {
        $payload = Payload::fromPrototype(UserDictionary::prototype());

        $this->assertInstanceOf('Ginger\Message\Payload', $payload);

        $this->assertEquals('GingerTest\Mock\UserDictionary', $payload->getTypeClass());

        $this->assertNull($payload->extractTypeData());

        $userData = array(
            'id' => 1,
            'name' => 'Alex',
            'address' => array(
                'street' => 'Main Street',
                'streetNumber' => 10,
                'zip' => '12345',
                'city' => 'Test City'
            )
        );

        $payload->replaceData($userData);

        $user = $payload->toType();

        $this->assertEquals('Alex', $user->property("name")->value());
    }

    /**
     * @test
     */
    public function it_encodes_and_decodes_payload_with_prototype_to_and_from_json()
    {
        $payload = Payload::fromPrototype(UserDictionary::prototype());

        $jsonString = json_encode($payload);

        $jsonDecodedData = json_decode($jsonString, true);

        $decodedPayload = Payload::fromJsonDecodedData($jsonDecodedData);

        $this->assertEquals('GingerTest\Mock\UserDictionary', $decodedPayload->getTypeClass());
    }
}
 