<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 08.07.14 - 22:49
 */

namespace Ginger\Type;

use Ginger\Type\Description\Description;
use Ginger\Type\Description\DescriptionRegistry;
use Ginger\Type\Description\NativeType;
use Ginger\Type\Exception\InvalidTypeException;

/**
 * Class String
 *
 * @package Ginger\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class String extends SingleValue
{
    /**
     * The description is cached in the internal description property
     *
     * Implement the method to build the description only once and only if it is requested
     *
     * @return Description
     */
    public static function buildDescription()
    {
        if (DescriptionRegistry::hasDescription(__CLASS__)) return DescriptionRegistry::getDescription(__CLASS__);

        $desc = new Description('String', NativeType::STRING, false);

        DescriptionRegistry::registerDescriptionFor(__CLASS__, $desc);

        return $desc;
    }

    /**
     * Performs assertions and sets the internal value property on success
     *
     * @param mixed $value
     * @throws Exception\InvalidTypeException
     * @return void
     */
    protected function setValue($value)
    {
        if (!is_string($value)) {
            throw InvalidTypeException::fromMessageAndPrototype("Value must be a string", static::prototype());
        }

        if(!mb_check_encoding($value, 'UTF-8')) {
            throw InvalidTypeException::fromMessageAndPrototype("Value must be a UTF-8 encoded", static::prototype());
        }

        $this->value = $value;
    }

    /**
     * @param string $valueString
     * @return Type
     */
    public static function fromString($valueString)
    {
        return static::fromNativeValue($valueString);
    }
}
 