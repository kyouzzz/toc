<?php
namespace Lib\Cache;

use Lib\Util\Config;

class CacheFactory
{

    public static function factory()
    {
        // 获取缓存配置
        $config = Config::get("cache", "cache");
        $type = $config['type'];
        $cache_config = $config["$type"]['config'];
        // 根据配置实例化对应缓存类
        if ($type == "redis") {
            $cache = new RedisCache($cache_config);
        } else if ($cache_config['type'] == "memcache") {
            $cache = new MemCache($cache_config);
        }
        return $cache;
    }

}