<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.07.14 - 20:18
 */

namespace GingerTest\Type\Mock;

use Ginger\Type\AbstractDictionary;
use Ginger\Type\Description\Description;
use Ginger\Type\Description\NativeType;
use Ginger\Type\Integer;
use Ginger\Type\String;

class AddressDictionary extends AbstractDictionary
{
    /**
     * @return array[propertyName => Prototype]
     */
    public static function getPropertyPrototypes()
    {
        return array(
            'street' => String::prototype(),
            'streetNumber' => Integer::prototype(),
            'zip' => String::prototype(),
            'city' => String::prototype()
        );
    }

    /**
     * @return Description
     */
    public static function buildDescription()
    {
        return new Description("Address", NativeType::DICTIONARY, false);
    }
}
 