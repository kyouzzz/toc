<?php
namespace Lib\Cache;

use \Redis;
class RedisCache implements CacheInterface
{

    protected $host;
    protected $port;

    protected $redis;

    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->redis = new Redis();
        $this->redis->pconnect($this->host, $this->port);
    }
    /**
     * 获取 redis 连接
     * @return [type] [description]
     */
    public function getConnection()
    {
        return $this->redis;
    }
    /**
     * 获取数据
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function get($key)
    {
        return $this->redis->get($key);
    }
    /**
     * 默认缓存 30 天
     * @param [type]  $key   [description]
     * @param [type]  $value [description]
     * @param integer $sec   [description]
     */
    public function set($key, $value, $sec = 2592000)
    {
        $this->redis->set($key, $value, $sec);
    }
    /**
     * 删除缓存
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function delete($key)
    {
        return $this->redis->delete($key);
    }

}