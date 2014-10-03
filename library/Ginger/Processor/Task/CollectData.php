<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 01:47
 */

namespace Ginger\Processor\Task;

use Ginger\Type\Prototype;

/**
 * Class CollectData
 *
 * @package Ginger\Processor\Task
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
     * @param string $source
     * @param Prototype $prototype
     * @return \Ginger\Processor\Task\CollectData
     */
    public static function from($source, Prototype $prototype)
    {
        return new self($source, $prototype);
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

        return new self($source, $prototype);
    }

    /**
     * @param string $source
     * @param Prototype $prototype
     */
    private function __construct($source, Prototype $prototype)
    {
        \Assert\that($source)->notEmpty()->string();

        $this->source = $source;
        $this->prototype = $prototype;
    }

    /**
     * @return \Ginger\Type\Prototype
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
    public function getArrayCopy()
    {
        return [
            'source' => $this->source(),
            'prototype' => $this->prototype()->of(),
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
}
 