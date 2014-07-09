<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 09.07.14 - 21:17
 */

namespace Ginger\Type\Exception;

use Ginger\Type\Type;

class InvalidTypeException extends \InvalidArgumentException
{
    /**
     * @var
     */
    protected $relatedPrototypeOfType;

    /**
     * @param \InvalidArgumentException $exception
     * @param Type $type
     */
    public static function fromInvalidArgumentExceptionAndType(\InvalidArgumentException $exception, Type $type)
    {

    }
}
 