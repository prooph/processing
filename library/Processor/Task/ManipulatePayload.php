<?php
/**
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\Processing\Processor\Task;

use Prooph\Processing\Message\Payload;

/**
 * Class ManipulatePayload
 *
 * @package Prooph\Processing\Processor\Task
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class ManipulatePayload implements Task
{
    /**
     * @var callable
     */
    private $manipulationCallback;

    /**
     * @var string
     */
    private $manipulationScript;

    /**
     * Initialize task with a path to a manipulation script
     *
     * @param string $manipulationScript
     * @return ManipulatePayload
     */
    public static function with($manipulationScript)
    {
        return new self($manipulationScript);
    }

    /**
     * @param array $taskData
     * @throws \InvalidArgumentException
     * @return ManipulatePayload
     */
    public static function reconstituteFromArray(array $taskData)
    {
        if (! array_key_exists('manipulation_script', $taskData)) throw new \InvalidArgumentException("Key manipulation_script missing in task data");

        return new self($taskData['manipulation_script']);
    }

    /**
     * @param string $manipulationScript
     * @throws \InvalidArgumentException
     */
    private function __construct($manipulationScript)
    {
        if (! is_string($manipulationScript)) {
            throw new \InvalidArgumentException(sprintf("ManipulationScript must be a string path to an existing file but type of %s given", gettype($manipulationScript)));
        }

        if (! file_exists($manipulationScript)) {
            throw new \InvalidArgumentException(sprintf("ManipulationScript %s does not exist", $manipulationScript));
        }

        $this->manipulationCallback = include($manipulationScript);

        if (! is_callable($this->manipulationCallback)) {
            throw new \InvalidArgumentException(sprintf("ManipulationScript %s does not provide a callable", $manipulationScript));
        }

        $this->manipulationScript = $manipulationScript;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return ['manipulation_script' => $this->manipulationScript];
    }

    /**
     * @param Payload $payload
     */
    public function performManipulationOn(Payload $payload)
    {
        $callback = $this->manipulationCallback;
        $callback($payload);
    }

    /**
     * @param Task $task
     * @return bool
     */
    public function equals(Task $task)
    {
        if (! $task instanceof ManipulatePayload) return false;

        return $this->manipulationScript === $task->manipulationScript;
    }
}