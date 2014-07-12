<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.07.14 - 19:33
 */

namespace Ginger\Type;

use Codeliner\Comparison\EqualsBuilder;
use Ginger\Type\Description\Description;
use Ginger\Type\Exception\InvalidTypeException;

abstract class AbstractDictionary implements DictionaryType
{
    /**
     * @var array
     */
    protected $value = array();

    /**
     * @var Property[propertyName => Property]
     */
    protected $properties;

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
        $propertyPrototypes = static::getPropertyPrototypes();

        $propertyMap = array();

        foreach ($propertyPrototypes as $propertyName => $propertyPrototype) {
            $propertyMap[$propertyName] = new PrototypeProperty($propertyName, $propertyPrototype);
        }

        return new Prototype(
            get_called_class(),
            static::buildDescription(),
            $propertyMap
        );
    }

    /**
     * @param string $valueString
     * @return AbstractDictionary
     */
    public static function fromString($valueString)
    {
        $properties = json_decode($valueString, true);

        $prototypes = static::getPropertyPrototypes();

        foreach ($properties as $propertyName => $encodedProperty) {
            $propertyPrototype = $prototypes[$propertyName];

            $propertyClass = $propertyPrototype->of();

            $properties[$propertyName] = $propertyClass::fromJsonDecodedData($encodedProperty);
        }

        return new static($properties);
    }

    /**
     * @param mixed $value
     * @throws Exception\InvalidTypeException If value is not an array
     * @return AbstractDictionary
     */
    public static function fromNativeValue($value)
    {
        $propertyNames = array_keys(static::getPropertyPrototypes());

        try{
            \Assert\that($value)->isArray();
            \Assert\that(array_keys($value))->all()->inArray($propertyNames);
            \Assert\that($propertyNames)->all()->inArray(array_keys($value));

            return new static($value);
        } catch (\InvalidArgumentException $ex) {
            throw InvalidTypeException::fromInvalidArgumentExceptionAndPrototype($ex, static::prototype());
        }
    }

    /**
     * @param $value
     * @return AbstractDictionary
     */
    public static function fromJsonDecodedData($value)
    {
        $prototypes = static::getPropertyPrototypes();

        foreach ($value as $propertyName => $encodedProperty) {
            $propertyPrototype = $prototypes[$propertyName];

            $propertyClass = $propertyPrototype->of();

            $value[$propertyName] = $propertyClass::fromJsonDecodedData($encodedProperty);
        }

        return new static($value);
    }

    /**
     * @param array $value
     */
    protected function __construct(array $value)
    {
        $prototypes = static::getPropertyPrototypes();

        $properties = array();

        try {

            foreach ($value as $propertyName => $propertyTypeOrNativeValue) {
                $propertyPrototype = $prototypes[$propertyName];

                $propertyTypeClass = $propertyPrototype->of();

                if (! $propertyTypeOrNativeValue instanceof $propertyTypeClass) {
                    $propertyTypeOrNativeValue = $propertyTypeClass::fromNativeValue($propertyTypeOrNativeValue);
                }

                \Assert\that($propertyTypeOrNativeValue)->isInstanceOf($propertyTypeClass);

                $properties[$propertyName] = new Property($propertyName, $propertyTypeOrNativeValue);

                $value[$propertyName] = $propertyTypeOrNativeValue;
            }

        } catch (\InvalidArgumentException $ex) {
            throw InvalidTypeException::fromInvalidArgumentExceptionAndPrototype($ex, static::prototype());
        }

        $this->value = $value;
        $this->properties = $properties;
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
        return $this->properties;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * @param string $name
     * @return Property|null
     */
    public function property($name)
    {
        return $this->hasProperty($name) ? $this->properties[$name] : null;
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
        return json_encode($this->value);
    }

    /**
     * @param Type $other
     * @return boolean
     */
    public function sameAs(Type $other)
    {
        $calledClass = get_called_class();

        if (! $other instanceof $calledClass) {
            return false;
        }

        $myProperties = $this->properties();
        $otherProperties = $other->properties();

        if ($this->description()->hasIdentifier()) {
            return $myProperties[$this->description()->identifierName()]->type()->sameAs(
                $otherProperties[$this->description()->identifierName()]->type()
            );
        }

        $equalsBuilder = EqualsBuilder::create();

        foreach ($this->properties() as $propertyName => $property) {
            $equalsBuilder->append($property->type()->sameAs($otherProperties[$propertyName]->type()));
        }

        return $equalsBuilder->equals();
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return $this->value;
    }
}
 