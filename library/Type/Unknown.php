<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 09.12.14 - 18:20
 */

namespace Prooph\Processing\Type;

use Prooph\Processing\Type\Description\Description;
use Prooph\Processing\Type\Description\DescriptionRegistry;
use Prooph\Processing\Type\Description\NativeType;
use Prooph\Processing\Type\Exception\InvalidTypeException;

/**
 * Class Unknown
 *
 * An Unknown can have any scalar or array or a mix of both as value but no objects or resources
 *
 * @package Prooph\Processing\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Unknown extends SingleValue
{
    /**
     * Performs assertions and sets the internal value property on success
     *
     * @param mixed $value
     * @return void
     */
    protected function setValue($value)
    {
        $this->assertIsScalarOrArray($value);

        $this->value = $value;
    }

    /**
     * @return Description
     */
    public static function buildDescription()
    {
        if (DescriptionRegistry::hasDescription(__CLASS__)) return DescriptionRegistry::getDescription(__CLASS__);

        $desc = new Description('Unknown', NativeType::UNKNOWN, false);

        DescriptionRegistry::registerDescriptionFor(__CLASS__, $desc);

        return $desc;
    }

    /**
     * @param string $valueString
     * @return Type
     */
    public static function fromString($valueString)
    {
        return Unknown::fromNativeValue(json_decode($valueString, true));
    }

    /**
     * @return string representation of the value
     */
    public function toString()
    {
        return json_encode($this->value);
    }

    /**
     * This method checks recursively if the value only consists of arrays and scalar types
     *
     * @param $value
     * @throws Exception\InvalidTypeException
     */
    private function assertIsScalarOrArray($value)
    {
        if (! is_scalar($value) && !is_array($value)) {
            throw InvalidTypeException::fromMessageAndPrototype("An unknown type must at least be a scalar or array value", static::prototype());
        }

        if (is_array($value)) {
            foreach ($value as $item) $this->assertIsScalarOrArray($item);
        }
    }
}
 