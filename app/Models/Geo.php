<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/7 0007
 * Time: 下午 4:47
 */

namespace App\Models;


use App\Http\Common\RedisDriver;
use App\Libraries\helper\Helper;

class Geo extends BaseModel
{
    /**
     * @param $user_id
     * @param $longitude
     * @param $latitude
     * @return bool
     * @throws \Exception
     */
    public static function set($user_id, $longitude, $latitude)
    {
        $result = User::upInfoWhere([
            'longitude' => $longitude,
            'latitude' => $latitude,
            'updated_at'=>TIME],['id'=>$user_id]);

        if (!$result) {
            return false;
        }

        $cache_key = RedisDriver::getInstance()->getCacheKey('geo.position');
        RedisDriver::getInstance()->predis->zrem($cache_key,$user_id);
        return RedisDriver::getInstance()->predis->geoadd($cache_key,$longitude, $latitude,$user_id);
    }

    public static function getNear($user_id,$game_id,$longitude, $latitude,$distance,$unit = 'km')
    {
        $cache_key = RedisDriver::getInstance()->getCacheKey('geo.position');
        $users = RedisDriver::getInstance()->predis->georadius($cache_key, $longitude, $latitude,$distance,$unit);

        $data = [];
        if(empty($users)){
            return $data;
        }

        $user_base_infos = (User::getAllInWhere('id',$users,['id','sexy','avatar','longitude','latitude']))->toArray();
        $limit = 50;
        $i = 1;
        shuffle($users);

        foreach ($users as $user){
            if($i>=$limit){
                continue;
            }

            if($user_id == $user){
                continue;
            }

            $user_base_info = current(Helper::multiQuery2Array($user_base_infos,['id'=>$user]));
            if(!$user_base_info){
                continue;
            }

            unset($user_base_info['tag_list']);
            $data[] = $user_base_info;
            $i++;
        }

        return $data;
    }

    public static function getDistance($user,$other)
    {
        $cache_key = RedisDriver::getInstance()->getCacheKey('geo.position');
        return RedisDriver::getInstance()->predis->geodist($cache_key,$user,$other,'km');
    }
}