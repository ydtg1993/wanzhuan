<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/23 0023
 * Time: 上午 10:59
 */

namespace App\Http\Common;


use Illuminate\Support\Facades\Redis;

class RedisDriver
{
    private static $RM;
    private static $config;
    public $redis;//原生redis
    public $predis;//框架redis


    private function __construct()
    {
        $host           = config('redis.host');
        $port           = config('redis.port');
        $instanceid     = config('redis.instanceid');
        $pwd            = config('redis.pwd');
        $this->redis    = new \Redis();
        $this->redis->connect($host, $port);
        $this->redis->auth($instanceid . ":" . $pwd);

        $this->predis = Redis::connection();
    }

    public static function getInstance()
    {
        if(self::$RM == null) {
            self::$config = config('redis_key');
            self::$RM = new self();
        }
        return self::$RM;
    }

    public function getCacheKey($name, ...$params)
    {
        $pos = strpos($name, '.');

        if (!is_int($pos)) {
            throw new \Exception('未设定键值');
        }

        $name_type = substr($name, 0, $pos);
        $name_key = substr($name, $pos + 1);
        if (!isset(self::$config[$name_type][$name_key])) {
            throw new \Exception('未设定键值');
        }

        switch ($name_type){
            case 'key':
                $this->redis->select(1);
                break;
            case 'cache':
                $this->redis->select(2);
                break;
            case 'list':
                $this->redis->select(3);
                break;
            case 'hash':
                $this->redis->select(4);
                break;
            case 'geo':
                $this->predis = Redis::connection('new');//地图缓存用第二台
                $this->predis->select(10);
                break;
        }

        $cache = (string)self::$config[$name_type][$name_key];

        foreach ($params as $k => $param) {
            $cache .= '_'.$param;
        }

        return $cache;
    }
}