<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.07.14 - 20:48
 */

namespace Ginger\Type;

use Ginger\Type\Description\Description;
use Ginger\Type\Exception\InvalidTypeException;

/**
 * Class Collection
 *
 * @package Ginger\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
abstract class AbstractCollection implements CollectionType, \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    protected $value = array();

    /**
     * @var Description
     */
    protected $description;

    /**
     * @var Property
     */
    protected $itemProperty;

    /**
     * Provides access to a prototype of the Ginger\Type\Type (empty Object, with a Description and PrototypeProperties)
     *
     * @return Prototype
     */
    public static function prototype()
    {
        $implementer = get_called_class();

        if (PrototypeRegistry::hasPrototype($implementer)) return PrototypeRegistry::getPrototype($implementer);

        return new Prototype(
            $implementer,
            static::buildDescription(),
            array(
                'item' => new PrototypeProperty('item', static::itemPrototype())
            )
        );
    }

    /**
     * @param string $valueString
     * @return Type
     */
    public static function fromString($valueString)
    {
        $itemsEncoded = json_decode($valueString, true);

        $itemClass = static::prototype()->typeProperties()['item']->typePrototype()->of();

        $items = array();

        foreach ($itemsEncoded as $encodedItem) {
            $items[] = $itemClass::fromJsonDecodedData($encodedItem);
        }

        return new static($items);
    }

    /**
     * @param mixed $value
     * @throws Exception\InvalidTypeException If value is not an array containing only items of related Ginger\Type
     * @return CollectionType
     */
    public static function fromNativeValue($value)
    {
        if (! is_array($value)) {
            throw InvalidTypeException::fromMessageAndPrototype("Value must be an array", static::prototype());
        }

        return new static($value);
    }

    /**
     * @param $value
     * @return AbstractCollection
     */
    public static function fromJsonDecodedData($value)
    {
        $itemClass = static::prototype()->typeProperties()['item']->typePrototype()->of();

        $items = array();

        foreach ($value as $encodedItem) {
            $items[] = $itemClass::fromJsonDecodedData($encodedItem);
        }

        return new static($items);
    }

    /**
     * @param array $value
     * @throws Exception\InvalidTypeException If value is not an array containing only items of related Ginger\Type
     */
    protected function __construct(array $value)
    {
        $itemClass = static::prototype()->typeProperties()['item']->typePrototype()->of();

        try {

            foreach ($value as $index => $itemOrNativeValue) {
                if (! $itemOrNativeValue instanceof $itemClass) {
                    $value[$index] = $itemClass::fromNativeValue($itemOrNativeValue);
                }
            }

        } catch (\InvalidArgumentException $ex) {
            throw InvalidTypeException::fromInvalidArgumentExceptionAndPrototype($ex, static::prototype());
        }

        $this->value = $value;

        $refItem = new \ReflectionClass($itemClass);

        $this->itemProperty = new Property("item", $refItem->newInstanceWithoutConstructor());
    }

    /**
     * @return Description
     */
    public function description()
    {
        if (is_null($this->description)) {
            $this->description = static::buildDescription();
        }

        return $this->description;
    }

    /**
     * Get properties of the type indexed by property name
     *
     * A Ginger\Type\SingleValue has no properties, so you'll get an empty list
     * A Ginger\Type\Collection has a numeric index but all elements are of the same type
     * so properties() returns a list containing one property with name item that describes the elements
     * but has no value.
     *
     * @return Property[propertyName => Property]
     */
    public function properties()
    {
        return array('item' => $this->itemProperty);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return $name === "item";
    }

    /**
     * @param string $name
     * @return Property|null
     */
    public function property($name)
    {
        return $name === "item" ? $this->itemProperty : null;
    }

    /**
     * @return array
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
        return json_encode($this->value());
    }

    public function jsonSerialize()
    {
        return $this->value();
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->value());
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->value());
    }

    /**
     * @param Type $other
     * @return boolean
     */
    public function sameAs(Type $other)
    {
        return $this->toString() === $other->toString();
    }
}
 