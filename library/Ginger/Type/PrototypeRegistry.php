<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.12.14 - 19:52
 */

namespace Ginger\Type;

/**
 * Class PrototypeRegistry
 *
 * @package Ginger\Type
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
 