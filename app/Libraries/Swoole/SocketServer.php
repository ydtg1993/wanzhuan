<?php

namespace App\Libraries\Swoole;

use App\Models\User;

/**
 * Created by PhpStorm.
 * User: donggege
 * Date: 2018/11/19
 * Time: 10:57
 */
class SocketServer
{
    protected static $redis = null;

    public function __construct()
    {
        $host = env('REDIS_HOST', config('redis.host'));
        $port = env('REDIS_PORT', config('redis.port'));
        $instanceid = env('REDIS_INSTANCEID', config('redis.instanceid'));
        $pwd = env('REDIS_PWD', config('redis.pwd'));
        self::$redis = new \Redis();
        try {
            self::$redis->connect($host, $port);
            self::$redis->auth($instanceid . ":" . $pwd);
            self::$redis->select(1);
        } catch (\Exception $e) {
            //throw new \Exception(env('REDIS_HOST', config('redis.host')));
            throw new \Exception('redis 连接失败');
        }


    }

    /**
     * 用户连接处理
     *
     * @param \swoole_server $server
     * @param $fd
     * @param $data
     */
    public function connect(\swoole_server $server, $fd, $data)
    {
        $user = User::find(array_get($data, 'user_id'));

        if (!$user) {
            $server->close($fd);
        }
        $redisUserFdKey = 'user:fd:' . $user->id;
        $hashData['ip'] = array_get(swoole_get_local_ip(), 'ens33');
        $hashData['fd'] = $fd;
        self::$redis->hmset($redisUserFdKey, $hashData);
        self::$redis->expire($redisUserFdKey, 6000);
        $server->finish(json_encode(['fd' => $fd, 'type' => __FUNCTION__]));
    }

    /**
     * 心跳检测
     *
     * @param \swoole_server $server
     * @param $fd
     * @param $data
     */
    public function keepalive(\swoole_server $server, $fd, $data)
    {
        info($data);

        $redisUserFdKey = 'user:fd:' . $data['user_id'];

        if (!self::$redis->exists($redisUserFdKey)) {
            $server->close($fd);
        }
        self::$redis->expire($redisUserFdKey, 7000);
        if ($server->exist($fd)) {
            $server->finish(json_encode(['fd' => $fd, 'type' => __FUNCTION__]));
        }
    }
}