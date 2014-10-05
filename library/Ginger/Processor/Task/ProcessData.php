<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 05.10.14 - 21:22
 */

namespace Ginger\Processor\Task;

use Codeliner\Comparison\EqualsBuilder;

/**
 * Class ProcessData
 *
 * @package Ginger\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessData implements Task
{
    /**
     * @var string
     */
    private $target;

    /**
     * @var array of Ginger\Type\Type classes
     */
    private $allowedTypes;

    /**
     * @var string of Ginger\Type\Type class
     */
    private $preferredType;

    /**
     * @param string $target
     * @param array $allowedTypes
     * @param null|string $preferredType
     * @return ProcessData
     */
    public static function address($target, array $allowedTypes, $preferredType = null)
    {
        return new self($target, $allowedTypes, $preferredType);
    }

    /**
     * @param array $taskData
     * @return static
     */
    public static function reconstituteFromArray(array $taskData)
    {
        \Assert\that($taskData)->keyExists('target')
            ->keyExists('allowedTypes')
            ->keyExists('preferredType');

        \Assert\that($taskData['allowedTypes'])->isArray();

        return new self($taskData['target'], $taskData['allowedTypes'], $taskData['preferredType']);
    }

    private function __construct($target, array $allowedTypes, $preferredType = null)
    {
        \Assert\that($target)->notEmpty()->string();

        \Assert\that($allowedTypes)->notEmpty()->all()->classExists()->implementsInterface('Ginger\Type\Type');

        \Assert\that($preferredType)->nullOr()->inArray($allowedTypes);

        $this->target = $target;
        $this->allowedTypes = $allowedTypes;
        $this->preferredType = $preferredType;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'target' => $this->target,
            'allowedTypes' => $this->allowedTypes,
            'preferredType' => $this->preferredType
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
            ->strict()
            ->equals();
    }
}
 