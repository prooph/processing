<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 10.07.14 - 20:57
 */

namespace Ginger\Type;

/**
 * Interface CollectionType
 *
 * @package Ginger\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface CollectionType extends Type
{
    /**
     * Returns the prototype of the items type
     *
     * A collection has always one property with name item representing the type of all items in the collection.
     *
     * @return Prototype
     */
    public static function itemPrototype();

    /**
     * Returns the item count
     * The implementer should implement \Countable
     *
     * @return int
     */
    public function count();

    /**
     * Forces the implementer to be a \IteratorAggregate
     * The implementer should implement \IteratorAggregate
     * to be directly usable in a foreach
     *
     * @return \Traversable
     */
    public function getIterator();
}
 