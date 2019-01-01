<?php

namespace App\Models;

use App\Exceptions\AuthException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Identity extends Model
{
    protected $table = 'identities';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'number',
        'realname',
        'file_path',
        'created_at',
        'updated_at',
        'person_video',
        'other_video',
        'person_path',
        'birth',
        'sexy',
        'status'
    ];

    /**
     * 实名认证(人工审核)
     *
     * @author AdamTyn
     *
     * @param array
     * @return void
     *
     * @throws \App\Exceptions\AuthException;
     */
    public static function canIdentity($data)
    {
        if(!isset($data['data']['person_video'])){
            $data['data']['person_video'] = '';
        }
        if(!isset($data['data']['other_video'])){
            $data['data']['other_video'] = '';
        }
        if(!isset($data['data']['person_path'])){
            $data['data']['person_path'] = '';
        }
        $temp = self::where('user_id', $data['user_id'])->orderBy('id','desc')->first();
        if (empty($temp)) {
            self::create([
                'user_id' => $data['user_id'],
                'number' => $data['data']['number'],
                'realname' => $data['data']['name'],
                'file_path' => $data['data']['file_path'],
                'person_video' => $data['data']['person_video'],
                'other_video' => $data['data']['other_video'],
                'person_path' => $data['data']['person_path'],
                'created_at' => time()
            ]);

        } else {
            switch ($temp->status) {
                case 0:
                case 1:
                    DB::table('identities')->where('user_id', $data['user_id'])->delete();
                    self::create([
                        'user_id' => $data['user_id'],
                        'number' => $data['data']['number'],
                        'realname' => $data['data']['name'],
                        'file_path' => $data['data']['file_path'],
                        'person_video' => $data['data']['person_video'],
                        'other_video' => $data['data']['other_video'],
                        'person_path' => $data['data']['person_path'],
                        'created_at' => time()
                    ]);
                    break;
                case 2:
                    $authorizesData = DB::table('authorizes')->where('user_id', $data['user_id'])->select('id', 'user_id', 'status')->orderBy('id', 'desc')->first();
                    if($authorizesData->status == 1){
                        throw new AuthException('游戏认证审核中', '2001');
                    }else{
                        self::create([
                            'user_id' => $data['user_id'],
                            'number' => $data['data']['number'],
                            'realname' => $data['data']['name'],
                            'file_path' => $data['data']['file_path'],
                            'person_video' => $data['data']['person_video'],
                            'other_video' => $data['data']['other_video'],
                            'person_path' => $data['data']['person_path'],
                            'created_at' => time()
                        ]);
                        break;
                    }
                default:
                    throw new AuthException('无法响应请求，服务端异常', '5002');
            }
        }
    }

    /**
     * 查看实名认证状态
     *
     * @author AdamTyn
     *
     * @param array
     * @return void
     *
     * @throws \App\Exceptions\AuthException;
     */
    public static function CheckIdentity($data)
    {
        $checkData = self::where('user_id', $data['user_id'])->select('id', 'user_id', 'status')->first();
        if($checkData){
            return $checkData;
        }
        return (object)null;
        /*
        if (empty($temp))
            throw new AuthException('该用户未申请认证', '0');

        switch ($temp->status) {
            case 0:
                throw new AuthException('未通过认证', '0');
            case 1:
                throw new AuthException('正在审核中', '0');
            case 2:
                throw new AuthException('认证已通过', '0');
            default:
                throw new AuthException('无法响应请求，服务端异常', '5002');
        }
        */
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
