<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.01.15 - 23:52
 */

namespace Prooph\Processing\Type\Description;

/**
 * Class DescriptionRegistry
 *
 * @package Prooph\Processing\Type\Description
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class DescriptionRegistry 
{
    /**
     * @var Description[]
     */
    private static $typeDescriptions;

    /**
     * @param $typeClass
     * @param Description $description
     * @throws \InvalidArgumentException
     */
    public static function registerDescriptionFor($typeClass, Description $description)
    {
        if (self::hasDescription($typeClass)) throw new \InvalidArgumentException(sprintf('A description for type %s is already registered', $typeClass));
        self::$typeDescriptions[$typeClass] = $description;
    }

    /**
     * @param string $typeClass
     * @return bool
     */
    public static function hasDescription($typeClass)
    {
        return isset(self::$typeDescriptions[$typeClass]);
    }

    /**
     * @param string $typeClass
     * @return Description|null
     */
    public static function getDescription($typeClass)
    {
        return self::hasDescription($typeClass)? self::$typeDescriptions[$typeClass] : null;
    }
}
 