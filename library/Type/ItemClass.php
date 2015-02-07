<?php
/*
 * This file is part of the prooph processing framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 29.01.15 - 18:18
 */

namespace Prooph\Processing\Type;

use Assert\Assertion;
use Prooph\Processing\Type\Description\Description;
use Prooph\Processing\Type\Description\DescriptionRegistry;
use Prooph\Processing\Type\Description\NativeType;

/**
 * Class ItemClass
 *
 * @package Prooph\Processing
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class ItemClass extends String
{
    /**
     * @return Description
     */
    public static function buildDescription()
    {
        if (DescriptionRegistry::hasDescription(__CLASS__)) return DescriptionRegistry::getDescription(__CLASS__);

        $desc = new Description('Item Class', NativeType::STRING, false);

        DescriptionRegistry::registerDescriptionFor(__CLASS__, $desc);

        return $desc;
    }

    /**
     * @param mixed $value
     */
    protected function setValue($value)
    {
        //We use the string assertion first
        parent::setValue($value);

        //and then check, if we really got an item class
        Assertion::implementsInterface($value, 'Prooph\Processing\Type\Type');
    }

    /**
     * @return Prototype
     */
    public function itemPrototype()
    {
        $itemClass = $this->value();

        return $itemClass::prototype();
    }

    /**
     * @return Description
     */
    public function itemDescription()
    {
        $itemClass = $this->value();

        return $itemClass::buildDescription();
    }
}
 