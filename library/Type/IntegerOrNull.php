<?php
/*
 * This file is part of the prooph processing framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 28.01.15 - 23:24
 */

namespace Prooph\Processing\Type;

/**
 * Class IntegerOrNull
 *
 * @package Prooph\Processing\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class IntegerOrNull extends Integer implements Optional
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
            return static::fromNativeValue((int)$valueString);
        }
    }

    public function toString()
    {
        if (is_null($this->value)) {
            return "";
        } else {
            return empty($this->value)? "0" : (string)$this->value;
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
 