<?php

namespace App\Models;

use App\Http\Common\RedisDriver;
use App\Libraries\helper\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Master extends Model
{
    protected $table = 'masters';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'order_count',
        'status',
        'arg_score'
    ];

    /**
     * 导师个人主页
     *
     * @author AdamTyn
     *
     * @param array
     * @return array
     */
    public static function masterHome($data)
    {
        $master = self::where('user_id', $data['master_id'])->first();

        if (empty($master))
            return null;

        // 拼接基本信息
        $user = $master->user()->first();
        $return['nickname'] = $user->nickname;
        $return['avatar'] = empty($user->avatar) ? null : $user->avatar;
        $return['location'] = empty($user->location) ? null : $user->location;
        $return['birth'] = empty($user->birth) ? null : $user->birth;
        $return['level'] = $user->level;
        $return['about'] = empty($user->about) ? null : $user->about;
        $return['sexy'] = empty($user->sexy) ? null : $user->sexy;

        // 拼接标准技能信息
        $return['normal_skill'] = Skill::getNormal($data['master_id']);

        // 拼接自定义技能信息
        // $return['diy_skill'] = Skill::getDIY($data['master_id']);

        // 拼接社交信息
        $return['social_count'] = User::socialInfo($data['master_id']);

        $return['isFollow'] = DB::table('follows')->where('fan_id', $data['user_id'])->where('star_id', $data['master_id'])->exists();

        return $return;
    }

    public static function addMaster($data)
    {
        return self::insert($data);
    }

    /**
     * 用户游戏数据
     *
     * @author AdamTyn
     *
     * @param array
     * @return mixed
     */
    public static function masterInfo($user_id)
    {
        $master = self::where('user_id', $user_id)->first();

        return $master;
    }

    /**
     * 获取导师接单范围
     * @param $user_id
     * @return array|null|string
     * @throws \Exception
     */
    public function getMasterRange($user_id)
    {
        /*$cache_key = RedisDriver::getInstance()->getCacheKey('hash.masterRange');
        if(RedisDriver::getInstance()->redis->hExists($cache_key,$user_id)){
            $data = RedisDriver::getInstance()->redis->hGet($cache_key,$user_id);
            return (array)json_decode($data,true);
        }*/

        $data = self::where('user_id', $user_id)
            ->with('user')
            ->with('range')
            ->with(['skill'=>function($query){$query->select('master_user_id','game_id','game_name');}])
            ->first();
        if(!$data){
            return null;
        }
        $data = $data->toArray();
        //RedisDriver::getInstance()->redis->hSet($cache_key,$user_id,json_encode($data));

        return $data;
    }

    /**
     * @param $user_id
     * @param $game_id
     * @return array|null|string
     * @throws \Exception
     */
    public function getMasterGameRange($user_id,$game_id)
    {
        $cache_key = RedisDriver::getInstance()->getCacheKey('hash.masterRange');
        if(RedisDriver::getInstance()->redis->hExists($cache_key,$user_id)){
            $data = RedisDriver::getInstance()->redis->hGet($cache_key,$user_id);
            $data = (array)json_decode($data,true);
            $data['range'] = Helper::multiQuery2Array($data['range'],['game_id'=>$game_id]);
            return $data;
        }

        $data = self::where('user_id', $user_id)
            ->with('user')
            ->with('range')
            ->with(['skill'=>function($query){$query->select('master_user_id','game_id','game_name');}])
            ->first();
        if(!$data){
            return null;
        }
        $data = $data->toArray();
        RedisDriver::getInstance()->redis->hSet($cache_key,$user_id,json_encode($data));
        $data['range'] = Helper::multiQuery2Array($data['range'],['game_id'=>$game_id]);

        return $data;
    }

    /**
     * @param $user_id
     */
    public static function incrementCountOrder($user_id)
    {
        self::where('user_id', $user_id)
            ->increment('order_count');
    }

    /**
     * @param $data
     * @return int
     */
    public static function updateInfo($data)
    {
        return DB::table('masters')->where('user_id', $data['user_id'])
            ->update($data);
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

    /**
     * 接单能力范围
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function range()
    {
        return $this->hasMany('App\Models\MasterRange', 'master_id','user_id');
    }

    /**
     * 导师游戏认证
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function skill()
    {
        return $this->hasMany('App\Models\Skill',  'master_user_id','user_id');
    }
}