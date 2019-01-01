<?php

namespace App\Models;

use App\Events\UserCreated;
use App\Events\UserUpdated;
use App\Exceptions\AuthException;
use App\Exceptions\UpdateException;
use App\Libraries\helper\Helper;
use App\Libraries\LBS\Services\LBSServer;
use App\Models\Traits\UserTrait;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, UserTrait;

    protected $table = 'users';
    public $timestamps = false;
    protected $fillable = [
        'nickname',
        'mobile',
        'avatar',
        'status',
        'app_level',
        'location',
        'created_at',
        'updated_at',
        'about',
        'sexy',
        'birth',
        'identity',
        'isMaster',
        'wx_id',
        'xcx_id',
        'qq_id',
        'hx_id',
        'invite_number',
        'follow_count',
        'friend_count',
        'fan_count',
        'isGirl',
        'profession'
    ];

    protected $dispatchesEvents = [
        'created' => UserCreated::class,
        'updated' => UserUpdated::class
    ];

    public static function getAllWhere(array $where = [],$order_by = 'id',$sort = 'ASC')
    {
        $data = self::where($where)->orderBy($order_by, $sort)->get();
        if($data){
            return $data->toArray();
        }

        return [];
    }

    public static function countAllWhere(array $where = [])
    {
        return self::where($where)->count();
    }

    /**
     * 关联动态表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dynamics()
    {
        return $this->hasMany(Dynamic::class);
    }


    public static function getAllInWhere($flied, $where, $columns = ['*'])
    {
        return self::whereIn($flied, $where)->select($columns)->with('tagList')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tagList()
    {
        return $this->hasMany('App\Models\UserTag', 'user_id', 'id');
    }

    public static function upInfoWhere(array $data, array $where)
    {
        return self::where($where)->update($data);
    }

    /**
     * 手机登录验证
     *
     * @author AdamTyn
     *
     * @param array
     * @return mixed
     *
     * @throws \App\Exceptions\AuthException;
     */
    public static function loginByMobile($request)
    {
        $user = self::firstOrCreate([
            'mobile' => $request['mobile']
        ], [
            'created_at' => time(),
            'nickname' => rand_name()
        ]);

        if (!empty($request['data']) && isset($request['data']['type'])) {
            // if (isset($request['data']['name']) && !empty($request['data']['name'])) {
            //     $otherUser = self::where('nickname', $request['data']['name'])->first();
            //     if ($otherUser && ($otherUser->mobile != $request['mobile'])) {
            //         if (($otherUser->nickname) == $request['data']['name'])
            //             throw new AuthException('当前昵称已存在', '2003');
            //     }
            // }
            $user->avatar = $request['data']['avatar'];
            $user->nickname = $request['data']['name'];
            switch (intval($request['data']['type'])) {
                case 0:
                    if (($user->wx_id) == $request['data']['id'])
                        throw new AuthException('当前手机号已经绑定过微信', '2003');
                    $user->wx_id = $request['data']['id'];
                    break;
                case 1:
                    if (($user->wx_id) == $request['data']['id'])
                        throw new AuthException('当前手机号已经绑定过QQ', '2003');
                    $user->qq_id = $request['data']['id'];
                    break;
                default:
                    throw new AuthException('请求失败，`type`参数无效或错误', '4000');
                    break;
            }
        }
        if (isset($request['data']['system'])) {
            $user->system = $request['data']['system'];
        }
        $user->save();
        return $user;
    }

    /**
     * QQ微信登录验证
     * @author AdamTyn
     * @param string
     * @return mixed
     * @throws \App\Exceptions\AuthException;
     */
    public static function loginByQQ($qq_id)
    {
        if (DB::table('users')->where('qq_id', $qq_id)->doesntExist()) {
            return false;
        }
        //throw new AuthException('当前QQ未绑定手机号', '1001');
        $user = self::where('qq_id', $qq_id)->first();
        if (!empty($request['data']) && isset($request['data']['system'])) {
            $user->system = $request['data']['system'];
        }
        $user->save();
        return $user;
    }

    /**
     * 微信登录验证
     * @author AdamTyn
     * @param string
     * @return mixed
     * @throws \App\Exceptions\AuthException;
     */
    public static function LoginByWX($wx_id)
    {
        if (DB::table('users')->where('wx_id', $wx_id)->doesntExist()) {
            return false;
        }
        //throw new AuthException('当前微信未绑定手机号', '1001');
        $user = self::where('wx_id', $wx_id)->first();
        if (!empty($request['data']) && isset($request['data']['system'])) {
            $user->system = $request['data']['system'];
        }
        $user->save();
        return $user;
    }

    /**
     * 拼接基本信息
     * @author AdamTyn
     * @param string
     * @return array
     */
    public static function getBasic($user_id)
    {
        // 拼接基本信息
        $user = self::find($user_id);
        if (!$user) {
            throw new UpdateException('用户不存在', '4004');
        }
        $user->identity = boolval($user->identity);
        $user->isMaster = boolval($user->isMaster);
        //语音
        $audio = \DB::table('resources')->where(['user_id' => $user_id, 'status' => 0, 'kind' => 6])->orderBy('created_at', 'DESC')->first();
        if($audio){
            $user->audio = $audio->path;
        }else{
            $user->audio = '';
        }
        //活跃状态
        if($user->logout_time == 0){
            $user->active = '当前在线';
        }else{
            $active = Helper::differTime(TIME - $user->logout_time);
            $user->active = $active.'活跃';
        }

        return $user;
    }

    /**
     * 拼接基本信息
     * @author AdamTyn
     * @param string
     * @return array
     */
    public static function getProfile($user_id, $other_user_id)
    {
        // 拼接基本信息
        $user = self::find($other_user_id);
        if (!$user) {
            throw new UpdateException('用户不存在', '4004');
        }
        $user->identity = boolval($user->identity);
        $user->isMaster = boolval($user->isMaster);
        $user->skills = [];
        $user->masterInfo = (object)null;
        if ($user->isMaster) {
            $masterInfo = DB::table('masters')->where('user_id', $other_user_id)->first();
            if ($masterInfo) {
                $user->masterInfo = $masterInfo;
            }
            $skills = DB::table('skills')->where('master_user_id', $other_user_id)->get();
            $user->skills = $skills;
        }

        $follow_info = DB::table('follows')->where('fan_id', $user_id)->where('star_id', $other_user_id)->first();
        if ($follow_info) {
            $follow_info->is_follow = 1;
            $user->follow_info = $follow_info;
        } else {
            $follow_info = (object)array('is_follow' => 0);
            $user->follow_info = $follow_info;
        }
        //距离
        $user->distance = Geo::getDistance($user_id, $other_user_id );
        //活跃状态
        if($user->logout_time == 0){
            $user->active = '当前在线';
        }else{
            $active = Helper::differTime(TIME - $user->logout_time);
            $user->active = $active.'活跃';
        }
        //语音
        $audio = \DB::table('resources')->where(['user_id' => $other_user_id, 'status' => 0, 'kind' => 6])->orderBy('created_at', 'DESC')->first();
        if($audio){
            $user->audio = $audio->path;
        }else{
            $user->audio = '';
        }

        return $user;
    }

    /**
     * 拼接基本信息
     * @author AdamTyn
     * @param string
     * @return array
     */
    public static function getHxProfile($user_id)
    {
        // 拼接基本信息
        $user = self::find($user_id);
        if (!$user) {
            throw new UpdateException('用户不存在', '4004');
        }
        $user->identity = boolval($user->identity);
        $user->isMaster = boolval($user->isMaster);
        $user->skills = [];
        $user->masterInfo = (object)null;
        if ($user->isMaster) {
            $masterInfo = DB::table('masters')->where('user_id', $user_id)->first();
            if ($masterInfo) {
                $user->masterInfo = $masterInfo;
            }
            $skills = DB::table('skills')->where('master_user_id', $user_id)->get();
            $user->skills = $skills;
        }
        return $user;
    }

    /**
     * 技能的信息
     * @author AdamTyn
     * @param int
     * @return mixed
     */
    public static function getSkill($user_id)
    {
        return DB::table('skills')
            ->where('master_user_id', $user_id)->where('status', 1)
            ->get();
    }

    /**
     * 聊天对象的信息
     * @author AdamTyn
     * @param int
     * @return mixed
     */
    public static function chatInfo($user_id_1)
    {
        return DB::table('users')->sharedLock()
            ->join('xghxs', 'users.id', '=', 'xghxs.user_id')
            ->select('users.level')
            ->where('xghxs.user_id', $user_id_1)
            ->first();
    }

    /**
     * 更新基本信息
     * @author AdamTyn
     * @param array
     * @return mixed
     *
     * @throws \App\Exceptions\UpdateException;
     */
    public static function setMobile($data)
    {
        if (DB::table('users')->where('mobile', $data['mobile'])->exists())
            throw new UpdateException('修改失败，手机号已被使用', '1004');

        return self::find($data['user_id'])
            ->update(['mobile' => $data['mobile'], 'updated_at' => time()]);
    }

    /**
     * 更新基本信息
     * @author AdamTyn
     * @param array
     * @return mixed
     * @throws \App\Exceptions\UpdateException;
     */
    public static function setNickname($data)
    {
        // if (DB::table('users')->where('id', '<>', $data['user_id'])->where('nickname', $data['nickname'])->exists())
        //     throw new UpdateException('修改失败，昵称已被使用', '1004');

        return self::find($data['user_id'])
            ->update(['nickname' => $data['nickname'], 'updated_at' => time()]);
    }

    /**
     * 更新基本信息
     * @author AdamTyn
     * @param array
     * @return mixed
     */
    public static function setBasic($data)
    {
        $updateData = [];
        $user = self::find($data['user_id']);

        foreach ($data['data'] as $k => $v) {
            if ($v != '') {
                $updateData[$k] = $v;
            }
            if ($k == 'sexy' && !$user->avatar) {
                if ($v == '女') {
                    $avatar = 'https://avatar-1257042421.cos.ap-chengdu.myqcloud.com/icon_default%20Girl%402x.png';
                } else {
                    $avatar = 'https://avatar-1257042421.cos.ap-chengdu.myqcloud.com/icon_default%20boy%402x.png';
                }
                $updateData['avatar'] = $avatar;
            }
        }
        $user->update(array_collapse([$updateData, ['updated_at' => time()]]));
    }

    /**
     * 更新基本信息
     * @author AdamTyn
     * @param array
     * @return mixed
     */
    public static function takeCash($data)
    {
        $order_id = self::createOrderId();
        DB::table('user_take_cash')->insert([
            'order_id' => $order_id,
            'user_id' => $data['user_id'],
            'type' => $data['data']['type'],
            'cash_account' => $data['data']['cash_account'],
            'money' => $data['data']['money'],
            'status' => 0,
            'created_at' => time()
        ]);
        DB::table('wallets')->where('user_id', $data['user_id'])->decrement('cash', $data['data']['money']);
        $transactionData = [];
        $transactionData['user_id'] = $data['user_id'];
        $transactionData['order_id'] = $order_id;
        $transactionData['money'] = $data['data']['money'];
        $transactionData['title'] = '用户提现';
        $transactionData['desc'] = '用户提现';
        $transactionData['type'] = 4;
        $transactionData['status'] = 1;
        $transactionData['created_at'] = time();
        DB::table('user_transaction')->insert($transactionData);
    }

    private static function createOrderId()
    {
        $order_id_main = 'cash_' . date('YmdHis');
        $random = rand(10000000, 99999999);
        $id = (100 - $random % 100) % 100;
        $order_id = $order_id_main . str_pad($id, 6, '0', STR_PAD_LEFT);
        return $order_id;
    }

}
