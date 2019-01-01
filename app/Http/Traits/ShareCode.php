<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/21 0021
 * Time: 上午 11:53
 */

namespace App\Http\Traits;

use Mockery\Exception;

trait ShareCode
{
    private static $code_column = [
        'user_id',
        'share_date'
    ];

    /**
     * @param array $data
     * @return string
     */
    public static function encodeCode(array $data)
    {
        self::checkColumn($data);
        return bin2hex(json_encode($data));
    }

    /**
     * @param $encode_string
     * @return array
     */
    public static function decodeCode($encode_string)
    {
        $decode_data = (array)json_decode(hex2bin($encode_string));
        self::checkColumn($decode_data);

        return $decode_data;
    }

    private static function checkColumn($data){
        foreach (self::$code_column as $column){
            if(!isset($data[$column])){
                throw new Exception('code无法识别');
            }
        }
    }
}