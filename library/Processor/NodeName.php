<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 04.12.14 - 17:01
 */

namespace Prooph\Processing\Processor;

/**
 * Class NodeName
 * VO that represents the name of the system which runs the workflow processor.
 * In a network this would be the computer or server name but you can use
 * whatever name you want to call a node.
 * The node name becomes important when you want to use sub processes.
 * The node name is configured to tell the parent process which processor
 * is responsible for the sub process and the node name is used to retrieve
 * the correct command bus from the workflow engine to send the start sub process command.
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class NodeName
{
    /**
     * @var string
     */
    private $name;

    /**
     * @return NodeName
     */
    public static function defaultName()
    {
        return new self(Definition::DEFAULT_NODE_NAME);
    }

    /**
     * @param string $name
     * @return NodeName
     */
    public static function fromString($name)
    {
        return new self($name);
    }

    /**
     * @param string $name
     * @throws \InvalidArgumentException
     */
    private function __construct($name)
    {
        if (! is_string($name) || strlen($name) < 3) {
            throw new \InvalidArgumentException("The node name must be a string with at least 3 characters");
        }

        $this->name = $name;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @param NodeName $other
     * @return bool
     */
    public function equals(NodeName $other)
    {
        return $this->toString() === $other->toString();
    }
}
 