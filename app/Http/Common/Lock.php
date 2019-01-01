<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/22 0022
 * Time: 下午 4:50
 */

namespace App\Http\Common;


class Lock
{
    private static $path = PROGECT_ROOT_PATH.'/lock/';

    public function judge($tag)
    {
        $cache_key = RedisDriver::getInstance()->getCacheKey('hash.lock');
        $flag = RedisDriver::getInstance()->redis->hExists($cache_key,$tag);
        if($flag === false){
            $t = rand(1,500);
            usleep($t);
            $flag = RedisDriver::getInstance()->redis->hSet($cache_key,$tag,true);
            if($flag){
                return true;
            }
            return false;
        }

        return false;
    }

    public function unlash($tag)
    {
        $cache_key = RedisDriver::getInstance()->getCacheKey('hash.lock');
        RedisDriver::getInstance()->redis->hDel($cache_key,$tag);
    }


}