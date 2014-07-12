<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 07.07.14 - 21:07
 */
namespace Ginger\Type;

use Ginger\Type\Description\Description;

/**
 * Interface Type
 *
 * Common interface for a source or a target data object in a workflow.
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface Type extends \JsonSerializable
{
    /**
     * Provides access to a prototype of the Ginger\Type\Type (empty Object, with a Description and PrototypeProperties)
     *
     * @return Prototype
     */
    public static function prototype();

    /**
     * @return Description
     */
    public static function buildDescription();

    /**
     * @param string $valueString
     * @return Type
     */
    public static function fromString($valueString);

    /**
     * @param mixed $value
     * @return Type
     */
    public static function fromNativeValue($value);

    /**
     * @param $value
     * @return Type
     */
    public static function fromJsonDecodedData($value);

    /**
     * @return Description
     */
    public function description();


    /**
     * Get properties of the type indexed by property name
     *
     * A Ginger\Type\SingleValue has no properties, so you'll get an empty list
     * A Ginger\Type\Collection has a numeric index but all elements are of the same type
     * so properties() returns a list containing one property with name item that describes the elements
     * but has no value.
     *
     * @return Property[]
     */
    public function properties();

    /**
     * @param string $name of the property
     * @return bool
     */
    public function hasProperty($name);

    /**
     * @param string $name of the property
     * @return null|Property
     */
    public function property($name);

    /**
     * @return mixed Type of the value is defined in Ginger\Type\Description of the type
     */
    public function value();

    /**
     * @return string representation of the value
     */
    public function toString();

    /**
     * @param Type $other
     * @return boolean
     */
    public function sameAs(Type $other);
}
 