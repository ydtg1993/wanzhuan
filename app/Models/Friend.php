<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Friend extends Model
{
    protected $table = 'friends';
    protected $fillable = [
        'user_id',
        'friends'
    ];
    public $timestamps = false;

    /**
     * 申请添加好友(停用)
     *
     * @author AdamTyn
     *
     * @param string
     * @param string
     * @return boolean
     */
    public static function canAdd($user_id, $user_id_1)
    {
        $temp = self::sharedLock()->firstOrCreate(['user_id' => $user_id]);
        $friends = empty($temp->friends) ? [] : explode('@', $temp->friends);

        if (in_array($user_id_1, $friends)) {
            return -1;
        } else {
            $mf = MakeFriend::sharedLock()->firstOrCreate(['user_id' => $user_id_1]);
            $users = empty($mf->users) ? [] : explode('@', $mf->users);
            if (in_array($user_id, $users)) {
                return 0;
            } else {
                return $mf->addOne($user_id);
            }
        }
    }

    /**
     * 通过好友申请(停用)
     *
     * @author AdamTyn
     *
     * @param string
     * @param string
     * @return boolean
     */
    public static function passApplyAdd($user_id, $user_id_1)
    {
        $mf = MakeFriend::sharedLock()->firstOrCreate(['user_id' => $user_id]);
        $users = empty($mf->users) ? [] : explode('@', $mf->users);

        if (in_array($user_id_1, $users)) {
            $mf->lockForUpdate()->update([
                'users' => substr_count($mf->users, '@') > 0 ? str_replace('@' . strval($user_id_1), '', $mf->users) : str_replace(strval($user_id_1), '', $mf->users)
            ]);
            $temp = self::sharedLock()->firstOrCreate(['user_id' => $user_id]);
            $temp->lockForUpdate()->update([
                'friends' => ltrim(($temp->friends) . '@' . $user_id_1, '@')
            ]);
            $temp = self::sharedLock()->firstOrCreate(['user_id' => $user_id_1]);
            $temp->lockForUpdate()->update([
                'friends' => ltrim(($temp->friends) . '@' . $user_id, '@')
            ]);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取好友信息(停用)
     *
     * @author AdamTyn
     *
     * @param string
     * @param string
     * @return mixed
     */
    public static function showFriends($user_id, $paginate)
    {
        $temp = self::sharedLock()->firstOrCreate(['user_id' => $user_id]);
        $friends = empty($temp->friends) ? [] : explode('@', $temp->friends);

        return DB::table('users')
            ->select('id', 'nickname', 'sexy', 'avatar', 'about', 'level')
            ->whereIn('id', $friends)
            ->orderBy('id')
            ->offset($paginate * 10 - 10)
            ->limit(10)
            ->get();
    }

    /**
     * 查看好友申请(停用)
     *
     * @author AdamTyn
     *
     * @param string
     * @param string
     * @return mixed
     */
    public static function checkApply($user_id, $paginate)
    {
        $mf = MakeFriend::sharedLock()->firstOrCreate(['user_id' => $user_id]);
        $users = empty($mf->users) ? [] : explode('@', $mf->users);

        return DB::table('users')
            ->select('id', 'nickname', 'sexy', 'avatar', 'about', 'level')
            ->whereIn('id', $users)
            ->orderBy('id')
            ->offset($paginate * 10 - 10)
            ->limit(10)
            ->get();
    }

    /**
     * 关联函数
     *
     * @author AdamTyn
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}