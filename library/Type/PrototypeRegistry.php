<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.12.14 - 19:52
 */

namespace Prooph\Processing\Type;

/**
 * Class PrototypeRegistry
 *
 * @package Prooph\Processing\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class PrototypeRegistry 
{
    /**
     * @var array
     */
    private static $registry = [];

    public static function registerPrototype(Prototype $prototype)
    {
        self::$registry[$prototype->of()] = $prototype;
    }

    /**
     * @param $type
     * @return null|Prototype
     */
    public static function getPrototype($type)
    {
        return isset(self::$registry[$type])? self::$registry[$type] : null;
    }

    /**
     * @param $type
     * @return bool
     */
    public static function hasPrototype($type)
    {
        return isset(self::$registry[$type]);
    }

    public static function clear()
    {
        self::$registry = [];
    }
}
 