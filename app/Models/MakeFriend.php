<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MakeFriend extends Model
{
    protected $table='make_friend';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'users'
    ];

    /**
     * 添加好友申请(停用)
     *
     * @author AdamTyn
     *
     * @param string
     * @param string
     * @return void
     */
    public function addOne($user_id)
    {
        $this->users=ltrim(($this->users) . '@' . $user_id, '@');
        return $this->save();
    }

    /**
     * 关联函数
     *
     * @author AdamTyn
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id','id');
    }
}