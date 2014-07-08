<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 08.07.14 - 21:12
 */

namespace Ginger\Type;

use Ginger\Type\Description\Description;

/**
 * Abstract Class SingleValue
 *
 * Ginger\Type that transports just one value
 *
 * @package Ginger\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
abstract class SingleValue implements Type
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var Description
     */
    protected $description;

    /**
     * Performs assertions and sets the internal value property on success
     *
     * @param mixed $value
     * @return void
     */
    abstract protected function setValue($value);

    /**
     * The description is cached in the internal description property
     *
     * Implement the method to build the description only once and only if it is requested
     *
     * @return Description
     */
    abstract protected function buildDescription();

    /**
     * @param mixed $value
     * @return Type
     */
    public static function fromNativeValue($value)
    {
        return new static($value);
    }

    /**
     * Non accessible construct
     *
     * Use static factory methods to construct a SingleValue
     *
     * @param mixed $value
     */
    protected function __construct($value)
    {
        $this->setValue($value);
    }

    /**
     * @return Description
     */
    public function description()
    {
        if (is_null($this->description)) {
            $this->description = $this->buildDescription();
        }

        return $this->description;
    }

    /**
     * A single value has no properties so method always returns an empty list
     *
     * @return Property[]
     */
    public function properties()
    {
        return array();
    }

    /**
     * @return mixed Type of the value is defined in Ginger\Type\Description of the type
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @return string representation of the value
     */
    public function toString()
    {
        return (string)$this->value;
    }
}
 