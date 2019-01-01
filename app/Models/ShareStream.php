<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShareStream extends Model
{
    protected $table = 'share_stream';
    public $timestamps = false;

    /**
     * @param $mobile
     * @param $code
     * @return mixed
     */
    public static function getInfo($mobile,$code)
    {
        return self::where('mobile',$mobile)
            ->where('share_code',$code)
            ->first();
    }

    /**
     * @param $mobile
     * @param int $status
     * @return mixed
     */
    public static function getAllByStatus($mobile,$status = 0)
    {
        return self::where('status',$status)
            ->where('mobile',$mobile)
            ->get();
    }

    /**
     * @param $mobile
     * @param int $has_played
     * @return mixed
     */
    public static function getAllShareUser($mobile,$has_played = 0)
    {
        return self::where('has_played',$has_played)
            ->where('mobile',$mobile)
            ->orderBy('share_user_id')
            ->get();
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function add($data)
    {
        return self::insert($data);
    }

    /**
     * 领券操作
     * @param $mobile
     * @return mixed
     */
    public static function upStatus($mobile)
    {
        return self::where('mobile',$mobile)->update(['status'=>1]);
    }

    /**
     * 用户已经玩过游戏
     * @param $mobile
     * @return mixed
     */
    public static function upPlayed($mobile)
    {
        return self::where('mobile',$mobile)->update(['has_played'=>1]);
    }
}
