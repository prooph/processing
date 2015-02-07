<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 07.07.14 - 22:10
 */
namespace Prooph\Processing\Type;
use Codeliner\Comparison\EqualsBuilder;
use Prooph\Processing\Type\Exception\InvalidTypeException;

/**
 * Class Property
 *
 * A property describes a child type of a Prooph\ProcessingType with a local unique name.
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Property
{
    /**
     * Local unique name of the property
     *
     * @var string
     */
    protected $name;

    /**
     * Prooph\ProcessingType of the property
     *
     * @var Type
     */
    protected $type;

    /**
     * @var mixed Type of the value is defined in Prooph\ProcessingType\Description of the property
     */
    protected $value;

    /**
     * @param string $name
     * @param Type $type
     * @throws \InvalidArgumentException
     */
    public function __construct($name, Type $type)
    {
        if (! is_string($name) || empty($name)) {
            throw new \InvalidArgumentException("Name of a property must be a non empty string");
        }

        $this->name  = $name;
        $this->type  = $type;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return Type
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->type()->value();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return $this->type()->hasProperty($name);
    }

    /**
     * @param string $name
     * @return Property|null
     */
    public function property($name)
    {
        return $this->type()->property($name);
    }

    /**
     * @param Property $other
     * @return bool
     */
    public function sameAs(Property $other)
    {
        return $this->type()->sameAs($other->type()) && $this->name() === $other->name();
    }
}
 