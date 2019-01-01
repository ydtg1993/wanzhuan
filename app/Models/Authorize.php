<?php

namespace App\Models;

use App\Exceptions\AuthException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Authorize extends Model
{
    protected $table = 'authorizes';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'game_id',
        'game_account',
        'game_name',
        'server_id',
        'game_server',
        'level_id',
        'game_level',
        'file_path',
        'level_path',
        'status',
        'created_at',
        'updated_at'
    ];

    public static function getInfoWhere(array $where,$orderBy = 'id')
    {
        return self::where($where)->latest($orderBy)->first();
    }

    public static function upInfoWhere(array $data,array $where)
    {
        return self::where($where)->update($data);
    }

    /**
     * 导师游戏认证(人工审核)
     *
     * @author AdamTyn
     *
     * @param array
     * @return void
     *
     * @throws \App\Exceptions\AuthException;
     */
    public static function canAuth($data)
    {
        $temp = self::where('user_id', $data['user_id'])
            ->where('game_id', $data['data']['game_id'])->orderBy('id', 'desc')->first();
        if (empty($temp)) {
            self::create([
                'user_id' => $data['user_id'],
                'game_id' => $data['data']['game_id'],
                'game_account' => $data['data']['game_account'],
                'game_name' => $data['data']['game_name'],
                'server_id' => $data['data']['server_id'],
                'game_server' => $data['data']['game_server'],
                'level_id' => $data['data']['level_id'],
                'game_level' => $data['data']['game_level'],
                'file_path' => $data['data']['file_path'],
                'level_path' => $data['data']['level_path'],
                'created_at' => time()
            ]);
        } else {
            switch ($temp->status) {
                case 0:
                case 5:
                    self::create([
                        'user_id' => $data['user_id'],
                        'game_id' => $data['data']['game_id'],
                        'game_account' => $data['data']['game_account'],
                        'game_name' => $data['data']['game_name'],
                        'server_id' => $data['data']['server_id'],
                        'game_server' => $data['data']['game_server'],
                        'level_id' => $data['data']['level_id'],
                        'game_level' => $data['data']['game_level'],
                        'file_path' => $data['data']['file_path'],
                        'level_path' => $data['data']['level_path'],
                        'created_at' => time()
                    ]);
                    break;
                case 1:
                case 2:
                case 3:
                    throw new AuthException('正在审核中，不可重复申请', '2001');
                case 4:
                    throw new AuthException('此款游戏已认证导师', '2002');
                default:
                    throw new AuthException('无法响应请求，服务端异常', '5002');
            }
        }
    }

    /**
     * 查看导师游戏认证状态
     *
     * @author AdamTyn
     *
     * @param array
     * @return void
     *
     * @throws \App\Exceptions\AuthException;
     */
    public static function CheckAuth($data)
    {
        $checkData = self::where('user_id', $data['user_id'])
            ->where('game_id', $data['game_id'])->select('id', 'user_id', 'status')->first();
        if($checkData){
            return $checkData;
        }
        return (object)null;
        /*
        if (empty($temp))
            throw new AuthException('该用户或该游戏未申请认证', '0');

        switch ($temp->status) {
            case 0:
                throw new AuthException('该用户或该游戏未通过认证', '0');
            case 1:
                throw new AuthException('正在审核中', '0');
            case 2:
                throw new AuthException('此款游戏已认证导师', '0');
            default:
                throw new AuthException('无法响应请求，服务端异常', '5002');
        }
        */
    }

    /**
     * 取消导师游戏认证状态
     *
     * @author AdamTyn
     *
     * @param array
     * @return void
     *
     * @throws \App\Exceptions\AuthException;
     */
    public static function cancelAuthorize($user_id)
    {
        return self::where('user_id', $user_id)->where('status', '<', 3)->update(['status' => 5]);
    }

    /**
     * 查看导师游戏认证状态
     *
     * @author AdamTyn
     *
     * @param array
     * @return void
     *
     * @throws \App\Exceptions\AuthException;
     */
    public static function UserAuthInfo($data)
    {
        $returnData = [];
        $identitiesData = DB::table('identities')->where('user_id', $data['user_id'])->select('id', 'user_id', 'status')->orderBy('id', 'desc')->first();
        $authorizesData = DB::table('authorizes')->where('user_id', $data['user_id'])->select('id', 'user_id', 'status')->orderBy('id', 'desc')->first();
        if($identitiesData){
            $returnData['identities'] = $identitiesData->status;
            if($identitiesData->status == 0){
                return false;
            }
        }else{
            return false;
        }
        if($authorizesData){
            $returnData['authorizes'] = $authorizesData->status;
            if($authorizesData->status == 5){
                return false;
            }
        }else{
            return false;
        }
        return $returnData;
    }

    /**
     * 查看导师游戏认证状态
     *
     * @author AdamTyn
     *
     * @param array
     * @return void
     *
     * @throws \App\Exceptions\AuthException;
     */
    public static function authProgressInfo($data)
    {
        $returnData = [];
        $userInfo = DB::table('users')->where('id', $data['user_id'])->select('id', 'isMaster')->first();
        if(!$userInfo){
            throw new AuthException('用户不存在', '5003');
        }
        $identitiesData = DB::table('identities')->where('user_id', $data['user_id'])->select('id','user_id','status')->orderBy('id','desc')->first();
        $authorizesData = DB::table('authorizes')->where('user_id', $data['user_id'])->select('id','user_id','game_id','game_name','status')->orderBy('id','desc')->first();
        $returnData['user_id'] = $data['user_id'];
        $returnData['is_master'] = $userInfo->isMaster;
        $returnData['progress_code'] = 0;

        if($authorizesData){
            if($authorizesData->status == 0){
                $returnData['progress_code'] = 5;
                $returnData['progress_tag'] = '游戏认证资料审核失败';
            }
            if($authorizesData->status == 1){
                $returnData['progress_code'] = 4;
                $returnData['progress_tag'] = '游戏认证资料等待审核';
            }
            if($authorizesData->status == 2){
                $returnData['progress_code'] = 6;
                $returnData['progress_tag'] = '游戏审核通过未交保证金';
            }
            if($authorizesData->status == 4){
                $returnData['progress_code'] = 7;
                $returnData['progress_tag'] = '无游戏认证信息';
            }
            if($authorizesData->status == 5){
                $returnData['progress_code'] = 7;
                $returnData['progress_tag'] = '无游戏认证信息';
            }
            $returnData['progress_info']['game_id'] = $authorizesData->game_id;
            $returnData['progress_info']['game_name'] = $authorizesData->game_name;
        }else{
            if($identitiesData){
                DB::table('identities')->where('user_id', $data['user_id'])->where('status','<>',2)->delete();
                $returnData['progress_code'] = 0;
                $returnData['progress_tag'] = '未提交认证信息';
            }else{
                $returnData['progress_code'] = 0;
                $returnData['progress_tag'] = '未提交认证信息';
            }
            $returnData['progress_info'] = (object)null;
        }

        return $returnData;
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
