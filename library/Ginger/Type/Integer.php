<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 08.07.14 - 21:26
 */

namespace Ginger\Type;

use Ginger\Type\Description\Description;
use Ginger\Type\Description\NativeType;

/**
 * Class Integer
 *
 * SingleValue type integer
 *
 * @package Ginger\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Integer extends SingleValue
{
    /**
     * The description is cached in the internal description property
     *
     * @return Description
     */
    public static function buildDescription()
    {
        return new Description('Integer', NativeType::INTEGER, false);
    }

    /**
     * @param string $valueString
     * @return Type
     */
    public static function fromString($valueString)
    {
        return new static(intval($valueString));
    }

    /**
     * Performs assertions and sets the internal value property on success
     *
     * @param mixed $value
     * @return void
     */
    protected function setValue($value)
    {
        \Assert\that($value)->integer();
        $this->value = $value;
    }
}
 