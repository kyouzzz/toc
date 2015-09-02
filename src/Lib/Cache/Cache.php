<?php
namespace Lib\Cache;

class Cache
{

    private static $instance;

    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = CacheFactory::factory();
        }
        return static::$instance;
    }

}