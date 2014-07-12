<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 09.07.14 - 20:26
 */

namespace Ginger\Type;

use Ginger\Type\Description\Description;
use Ginger\Type\Description\NativeType;

/**
 * Class DateTime
 *
 * @package Ginger\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class DateTime extends SingleValue
{
    /**
     * @return \DateTime|null
     */
    public function value()
    {
        return clone $this->value;
    }

    /**
     * Performs assertions and sets the internal value property on success
     *
     * @param mixed $value
     * @return void
     */
    protected function setValue($value)
    {
        \Assert\that($value)->isInstanceOf('\DateTime');

        $this->value = clone $value;
    }

    /**
     * The description is cached in the internal description property
     *
     * Implement the method to build the description only once and only if it is requested
     *
     * @return Description
     */
    public static function buildDescription()
    {
        return new Description('DateTime', NativeType::DATETIME, false);
    }

    /**
     * @param string $valueString
     * @return Type
     */
    public static function fromString($valueString)
    {
        return new static(new \DateTime($valueString));
    }

    /**
     * @param mixed $value
     * @return Type
     */
    public static function fromJsonDecodedData($value)
    {
        return static::fromString($value);
    }

    /**
     * @return string
     */
    public function toString()
    {
        if (is_null($this->value)) {
            return "";
        } else {
            return $this->value->format(\DateTime::ISO8601);
        }
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->toString();
    }

    /**
     * @param Type $other
     * @return bool
     */
    public function sameAs(Type $other)
    {
        if (! $other instanceof DateTime) {
            return false;
        }

        return $this->toString() === $other->toString();
    }
}
 