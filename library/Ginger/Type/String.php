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
use Ginger\Type\Description\NativeType;

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
        return new Description('String', NativeType::STRING, false);
    }

    /**
     * Performs assertions and sets the internal value property on success
     *
     * @param mixed $value
     * @return void
     */
    protected function setValue($value)
    {
        \Assert\that($value)->string();

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
 