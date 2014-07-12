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

use Codeliner\ArrayReader\ArrayReader;
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
     * @var array
     */
    protected $data;

    /**
     * @var ArrayReader
     */
    protected $payloadReader;

    /**
     * @param Type $aType
     * @return Payload
     */
    public static function fromType(Type $aType)
    {
        //We need to convert the type to it's native value representation with the help of json encoding
        $jsonString = json_encode($aType);

        $data = json_decode($jsonString, true);

        return new static(get_class($aType), static::normalizeData($data));
    }

    /**
     * @param array $jsonDecodedData
     * @return Payload
     */
    public static function jsonDecode(array $jsonDecodedData)
    {
        \Assert\that($jsonDecodedData)->keyExists("typeClass");
        \Assert\that($jsonDecodedData)->keyExists("data");
        \Assert\that($jsonDecodedData['typeClass'])->notEmpty()->string();

        return new static($jsonDecodedData['typeClass'], static::normalizeData($jsonDecodedData['data']));
    }

    /**
     * @param string $typeClass
     * @param mixed $data
     */
    protected function __construct($typeClass, array $data)
    {
        $this->typeClass = $typeClass;
        $this->data = $data;
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
        return array('typeClass' => $this->typeClass, 'data' => $this->data);
    }

    /**
     * @return mixed
     */
    public function getData()
    {
       return static::convertToOriginalData($this->data);
    }

    /**
     * @param mixed $newData
     */
    public function changeData($newData)
    {
        $newData = static::normalizeData($newData);
        $this->data = \Zend\Stdlib\ArrayUtils::merge($this->data, $newData);
        $this->resetPayloadReader();
    }

    /**
     * @param mixed $newData
     */
    public function replaceData($newData)
    {
        $this->data = static::normalizeData($newData);
        $this->resetPayloadReader();
    }

    /**
     * @return ArrayReader
     */
    public function toPayloadReader()
    {
        if (is_null($this->payloadReader)) {
            $this->payloadReader = new ArrayReader($this->data);
        }
        return $this->payloadReader;
    }

    /**
     * @return null
     */
    protected function resetPayloadReader()
    {
        return $this->payloadReader = null;
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
        $this->typeClass = $newTypeClass;
    }

    /**
     * @return Type
     */
    public function toType()
    {
        $typeClass = $this->typeClass;

        return $typeClass::jsonDecode($this->convertToOriginalData($this->data));
    }

    /**
     * Internally data always has to be of type array
     *
     * @param mixed $data
     * @return array
     */
    protected static function normalizeData($data)
    {
        if (! is_array($data)) {
            $data = array('__value__' => $data);
        }

        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected static function convertToOriginalData(array $data)
    {
        if (count($data) === 1 && isset($data['__value__'])) {
            $data = $data['__value__'];
        }

        return $data;
    }
}
 