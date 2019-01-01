<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verify extends Model
{
    protected $table = 'codes';
    public $timestamps = false;
    protected $fillable = [
        'mobile',
        'verify',
        'type',
        'created_at',
        'expired_at'
    ];

    /**
     * 更新用户验证码
     * @author AdamTyn
     * @param string
     * @param string
     * @return void
     */
    public static function setVerify($mobile, $verify)
    {
        $now = time();
        $last = $now - 60;
        $res = self::where('mobile',$mobile)->where('type',1)->orderBy('id', 'desc')->first();
        if($res){
            if($res->created_at > $last){
                return false;
            }
        }
        self::create([
            'mobile' => $mobile,
            'verify' => $verify,
            'type'   => 1,
            'created_at' => $now,
            'expired_at' => $now + 300
        ]);
        return true;
    }

    /**
     * 更新提现验证码
     * @author AdamTyn
     * @param string
     * @param string
     * @return void
     */
    public static function setCashVerify($mobile, $verify)
    {
        $now = time();
        $last = $now - 60;
        $res = self::where('mobile',$mobile)->where('type',2)->orderBy('id', 'desc')->first();
        if($res){
            if($res->created_at > $last){
                return false;
            }
        }
        self::create([
            'mobile' => $mobile,
            'verify' => $verify,
            'type'   => 2,
            'created_at' => $now,
            'expired_at' => $now + 300
        ]);
        return true;
    }

    /**
     * 清理废弃验证码
     * @author AdamTyn
     * @param string
     * @param string
     * @return void
     */
    public static function outAllVerify()
    {
        self::where('expired_at','<',time())->forceDelete();
    }
}