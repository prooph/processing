<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 09.12.14 - 19:13
 */

namespace Ginger\Type;

use Ginger\Type\Description\Description;
use Ginger\Type\Description\NativeType;

/**
 * Class UnknownCollection
 *
 * @package Ginger\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class UnknownCollection extends AbstractCollection
{
    /**
     * Returns the prototype of the items type
     *
     * A collection has always one property with name item representing the type of all items in the collection.
     *
     * @return Prototype
     */
    public static function itemPrototype()
    {
        return Unknown::prototype();
    }

    /**
     * @return Description
     */
    public static function buildDescription()
    {
        return new Description('UnknownCollection', NativeType::COLLECTION, false);
    }
}
 