<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Follow extends Model
{
    protected $table = 'follows';
    public $timestamps = false;
    protected $fillable = [
        'star_id',
        'fan_id',
        'created_at'
    ];

    /**
     * @param array $where
     * @return mixed
     */
    public static function getInfoWhere(array $where = [])
    {
        $data = self::where($where)->first();
        if($data){
            return $data->toArray();
        }

        return [];
    }

    /**
     * 添加关注 | 取消关注
     *
     * @author AdamTyn
     *
     * @param string
     * @param string
     * @return mixed
     *
     * @throws \Exception
     */
    public static function optFollow($fan_id, $star_id)
    {
        if (DB::table('follows')->where('star_id', $star_id)->where('fan_id', $fan_id)->exists()) {
            self::where('star_id', $star_id)->where('fan_id', $fan_id)->delete();
            DB::table('friends')->where('user_id', $star_id)->where('firend_id', $fan_id)->delete();
            DB::table('friends')->where('user_id', $fan_id)->where('firend_id', $star_id)->delete();
        } else {
            self::create([
                'star_id' => $star_id,
                'fan_id' => $fan_id,
                'created_at' => time()
            ]);

            if (DB::table('follows')->where('star_id', $fan_id)->where('fan_id', $star_id)->exists()) {
                DB::table('friends')->insert([
                    'user_id' => $star_id,
                    'firend_id' => $fan_id,
                    'created_at' => time()
                ]);
                DB::table('friends')->insert([
                    'user_id' => $fan_id,
                    'firend_id' => $star_id,
                    'created_at' => time()
                ]);
            }
        }
        $fanFollowCount = DB::table('follows')->where('fan_id', $fan_id)->count();
        $fanFanCount = DB::table('follows')->where('star_id', $fan_id)->count();
        $fanFriendCount = DB::table('friends')->where('user_id', $fan_id)->count();
        DB::table('users')->where('id', $fan_id)->update(['follow_count'=>$fanFollowCount,'fan_count'=>$fanFanCount,'friend_count'=>$fanFriendCount]);

        $starFollowCount = DB::table('follows')->where('fan_id', $star_id)->count();
        $starFanCount = DB::table('follows')->where('star_id', $star_id)->count();
        $starFriendCount = DB::table('friends')->where('user_id', $star_id)->count();
        DB::table('users')->where('id', $star_id)->update(['follow_count'=>$starFollowCount,'fan_count'=>$starFanCount,'friend_count'=>$starFriendCount]);
    }

    /**
     * 获取粉丝信息
     *
     * @author AdamTyn
     *
     * @param string
     * @param int
     * @return mixed
     */
    public static function showFriends($user_id, $paginate)
    {
        $friends = DB::table('friends')
            ->where('user_id', $user_id)
            ->offset(intval($paginate) * 10 - 10)
            ->limit(10)
            ->get();
        $ids = [];
        foreach ($friends as $user) {
            $ids[] = $user->firend_id;
        }
        $user = DB::table('users')
            ->whereIn('id', $ids)
            ->get();
        foreach ($user as &$val){
            $val->identity = boolval($val->identity);
            $val->isMaster = boolval($val->isMaster);

            $val->follow_info = array('is_follow' => 1);
        }
        return count($user) < 1 ? [] : $user;
    }

    /**
     * 获取粉丝信息
     *
     * @author AdamTyn
     *
     * @param string
     * @param int
     * @return mixed
     */
    public static function showFans($star_id, $paginate)
    {
        $user = DB::table('users')
            ->join('follows', 'users.id', '=', 'follows.fan_id')
            ->selectRaw('concat(users.id,"") as id,users.*')
            ->where('follows.star_id', $star_id)
            ->orderBy('id')
            ->offset(10 * intval($paginate) - 10)
            ->limit(10)
            ->get();

        foreach ($user as &$val){
            $val->identity = boolval($val->identity);
            $val->isMaster = boolval($val->isMaster);

            $follow_info = \DB::table('follows')->where('fan_id', $star_id)->where('star_id', $val->id)->first();
            if ($follow_info) {
                $follow_info->is_follow = 1;
                $val->follow_info = $follow_info;
            } else {
                $follow_info = (object)array('is_follow' => 0);
                $val->follow_info = $follow_info;
            }
        }
        return count($user) < 1 ? [] : $user;
    }

    /**
     * 获取关注信息
     *
     * @author AdamTyn
     *
     * @param string
     * @param int
     * @return mixed
     */
    public static function showFollows($fan_id, $paginate)
    {
        $user = DB::table('users')
            ->join('follows', 'users.id', '=', 'follows.star_id')
            ->selectRaw('concat(users.id,"") as id,users.*')
            ->where('follows.fan_id', $fan_id)
            ->orderBy('id')
            ->offset(10 * intval($paginate) - 10)
            ->limit(10)
            ->get();
        foreach ($user as &$val){
            $val->identity = boolval($val->identity);
            $val->isMaster = boolval($val->isMaster);

            $val->follow_info = array('is_follow' => 1);
        }
        return count($user) < 1 ? [] : $user;
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
