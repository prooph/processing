<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 07.07.14 - 21:17
 */
namespace Prooph\Processing\Type\Description;

use Assert\Assertion;

/**
 * Class Description
 *
 * Describes the structure of a Prooph\ProcessingType
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Description
{
    /**
     * Display name of the Prooph\ProcessingType
     *
     * @var string
     */
    protected $label;

    /**
     * Says whether related Prooph\ProcessingType has an identifier or not
     *
     * @var bool
     */
    protected $hasIdentifier = false;

    /**
     * Name of the identifier property if Prooph\ProcessingType has an identifier
     *
     * @var string
     */
    protected $identifierName;

    /**
     * One of the types defined in Prooph\ProcessingType\Description\NativeType
     *
     * @var string
     */
    protected $nativeType;

    /**
     * @param string      $label
     * @param string      $nativeType
     * @param bool        $hasIdentifier
     * @param null|string $identifierName
     */
    public function __construct($label, $nativeType, $hasIdentifier, $identifierName = null)
    {
        Assertion::notEmpty($label);
        Assertion::string($label);
        Assertion::inArray($nativeType, NativeType::all());
        Assertion::boolean($hasIdentifier);
        if (! is_null($identifierName)) {
            Assertion::notEmpty($identifierName);
            Assertion::string($identifierName);
        }

        $this->label = $label;
        $this->nativeType = $nativeType;
        $this->hasIdentifier = $hasIdentifier;
        $this->identifierName = $identifierName;
    }

    /**
     * Display name of the type
     *
     * @return string
     */
    public function label()
    {
        return $this->label;
    }

    /**
     * @return bool
     */
    public function hasIdentifier()
    {
        return $this->hasIdentifier;
    }

    /**
     * @return string
     */
    public function identifierName()
    {
        return $this->identifierName;
    }

    /**
     * @return string
     */
    public function nativeType()
    {
        return $this->nativeType;
    }
}
 