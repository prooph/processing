<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 28.01.15 - 20:58
 */

namespace Ginger\Type;

/**
 * Class BooleanOrNull
 *
 * @package Ginger\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class BooleanOrNull extends Boolean implements Optional
{
    /**
     * @param mixed $nullOrValue
     * @return static
     */
    public static function fromNativeValue($nullOrValue)
    {
        if (is_null($nullOrValue)) {
            return static::fromNull();
        } else {
            return parent::fromNativeValue($nullOrValue);
        }
    }

    /**
     * @param mixed $nullOrValue
     * @return static
     */
    public static function fromJsonDecodedData($nullOrValue)
    {
        if (is_null($nullOrValue)) {
            return static::fromNull();
        } else {
            return parent::fromJsonDecodedData($nullOrValue);
        }
    }

    /**
     * @param string $valueString
     * @return static
     */
    public static function fromString($valueString)
    {
        if ($valueString === "") {
            return static::fromNull();
        } else {
            return static::fromNativeValue((bool)(int)$valueString);
        }
    }

    public function toString()
    {
        if (is_null($this->value)) {
            return "";
        } else {
            return $this->value? "1" : "0";
        }
    }

    /**
     * @return static
     */
    public static function fromNull()
    {
        return new static(null);
    }

    /**
     * @return bool
     */
    public function isNull()
    {
        return is_null($this->value);
    }

    /**
     * @param mixed $nullOrValue
     */
    protected function setValue($nullOrValue)
    {
        if (is_null($nullOrValue)) {
            $this->value = $nullOrValue;
        } else {
            parent::setValue($nullOrValue);
        }
    }
}
 