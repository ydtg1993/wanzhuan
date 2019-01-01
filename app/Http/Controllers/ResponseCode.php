<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 下午 7:26
 */

namespace App\Http\Controllers;

use http\Env\Request;

class ResponseCode
{

    /*
    0 => 'success',
    1000 => '数据已过期',
    1001 => '未绑定手机号',
    1002 => '不可重复兑换优惠券',
    1003 => '优惠券不存在或已经被使用',
    1004 => '修改失败，昵称或手机号已被使用',

    2000 => '不可作用于当前用户自身',
    2001 => '正在审核中',
    2002 => '已认证',
    2003 => '当前手机号已经绑定过微信或QQ',

    4000 => '请求出错，缺少必须的参数',
    4001 => '无效令牌，需要重新获取',
    4002 => '请求出错，参数无效或错误',
    4003 => '当前接口禁止访问',
    4005 => '请求出错，user_id与令牌不匹配',

    5000 => '系统错误，无法生成令牌',
    5001 => '当前客户端未授权，需要重新登录',
    5002 => '无法响应请求，服务端异常',
    5003 => 'API系统维护中',
    5004 => '暂无数据',
    5005 => '保存数据失败',
    5006 => '三方验签失败'
    */
    private static $config = null;

    private static $set_msg = '';

    private static $instance = null;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function Code($code,$data = array())
    {
        if(self::$config === null){
            self::$config = config('code');
        }

        if(self::$set_msg == ''){
            $msg = self::$config[$code];
        }else{
            $msg = self::$set_msg;
            self::$set_msg = '';//销毁
        }
        if($code){
            (new LogController())->resposeLog($data);
        }
        return response()->json(['code'=>$code,'msg'=>$msg,'data'=>$data],200,array('Access-Control-Allow-Origin' => '*'));
    }

    /**
     * @param $msg
     * @return $this
     */
    public function setMsg($msg)
    {
        self::$set_msg = $msg;
        return $this;
    }
}