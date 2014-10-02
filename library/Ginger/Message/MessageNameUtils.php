<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.07.14 - 13:06
 */

namespace Ginger\Message;

/**
 * Class MessageNameUtils
 *
 * @package Ginger\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class MessageNameUtils
{
    const MESSAGE_NAME_PREFIX = "ginger-message-";

    const COLLECT_DATA = "%s%s-collect-data";

    const DATA_COLLECTED = "%s%s-data-collected";

    const PROCESS_DATA = "%s%s-process-data";

    const DATA_PROCESSED = "%s%s-data-processed";

    const LOG_MESSAGE_NAME = "ginger-log-message";

    const MESSAGE_PARTS_PATTERN = "/^ginger-message-(?P<type>[^-]+)-(?<message>[^-]+-[\w]+)$/";

    static protected $commandSuffixes = array("collect-data", "process-data");

    static protected $eventSuffixes = array("data-collected", "data-processed");

    /**
     * @param string $typeClassOfData
     * @return string
     */
    public static function getCollectDataCommandName($typeClassOfData)
    {
        return sprintf(
            self::COLLECT_DATA,
            self::MESSAGE_NAME_PREFIX,
            self::normalize($typeClassOfData)
        );
    }

    /**
     * @param string $typeClassOfData
     * @return string
     */
    public static function getProcessDataCommandName($typeClassOfData)
    {
        return sprintf(
            self::PROCESS_DATA,
            self::MESSAGE_NAME_PREFIX,
            self::normalize($typeClassOfData)
        );
    }

    /**
     * @param string $typeClassOfData
     * @return string
     */
    public static function getDataCollectedEventName($typeClassOfData)
    {
        return sprintf(
            self::DATA_COLLECTED,
            self::MESSAGE_NAME_PREFIX,
            self::normalize($typeClassOfData)
        );
    }

    /**
     * @param string $typeClassOfData
     * @return string
     */
    public static function getDataProcessedEventName($typeClassOfData)
    {
        return sprintf(
            self::DATA_PROCESSED,
            self::MESSAGE_NAME_PREFIX,
            self::normalize($typeClassOfData)
        );
    }

    /**
     * @param string $messageName
     * @return string
     */
    public static function normalize($messageName)
    {
        \Assert\that($messageName)->notEmpty()->string();

        $search = array(
            static::MESSAGE_NAME_PREFIX,
            "-",
            "\\",
            "/",
            " "
        );

        return strtolower(str_replace($search, "", $messageName));
    }

    /**
     * @param string $aMessageName
     * @return string|null
     */
    public static function getTypePartOfMessageName($aMessageName)
    {
        $match = array();

        preg_match(static::MESSAGE_PARTS_PATTERN, $aMessageName, $match);

        if (isset($match['type'])) {
            return $match['type'];
        } else {
            return null;
        }
    }

    /**
     * Is given message name a ginger command or ginger event
     *
     * @param string $aMessageName
     * @return bool
     */
    public static function isGingerMessage($aMessageName)
    {
        $match = array();

        preg_match(static::MESSAGE_PARTS_PATTERN, $aMessageName, $match);

        if (isset($match['message'])) {
            $choices = array_merge(static::$commandSuffixes, static::$eventSuffixes);

            return in_array($match['message'], $choices);
        }

        return false;
    }

    /**
     * Is given message name a ginger command
     *
     * @param string $aMessageName
     * @return bool
     */
    public static function isGingerCommand($aMessageName)
    {
        $match = array();

        preg_match(static::MESSAGE_PARTS_PATTERN, $aMessageName, $match);

        if (isset($match['message'])) {
            return in_array($match['message'], static::$commandSuffixes);
        }

        return false;
    }

    /**
     * Is given message name a ginger event
     *
     * @param string $aMessageName
     * @return bool
     */
    public static function isGingerEvent($aMessageName)
    {
        $match = array();

        preg_match(static::MESSAGE_PARTS_PATTERN, $aMessageName, $match);

        if (isset($match['message'])) {
            return in_array($match['message'], static::$eventSuffixes);
        }

        return false;
    }

    /**
     * @param string $aMessageName
     * @return bool
     */
    public static function isGingerLogMessage($aMessageName)
    {
        return self::LOG_MESSAGE_NAME === $aMessageName;
    }
}
 