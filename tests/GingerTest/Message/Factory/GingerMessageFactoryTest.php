<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 20:32
 */

namespace GingerTest\Message\Factory;

use Ginger\Message\Factory\GingerMessageFactoryFactory;
use Ginger\Message\MessageNameUtils;
use Ginger\Message\Payload;
use Ginger\Message\WorkflowMessage;
use GingerTest\TestCase;
use GingerTest\Type\Mock\UserDictionary;
use Prooph\ServiceBus\Message\MessageHeader;
use Prooph\ServiceBus\Message\StandardMessage;
use Zend\ServiceManager\ServiceManager;

/**
 * Class GingerMessageFactoryTest
 *
 * @package GingerTest\Message\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class GingerMessageFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_service_bus_message_from_workflow_command()
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

        $wfMessage = WorkflowMessage::newDataCollected($user);

        $wfMessage->prepareDataProcessing();

        $messageFactoryFactory = new GingerMessageFactoryFactory();

        $serviceLocator = new ServiceManager();

        $this->assertTrue($messageFactoryFactory->canCreateServiceWithName(
            $serviceLocator,
            $wfMessage->getMessageName(),
            $wfMessage->getMessageName()
        ));

        $messageFactory = $messageFactoryFactory->createServiceWithName(
            $serviceLocator,
            $wfMessage->getMessageName(),
            $wfMessage->getMessageName()
        );

        /** @var $serviceBusMessage StandardMessage */
        $serviceBusMessage = $messageFactory->fromCommand($wfMessage, 'testcase');

        $this->assertInstanceOf('Prooph\ServiceBus\Message\StandardMessage', $serviceBusMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertesttypemockuserdictionary-process-data',
            $serviceBusMessage->name()
        );

        $wfPayload = Payload::fromJsonDecodedData(json_decode($serviceBusMessage->payload()['json'], true));

        $this->assertEquals($userData, $wfPayload->getData());

        $this->assertTrue($serviceBusMessage->header()->uuid()->equals($wfMessage->getUuid()));

        $this->assertEquals(
            $serviceBusMessage->header()->createdOn()->format("Y-m-d H:i:s"),
            $wfMessage->getCreatedOn()->format("Y-m-d H:i:s")
        );

        $this->assertEquals($serviceBusMessage->header()->version(), $wfMessage->getVersion());

        $this->assertEquals('testcase', $serviceBusMessage->header()->sender());
        $this->assertEquals(MessageHeader::TYPE_COMMAND, $serviceBusMessage->header()->type());
    }

    /**
     * @test
     */
    public function it_creates_a_service_bus_message_from_workflow_event()
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

        $wfMessage = WorkflowMessage::newDataCollected($user);

        $messageFactoryFactory = new GingerMessageFactoryFactory();

        $serviceLocator = new ServiceManager();

        $this->assertTrue($messageFactoryFactory->canCreateServiceWithName(
            $serviceLocator,
            $wfMessage->getMessageName(),
            $wfMessage->getMessageName()
        ));

        $messageFactory = $messageFactoryFactory->createServiceWithName(
            $serviceLocator,
            $wfMessage->getMessageName(),
            $wfMessage->getMessageName()
        );

        /** @var $serviceBusMessage StandardMessage */
        $serviceBusMessage = $messageFactory->fromEvent($wfMessage, 'testcase');

        $this->assertInstanceOf('Prooph\ServiceBus\Message\StandardMessage', $serviceBusMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertesttypemockuserdictionary-data-collected',
            $serviceBusMessage->name()
        );

        $wfPayload = Payload::fromJsonDecodedData(json_decode($serviceBusMessage->payload()['json'], true));

        $this->assertEquals($userData, $wfPayload->getData());

        $this->assertTrue($serviceBusMessage->header()->uuid()->equals($wfMessage->getUuid()));

        $this->assertEquals(
            $serviceBusMessage->header()->createdOn()->format("Y-m-d H:i:s"),
            $wfMessage->getCreatedOn()->format("Y-m-d H:i:s")
        );

        $this->assertEquals($serviceBusMessage->header()->version(), $wfMessage->getVersion());

        $this->assertEquals('testcase', $serviceBusMessage->header()->sender());
        $this->assertEquals(MessageHeader::TYPE_EVENT, $serviceBusMessage->header()->type());
    }
}
 