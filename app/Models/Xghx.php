<?php

namespace App\Models;

use App\Exceptions\GetException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Xghx extends Model
{
    protected $table='xghxs';
    protected $fillable = [
        'user_id',
        'xg_id',
        'hx_id',
        'hx_pass'
    ];
    public $timestamps = false;

    /**
     * 绑定环信信鸽（停用）
     *
     * @author AdamTyn
     *
     * @param array
     * @param string
     * @return void
     */
    public static function bindXgHx($data,$user_id)
    {
        $xghx=self::firstOrCreate(['user_id'=>$user_id]);
        $xghx->update($data);
    }

    /**
     * 查看环信信鸽
     *
     * @author AdamTyn
     *
     * @param string
     * @return mixed
     *
     * @throws \App\Exceptions\GetException;
     */
    public static function getXgHx($user_id)
    {
        if (DB::table('xghxs')->where('user_id', $user_id)->doesntExist())
            throw new GetException('修改失败，手机号已被使用', '1004');

        return DB::table('xghxs')->select('xg_id', 'hx_id' ,'hx_pass')
            ->where('user_id', $user_id)->first();
    }

    /**
     * 退出环信信鸽（停用）
     *
     * @author AdamTyn
     *
     * @param string
     * @return bool | null
     *
     * @throws \Exception
     */
    public static function outXgHx(string $user_id)
    {
        $temp=self::where('user_id',$user_id)->first();

        return $temp->delete();
    }

    /**
     * 环信每日更新
     *
     * @author AdamTyn
     *
     * @param string
     * @return bool | null
     *
     * @throws \Exception
     */
    public static function updateInfo()
    {
        return null;
    }

    /**
     * 环信注册
     *
     * @author AdamTyn
     *
     * @param array
     * @return bool | null
     *
     * @throws \Exception
     */
    public static function hxRegister(array $info)
    {
        return null;
    }

    /**
     * 关联User
     *
     * @author AdamTyn
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
