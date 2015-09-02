<?php
namespace Lib\Cache;

class MemCache implements CacheInterface
{

    public function get($key)
    {
        return "memcahce key $key";
    }

    public function set($key, $value, $sec)
    {
        
    }

    public function delete($key)
    {
    }

}