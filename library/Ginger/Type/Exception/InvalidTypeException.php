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

use Ginger\Type\Prototype;

class InvalidTypeException extends \InvalidArgumentException
{
    /**
     * @var Prototype
     */
    protected $relatedPrototypeOfType;

    /**
     * @param \InvalidArgumentException $exception
     * @param \Ginger\Type\Prototype $prototype
     * @return InvalidTypeException
     */
    public static function fromInvalidArgumentExceptionAndPrototype(\InvalidArgumentException $exception, Prototype $prototype)
    {
        $invalidTypeException = new static($exception->getMessage(), $exception->getCode(), $exception);

        $invalidTypeException->relatedPrototypeOfType = $prototype;

        return $invalidTypeException;
    }

    /**
     * @return Prototype
     */
    public function getPrototypeOfRelatedType()
    {
        return $this->relatedPrototypeOfType;
    }
}
 