<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 09.07.14 - 21:34
 */

namespace Ginger\Type;
use Ginger\Type\Description\Description;

/**
 * Class Prototype
 *
 * @package Ginger\Type
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
    protected $propertiesOfType;

    /**
     * @param string $relatedTypeClass
     * @param Description $descriptionOfType
     * @param PrototypeProperty[] $propertiesOfType
     */
    public function __construct($relatedTypeClass, Description $descriptionOfType, array $propertiesOfType)
    {
        \Assert\that($relatedTypeClass)->implementsInterface('Ginger\Type\Type');
        \Assert\that($propertiesOfType)->all()->isInstanceOf('Ginger\Type\PrototypeProperty');

        $this->relatedTypeClass = $relatedTypeClass;
        $this->descriptionOfType = $descriptionOfType;
        $this->propertiesOfType = $propertiesOfType;
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
    public function propertiesOfType()
    {
        return $this->propertiesOfType;
    }
}
 