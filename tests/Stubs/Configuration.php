<?php

/**
 * In-memory stand-in for PrestaShop's Configuration class (only the methods the libs use).
 */
class Configuration
{
    /** @var array<string, mixed> */
    public static $values = array();

    public static function get($key)
    {
        return isset(self::$values[$key]) ? self::$values[$key] : false;
    }

    public static function getGlobalValue($key)
    {
        return self::get($key);
    }

    public static function updateValue($key, $value)
    {
        self::$values[$key] = $value;
        return true;
    }

    public static function updateGlobalValue($key, $value)
    {
        return self::updateValue($key, $value);
    }

    public static function reset()
    {
        self::$values = array();
    }
}
