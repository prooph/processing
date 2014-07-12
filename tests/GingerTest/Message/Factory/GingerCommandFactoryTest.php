<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 19:54
 */

namespace GingerTest\Message\Factory;
use Ginger\Message\Factory\GingerCommandFactoryFactory;
use Ginger\Message\MessageNameUtils;
use Ginger\Message\Payload;
use GingerTest\TestCase;
use GingerTest\Type\Mock\UserDictionary;
use Prooph\ServiceBus\Message\MessageHeader;
use Prooph\ServiceBus\Message\StandardMessage;
use Rhumsaa\Uuid\Uuid;
use Zend\ServiceManager\ServiceManager;

/**
 * Class GingerCommandFactoryTest
 *
 * @package GingerTest\Message\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class GingerCommandFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_detects_ginger_command_by_name_and_reconstructs_it_from_service_bus_message()
    {
        $uuid = Uuid::uuid4();
        $createdOn = new \DateTime();
        $version = 1;
        $sender = "testcase";
        $messageName = 'ginger-message-gingertesttypemockuserdictionary-process-data';

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

        $workflowMessagePayload = Payload::fromType($user);

        $messageHeader = new MessageHeader($uuid, $createdOn, $version, $sender, MessageHeader::TYPE_COMMAND);

        $serviceBusMessage = new StandardMessage(
            $messageName,
            $messageHeader,
            array('json' => json_encode($workflowMessagePayload))
        );

        $commandFactoryFactory = new GingerCommandFactoryFactory();

        $serviceLocator = new ServiceManager();

        $this->assertTrue($commandFactoryFactory->canCreateServiceWithName(
            $serviceLocator,
            $messageName,
            $messageName
        ));

        $commandFactory = $commandFactoryFactory->createServiceWithName(
            $serviceLocator,
            $messageName,
            $messageName
        );

        $wfMessage = $commandFactory->fromMessage($serviceBusMessage);

        $this->assertInstanceOf('Ginger\Message\WorkflowMessage', $wfMessage);

        $this->assertEquals(
            MessageNameUtils::MESSAGE_NAME_PREFIX . 'gingertesttypemockuserdictionary-process-data',
            $wfMessage->getMessageName()
        );

        $this->assertEquals($userData, $wfMessage->getPayload()->getData());

        $this->assertTrue($serviceBusMessage->header()->uuid()->equals($wfMessage->getUuid()));

        $this->assertEquals(
            $serviceBusMessage->header()->createdOn()->format("Y-m-d H:i:s"),
            $wfMessage->getCreatedOn()->format("Y-m-d H:i:s")
        );

        $this->assertEquals($serviceBusMessage->header()->version(), $wfMessage->getVersion());
    }
}
 