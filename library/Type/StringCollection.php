<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.07.14 - 21:54
 */

namespace Prooph\Processing\Type;

use Prooph\Processing\Type\Description\Description;
use Prooph\Processing\Type\Description\DescriptionRegistry;
use Prooph\Processing\Type\Description\NativeType;

/**
 * Class StringCollection
 *
 * @package Prooph\Processing\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StringCollection extends AbstractCollection
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
        return String::prototype();
    }

    /**
     * @return Description
     */
    public static function buildDescription()
    {
        if (DescriptionRegistry::hasDescription(__CLASS__)) return DescriptionRegistry::getDescription(__CLASS__);

        $desc = new Description('StringCollection', NativeType::COLLECTION, false);

        DescriptionRegistry::registerDescriptionFor(__CLASS__, $desc);

        return $desc;
    }
}
 