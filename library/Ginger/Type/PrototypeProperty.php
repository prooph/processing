<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 09.07.14 - 21:39
 */

namespace Ginger\Type;

/**
 * Class PrototypeProperty
 *
 * @package Ginger\Type
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
     * @param string  $propertyName
     * @param Prototype $typePrototype
     */
    public function __construct($propertyName, Prototype $typePrototype)
    {
        \Assert\that($propertyName)->notEmpty()->string();

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
 