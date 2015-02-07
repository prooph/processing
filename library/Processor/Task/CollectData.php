<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 01:47
 */

namespace Prooph\Processing\Processor\Task;

use Assert\Assertion;
use Prooph\Processing\Type\Prototype;

/**
 * Class CollectData
 *
 * @package Prooph\Processing\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class CollectData implements Task
{
    /**
     * @var string
     */
    private $source;

    /**
     * @var Prototype
     */
    private $prototype;

    /**
     * Metadata is passed along with the workflow message to the handler and can be used to define filters etc.
     *
     * @var array
     */
    private $metadata;

    /**
     * @param string $source
     * @param Prototype $prototype
     * @param array $metadata
     * @return \Prooph\Processing\Processor\Task\CollectData
     */
    public static function from($source, Prototype $prototype, array $metadata = [])
    {
        return new self($source, $prototype, $metadata);
    }

    /**
     * @param array $taskData
     * @return static
     */
    public static function reconstituteFromArray(array $taskData)
    {
        $source = $taskData['source'];

        $typeClass = $taskData['prototype'];

        $prototype = $typeClass::prototype();

        return new self($source, $prototype, $taskData['metadata']);
    }

    /**
     * @param string $source
     * @param Prototype $prototype
     * @param array $metadata
     */
    private function __construct($source, Prototype $prototype, array $metadata)
    {
        Assertion::notEmpty($source);
        Assertion::string($source);
        $this->assertMetadata($metadata);

        $this->source = $source;
        $this->prototype = $prototype;
        $this->metadata  = $metadata;
    }

    /**
     * @return \Prooph\Processing\Type\Prototype
     */
    public function prototype()
    {
        return $this->prototype;
    }

    /**
     * @return string
     */
    public function source()
    {
        return $this->source;
    }

    /**
     * @return array
     */
    public function metadata()
    {
        return $this->metadata;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'source' => $this->source(),
            'prototype' => $this->prototype()->of(),
            'metadata'  => $this->metadata(),
        ];
    }

    /**
     * @param Task $task
     * @return bool
     */
    public function equals(Task $task)
    {
        return $this->getArrayCopy() === $task->getArrayCopy();
    }

    /**
     * @param array $metadata
     * @throws \InvalidArgumentException
     */
    private function assertMetadata(array $metadata)
    {
        foreach ($metadata as $entry) {
            if (is_array($entry)) $this->assertMetadata($entry);
            elseif (! is_scalar($entry)) throw new \InvalidArgumentException('Metadata must only contain arrays or scalar values');
        }
    }
}
 