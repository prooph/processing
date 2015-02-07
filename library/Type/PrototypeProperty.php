<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 09.07.14 - 21:39
 */

namespace Prooph\Processing\Type;

/**
 * Class PrototypeProperty
 *
 * @package Prooph\Processing\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class PrototypeProperty 
{
    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var Prototype
     */
    protected $typePrototype;

    /**
     * @param string $propertyName
     * @param Prototype $typePrototype
     * @throws \InvalidArgumentException
     */
    public function __construct($propertyName, Prototype $typePrototype)
    {
        if (! is_string($propertyName) || empty($propertyName)) {
            throw new \InvalidArgumentException("Name of a property must be a non empty string");
        }

        $this->propertyName = $propertyName;
        $this->typePrototype = $typePrototype;
    }

    /**
     * @return string
     */
    public function propertyName()
    {
        return $this->propertyName;
    }

    /**
     * @return Prototype
     */
    public function typePrototype()
    {
       return $this->typePrototype;
    }
}
 