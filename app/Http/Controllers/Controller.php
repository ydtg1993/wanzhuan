<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected static $RESPONSE_CODE;
    protected $redis;

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

    /**
     * 返回成功响应
     *
     * @param string $msg
     * @return \Illuminate\Http\JsonResponse
     */
    public function success($data = null)
    {
        return $this->response($data, 0);
    }

    /**
     * 返回错误响应
     *
     * @param null $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function error($data = null, $code = 4002)
    {
        return $this->response($data, $code);
    }

    /**
     * 返回响应
     *
     * @param null $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function response($data = null, $code = 0)
    {
        $statusCode = config('code');
        if (!array_key_exists($code, $statusCode)) {
            $code = 4001;
        }
        $msg = is_string($data) ? $data : $statusCode[$code];
        $data = is_string($data) ? [] : $data;
        return response()->json(['code' => $code, 'msg' => $msg, 'data' => $data], 200, array('Access-Control-Allow-Origin' => '*'));
    }
}
