<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 22:35
 */

namespace Prooph\ProcessingExample\Type;

use Prooph\Processing\Type\AbstractDictionary;
use Prooph\Processing\Type\Description\Description;
use Prooph\Processing\Type\Description\NativeType;
use Prooph\Processing\Type\Integer;
use Prooph\Processing\Type\String;

class SourceUser extends AbstractDictionary
{
    /**
     * @return array[propertyName => Prototype]
     */
    public static function getPropertyPrototypes()
    {
        return array(
            'id' => Integer::prototype(),
            'name' => String::prototype(),
            'address' => SourceAddress::prototype()
        );
    }

    /**
     * @return Description
     */
    public static function buildDescription()
    {
        return new Description("User", NativeType::DICTIONARY, true, "id");
    }
}
 