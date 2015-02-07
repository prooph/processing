<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.07.14 - 19:39
 */

namespace Prooph\Processing\Type;

interface DictionaryType extends Type
{
    /**
     * @return array[propertyName => Prototype]
     */
    public static function getPropertyPrototypes();
}
 