<?php
namespace Lib\Cache;

interface CacheInterface
{

    public function set($key, $value, $sec);

    public function get($key);

    public function delete($key);

}