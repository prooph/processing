<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 05.10.14 - 21:22
 */

namespace Prooph\Processing\Processor\Task;

use Assert\Assertion;
use Codeliner\Comparison\EqualsBuilder;

/**
 * Class ProcessData
 *
 * @package Prooph\Processing\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessData implements Task
{
    /**
     * @var string
     */
    private $target;

    /**
     * @var array of Prooph\ProcessingType\Type classes
     */
    private $allowedTypes;

    /**
     * @var string of Prooph\ProcessingType\Type class
     */
    private $preferredType;

    /**
     * Metadata is passed along with the workflow message to the handler and can be used to provide additional information
     *
     * @var array
     */
    private $metadata;

    /**
     * @param string $target
     * @param array $allowedTypes
     * @param null|string $preferredType
     * @param array $metadata
     * @return ProcessData
     */
    public static function address($target, array $allowedTypes, $preferredType = null, array $metadata = [])
    {
        return new self($target, $allowedTypes, $preferredType, $metadata);
    }

    /**
     * @param array $taskData
     * @return static
     */
    public static function reconstituteFromArray(array $taskData)
    {
        Assertion::keyExists($taskData, 'target');
        Assertion::keyExists($taskData, 'allowed_types');
        Assertion::keyExists($taskData, 'preferred_type');
        Assertion::keyExists($taskData, 'metadata');
        Assertion::isArray($taskData['allowed_types']);

        return new self($taskData['target'], $taskData['allowed_types'], $taskData['preferred_type'], $taskData['metadata']);
    }

    private function __construct($target, array $allowedTypes, $preferredType = null, array $metadata)
    {
        Assertion::notEmpty($target);
        Assertion::string($target);
        Assertion::notEmpty($allowedTypes);
        $this->assertMetadata($metadata);

        foreach ($allowedTypes as $allowedType) {
            Assertion::classExists($allowedType);
            Assertion::implementsInterface($allowedType, 'Prooph\Processing\Type\Type');
        }

        if (! is_null($preferredType)) {
            Assertion::inArray($preferredType, $allowedTypes);
        }

        $this->target = $target;
        $this->allowedTypes  = $allowedTypes;
        $this->preferredType = $preferredType;
        $this->metadata      = $metadata;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'target' => $this->target,
            'allowed_types'  => $this->allowedTypes,
            'preferred_type' => $this->preferredType,
            'metadata'       => $this->metadata,
        ];
    }

    /**
     * @return array
     */
    public function allowedTypes()
    {
        return $this->allowedTypes;
    }

    /**
     * @return string
     */
    public function preferredType()
    {
        if (is_null($this->preferredType)) {
            return $this->allowedTypes[0];
        }

        return $this->preferredType;
    }

    /**
     * @return string
     */
    public function target()
    {
        return $this->target;
    }

    /**
     * @return array
     */
    public function metadata()
    {
        return $this->metadata;
    }

    /**
     * @param Task $task
     * @return bool
     */
    public function equals(Task $task)
    {
        if (! $task instanceof ProcessData) {
            return false;
        }

        return EqualsBuilder::create()
            ->append($this->target, $task->target)
            ->append($this->allowedTypes, $task->allowedTypes)
            ->append($this->preferredType, $task->preferredType)
            ->append($this->metadata(), $task->metadata())
            ->strict()
            ->equals();
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
 