<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;

trait UserTrait
{
    /**
     * 关联Wallet
     *
     * @author AdamTyn
     */
    public function wallet()
    {
        return $this->hasOne('App\Models\Wallet', 'user_id', 'id');
    }

    /**
     * 关联Blacklist
     *
     * @author AdamTyn
     */
    public function blacklist()
    {
        return $this->hasOne('App\Models\Blacklist', 'user_id', 'id');
    }

    /**
     * 社交数据
     *
     * @author AdamTyn
     *
     * @param string
     * @return mixed
     */
    public static function socialInfo($user_id)
    {
        $return['friends'] = DB::query('')->sharedLock()
            ->selectRaw('f_1.fan_id from follows as f_1,follows as f_2')
            ->whereRaw('f_1.star_id = f_2.fan_id and f_2.star_id = f_1.fan_id and f_1.star_id = ' . $user_id)
            ->get()->count();
        $return['follows'] = DB::table('follows')->sharedLock()->where('fan_id', $user_id)->count();
        $return['fans'] = DB::table('follows')->sharedLock()->where('star_id', $user_id)->count();

        return $return;
    }

    /**
     * 关联VirOrder
     *
     * @author AdamTyn
     */
    public function master()
    {
        return $this->hasOne('App\Models\Master', 'user_id', 'id');
    }

    /**
     * 关联VirContract
     *
     * @author AdamTyn
     */
    public function virContracts()
    {
        return $this->hasMany('App\Models\VirContract', 'user_id', 'id');
    }

    /**
     * 关联CheckIn
     *
     * @author AdamTyn
     */
    public function checkIn()
    {
        return $this->hasOne('App\Models\CheckIn', 'user_id', 'id');
    }

    /**
     * 关联NormalOrder
     *
     * @author AdamTyn
     */
    public function normalOrder()
    {
        return $this->hasMany('App\Models\NormalOrder', 'user_id', 'id');
    }

    /**
     * 关联TeamOrder
     *
     * @author AdamTyn
     */
    public function teamOrder()
    {
        return $this->hasMany('App\Models\TeamOrder', 'user_id', 'id');
    }

    /**
     * 关联SkillOrder
     *
     * @author AdamTyn
     */
    public function skillOrder()
    {
        return $this->hasMany('App\Models\SkillOrder', 'user_id', 'id');
    }

    /**
     * 关联NormalOrder
     *
     * @author AdamTyn
     */
    public function _normalOrder()
    {
        return $this->hasMany('App\Models\NormalOrder', 'user_id_1', 'id');
    }

    /**
     * 关联Follow
     *
     * @author AdamTyn
     */
    public function follow()
    {
        return $this->hasOne('App\Models\Follow', 'user_id', 'id');
    }

    /**
     * 关联Resource
     *
     * @author AdamTyn
     */
    public function resources()
    {
        return $this->hasMany('App\Models\Resource', 'user_id', 'id');
    }

    /**
     * 关联Ticket
     *
     * @author AdamTyn
     */
    public function tickets()
    {
        return $this->hasMany('App\Models\Ticket', 'user_id', 'id');
    }

    /**
     * JWT
     *
     * @author AdamTyn
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * JWT
     *
     * @author AdamTyn
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}