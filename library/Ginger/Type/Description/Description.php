<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 07.07.14 - 21:17
 */
namespace Ginger\Type\Description;

/**
 * Class Description
 *
 * Describes the structure of a Ginger\Type
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Description
{
    /**
     * Display name of the Ginger\Type
     *
     * @var string
     */
    protected $label;

    /**
     * Says whether related Ginger\Type has an identifier or not
     *
     * @var bool
     */
    protected $hasIdentifier = false;

    /**
     * Name of the identifier property if Ginger\Type has an identifier
     *
     * @var string
     */
    protected $identifierName;

    /**
     * One of the types defined in Ginger\Type\Description\NativeType
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
        \Assert\that($label)->notEmpty()->string();
        \Assert\that($nativeType)->inArray(NativeType::all());
        \Assert\that($hasIdentifier)->boolean();
        \Assert\that($identifierName)->nullOr()->notEmpty()->string();

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
 