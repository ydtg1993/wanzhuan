<?php

namespace App\Http\AppointmentControllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected static $RESPONSE_CODE;

    function __construct()
    {
        self::$RESPONSE_CODE = ResponseCode::getInstance();
        //redis信息
        $host = env('REDIS_HOST', config('redis.host'));
        $port = env('REDIS_PORT', config('redis.port'));
        $instanceid = env('REDIS_INSTANCEID', config('redis.instanceid'));
        $pwd = env('REDIS_PWD', config('redis.pwd'));
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
        $this->redis->auth($instanceid . ":" . $pwd);
    }
}
