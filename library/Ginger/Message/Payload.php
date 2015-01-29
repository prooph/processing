<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.07.14 - 22:22
 */

namespace Ginger\Message;

use Assert\Assertion;
use Ginger\Type\Prototype;
use Ginger\Type\Type;

/**
 * Class Payload
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Payload implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $typeClass;

    /**
     * @var Type
     */
    protected $type;

    /**
     * @var array
     */
    protected $data;

    /**
     * @param Prototype $aPrototype
     * @return Payload
     */
    public static function fromPrototype(Prototype $aPrototype)
    {
        return new static($aPrototype->of(), null);
    }

    /**
     * @param Type $aType
     * @return Payload
     */
    public static function fromType(Type $aType)
    {
        return new static(get_class($aType), $aType);
    }

    /**
     * @param array $jsonDecodedData
     * @return Payload
     */
    public static function fromJsonDecodedData(array $jsonDecodedData)
    {
        Assertion::keyExists($jsonDecodedData, 'typeClass');
        Assertion::keyExists($jsonDecodedData, 'data');
        Assertion::notEmpty($jsonDecodedData['typeClass']);
        Assertion::string($jsonDecodedData['typeClass']);

        return new static($jsonDecodedData['typeClass'], $jsonDecodedData['data']);
    }

    /**
     * @param string $typeClass
     * @param null $dataOrType
     */
    protected function __construct($typeClass, $dataOrType = null)
    {
        $this->typeClass = $typeClass;

        if (! is_null($dataOrType)) {
            if ($dataOrType instanceof Type) {
                $this->type = $dataOrType;
            } else {
                $this->data = $dataOrType;
            }
        }
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return array('typeClass' => $this->typeClass, 'data' => $this->extractTypeData());
    }

    /**
     * @return mixed
     */
    public function extractTypeData()
    {
       if (is_null($this->data) && ! is_null($this->type)) {
           $serializedData = json_encode($this->type);
           $jsonDecodedData = json_decode($serializedData, true);
           $this->data = $jsonDecodedData;
       }

        return $this->data;
    }

    /**
     * @param mixed $newData
     */
    public function replaceData($newData)
    {
        $this->data = $newData;
        $this->type = null;
    }

    /**
     * @return string
     */
    public function getTypeClass()
    {
        return $this->typeClass;
    }

    /**
     * @param string $newTypeClass
     */
    public function changeTypeClass($newTypeClass)
    {
        Assertion::string($newTypeClass);
        Assertion::implementsInterface($newTypeClass, 'Ginger\Type\Type');
        $this->typeClass = $newTypeClass;
        $this->type = null;
    }

    /**
     * @return Type|null
     */
    public function toType()
    {
        if (is_null($this->type) && ! is_null($this->data)) {
            $typeClass = $this->typeClass;

            $this->type = $typeClass::fromJsonDecodedData($this->data);
        }

        return $this->type;
    }
}
 