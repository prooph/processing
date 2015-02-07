<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 07.07.14 - 22:02
 */
namespace Prooph\Processing\Type\Description;

/**
 * Definition Class for native types
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class NativeType
{
    const UNKNOWN      = "unknown";

    const STRING       = "string";

    const BOOLEAN      = "boolean";

    const INTEGER      = "integer";

    const FLOAT        = "float";

    const DATETIME     = "datetime";

    const COLLECTION   = "collection";

    const DICTIONARY   = "dictionary";

    const OBJECT       = "object";

    const FILE         = "file";

    const STREAM       = "stream";

    public static function all()
    {
        return array(
            self::UNKNOWN,
            self::STRING,
            self::BOOLEAN,
            self::INTEGER,
            self::FLOAT,
            self::DATETIME,
            self::COLLECTION,
            self::DICTIONARY,
            self::OBJECT,
            self::FILE,
            self::STREAM,
        );
    }
}
 