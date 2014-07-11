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
abstract class AbstractCollection implements CollectionType
{
    const DELIMITER = '/||/';

    /**
     * @var array
     */
    protected $value = array();

    /**
     * @var Description
     */
    protected $description;

    /**
     * Provides access to a prototype of the Ginger\Type\Type (empty Object, with a Description and PrototypeProperties)
     *
     * @return Prototype
     */
    public static function prototype()
    {
        return new Prototype(
            get_called_class(),
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
        $itemsStrings = explode(self::DELIMITER, $valueString);

        $itemClass = static::prototype()->propertiesOfType()['item']->typePrototype()->of();

        $items = array();

        foreach ($itemsStrings as $itemString) {
            $items = $itemClass::fromString($itemString);
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
        try{
            \Assert\that($value)->isArray();
        } catch (\InvalidArgumentException $ex) {
            throw InvalidTypeException::fromInvalidArgumentExceptionAndPrototype($ex, static::prototype());
        }

        return new static($value);
    }

    /**
     * @param array $value
     * @throws Exception\InvalidTypeException If value is not an array containing only items of related Ginger\Type
     */
    protected function __construct(array $value)
    {
        $itemClass = static::prototype()->propertiesOfType()['item']->typePrototype()->of();

        try {
            \Assert\that($value)->all()->isInstanceOf($itemClass);
        } catch (\InvalidArgumentException $ex) {
            throw InvalidTypeException::fromInvalidArgumentExceptionAndPrototype($ex, static::prototype());
        }

        $this->value = $value;
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
        $itemClass = static::prototype()->propertiesOfType()['item']->typePrototype()->of();

        $refItem = new \ReflectionClass($itemClass);

        return array('item' => new Property('item', $refItem->newInstanceWithoutConstructor()));
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
        $itemsStrings = array();

        foreach ($this->value as $item) {
            $itemsStrings[] = $item->toString();
        }

        return implode(static::DELIMITER, $itemsStrings);
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
 