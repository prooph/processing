<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 09.07.14 - 21:34
 */

namespace Prooph\Processing\Type;

use Assert\Assertion;
use Prooph\Processing\Type\Description\Description;

/**
 * Class Prototype
 *
 * @package Prooph\Processing\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Prototype 
{
    /**
     * @var string
     */
    protected $relatedTypeClass;

    /**
     * @var Description
     */
    protected $descriptionOfType;

    /**
     * @var PrototypeProperty[propertyName => PrototypeProperty]
     */
    protected $typeProperties;

    /**
     * @param string $relatedTypeClass
     * @param Description $descriptionOfType
     * @param PrototypeProperty[] $typeProperties
     */
    public function __construct($relatedTypeClass, Description $descriptionOfType, array $typeProperties)
    {
        Assertion::implementsInterface($relatedTypeClass, 'Prooph\Processing\\Type\Type');
        foreach($typeProperties as $propertyOfType) Assertion::isInstanceOf($propertyOfType, 'Prooph\Processing\Type\PrototypeProperty');

        $this->relatedTypeClass = $relatedTypeClass;
        $this->descriptionOfType = $descriptionOfType;
        $this->typeProperties = $typeProperties;

        PrototypeRegistry::registerPrototype($this);
    }

    /**
     * @return string class name of the related type
     */
    public function of()
    {
        return $this->relatedTypeClass;
    }

    /**
     * @return Description
     */
    public function typeDescription()
    {
        return $this->descriptionOfType;
    }

    /**
     * @return PrototypeProperty[]
     */
    public function typeProperties()
    {
        return $this->typeProperties;
    }
}
 