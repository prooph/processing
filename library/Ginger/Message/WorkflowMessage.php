<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.07.14 - 22:07
 */

namespace Ginger\Message;

use Prooph\ServiceBus\Message\MessageNameProvider;

/**
 * Class WorkflowMessage
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class WorkflowMessage implements MessageNameProvider
{
    const MESSAGE_NAME_PREFIX = "GINGER-MESSAGE-";

    protected $messageName = "DEFAULT";

    /**
     * @var Uuid
     */
    protected $uuid;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var \DateTime
     */
    protected $createdOn;

    /**
     * @var \DateTime
     */
    protected $updatedOn;

    /**
     * @var array
     */
    protected $payload = array();

    /**
     * @return string Name of the message
     */
    public function getMessageName()
    {
        return static::MESSAGE_NAME_PREFIX . $this->messageName;
    }

    /**
     * @param string $aMessageName
     */
    public function setMessageName($aMessageName)
    {
        \Assert\that($aMessageName)->notEmpty()->string();

        $part = str_replace(static::MESSAGE_NAME_PREFIX, "", $aMessageName);

        $this->messageName = $part;
    }


}
 