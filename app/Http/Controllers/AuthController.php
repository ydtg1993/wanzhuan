<?php

namespace App\Http\Controllers;

use App\Http\Controllers\entrust\PresentTicketController;
use App\Jobs\SmsJob;
use App\Models\User;
use App\Models\UserTransaction;
use App\Models\Verify;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Qcloud\Sms\SmsSingleSender;

require_once __DIR__ . '/../../Libraries/cmq-sdk/cmq_api.php';

class AuthController extends Controller
{
    /**
     * 获取验证码
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithMobile;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function getVerify(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        try {
            $code = rand_code();
            $res = Verify::setVerify($request->input('mobile'), $code);
            if(!$res){
                $response['code'] = '4000';
                $response['msg'] = '请稍后再试';
                return response()->json($response);
            }
            $sms_app_id = config('cloud.sms_app_id');
            $sms_app_key = config('cloud.sms_app_key');
            $ssender = new SmsSingleSender($sms_app_id, $sms_app_key);
            $params = [$code, 5];
            $smsSign = "玩转科技";
            $result = $ssender->sendWithParam("86", $request->input('mobile'), 153083, $params, $smsSign, "", "");
            $response['data']['code'] = $code;
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }
        return response()->json($response);
    }

    /**
     * 获取验证码
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithMobile;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function getCashVerify(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        if (!($request->has('user_id'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`user_id`参数';
            return response()->json($response);
        }
        try {
            $user = DB::table('users')->where('id', $request->input('user_id'))->first();
            if(!$user){
                $response['code'] = '4000';
                $response['msg'] = '请求出错，`user_id`参数错误';
                return response()->json($response);
            }
            $mobile = $user->mobile;
            $code = rand_code();
            $res = Verify::setCashVerify($mobile, $code);
            if(!$res){
                $response['code'] = '4000';
                $response['msg'] = '请稍后再试';
                return response()->json($response);
            }
            $sms_app_id = config('cloud.sms_app_id');
            $sms_app_key = config('cloud.sms_app_key');
            $ssender = new SmsSingleSender($sms_app_id, $sms_app_key);
            $params = [$code, 5];
            $smsSign = "玩转科技";
            $result = $ssender->sendWithParam("86", $mobile, 179678, $params, $smsSign, "", "");
            //$response['data']['code'] = $code;
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }
        return response()->json($response);
    }

    /**
     * 手机验证码登录
     *
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithMobile;
     * @middleware \App\Http\Middleware\CheckVerify;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     * @throws \App\Exceptions\AuthException;
     */
    public function mobile(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = User::loginByMobile($request->only('mobile', 'data'));
            if(!$user->hx_id || !$user->xg_id){
                $options['client_id'] = config('easemob.client_id');
                $options['client_secret'] = config('easemob.client_secret');
                $options['org_name'] = config('easemob.org_name');
                $options['app_name'] = config('easemob.app_name');
                $easemob = new \Easemob($options);
                $username = $user->mobile . '-' . $user->id;
                $res = $easemob->getUser($username);
                if(!isset($res['error']) || !$res['error']){
                    DB::table('users')->where('id', $user->id)->update(['hx_id'=>$username,'xg_id'=>$username]);
                    $easemob->editNickname($username, $user->nickname);
                } else {
                    $res = $easemob->createUser($username, md5($user->mobile));
                    if(!isset($res['error']) || !$res['error']){
                        $easemob->editNickname($username, $user->nickname);
                        DB::table('users')->where('id', $user->id)->update(['hx_id'=>$username,'xg_id'=>$username]);
                    }
                }
                $present = 1000;
                Wallet::firstOrCreate(['user_id'=>$user->id]);
                /*Wallet::addUserTransaction([
                    'user_id' => $user->id,
                    'order_id' => '',
                    'money' => $present,
                    'title' => '赠送',
                    'desc' => '平台赠送',
                    'type' => 0,
                    'status' => 1,
                    'created_at' => TIME
                ]);*/
            }
            if (!($token = Auth::login($user))) {
                DB::rollBack();
                return self::$RESPONSE_CODE->setMsg('系统错误，无法生成令牌')->Code(5000);
            } else {
                $data = [];
                $data['user_id'] = strval($user->id);
                $data['access_token'] = $token;
                $data['expires_in'] = strval(time() + 86400);
            }
            (new PresentTicketController())->sharePresent($user->id,$user->mobile);
            $user_info = User::getBasic($user->id);
            if(!$user_info->from){
                $from = $request->has('from') ? (int)$request->input('from') : 0;
                User::upInfoWhere(['from'=>$from],['id'=>$user->id]);
            }
        } catch (QueryException $queryException) {
            DB::rollBack();
            return self::$RESPONSE_CODE->setMsg('无法响应请求，服务端异常')->Code(5002);
        }

        DB::commit();

        $options['client_id'] = config('easemob.client_id');
        $options['client_secret'] = config('easemob.client_secret');
        $options['org_name'] = config('easemob.org_name');
        $options['app_name'] = config('easemob.app_name');
        $easemob = new \Easemob($options);
        $target_type = 'users';
        $target = array($user->hx_id);
        $from = 'official';
        $content = '恭喜您，登陆成功';
        $ext['title'] = '';
        $ext['type'] = '2';
        $ext['orderInfo'] = '';
        $ext['redirectInfo'] = '';
        $ext['nickname'] = '官方通告';
        $ext['avatar'] = 'http://image.wanzhuanhuyu.cn/game-icon/official.png';
        $easemob->sendText($from, $target_type, $target, $content, $ext);
        
        return self::$RESPONSE_CODE->Code(0,$data);
    }

    /**
     * 微信登录
     * @author AdamTyn
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     * @throws \App\Exceptions\AuthException;
     */
    public function wx(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        if (!($request->has('wx_id'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`wx_id`参数';
        } else {
            try {
                $user = User::LoginByWX($request->input('wx_id'));
                if(is_bool($user) && !$user){
                    $response['msg'] = '当前微信未绑定手机号';
                    $response['data']['user_id'] = '0';
                    $response['data']['access_token'] = '';
                    $response['data']['expires_in'] = 0;
                    return response()->json($response);
                }
                if(!$user->hx_id || !$user->xg_id){
                    $options['client_id'] = config('easemob.client_id');
                    $options['client_secret'] = config('easemob.client_secret');
                    $options['org_name'] = config('easemob.org_name');
                    $options['app_name'] = config('easemob.app_name');
                    $easemob = new \Easemob($options);
                    $username = $user->mobile . '-' . $user->id;
                    $res = $easemob->getUser($username);
                    if(!isset($res['error']) || !$res['error']){
                        DB::table('users')->where('id', $user->id)->update(['hx_id'=>$username,'xg_id'=>$username]);
                        $easemob->editNickname($username, $user->nickname);
                    } else {
                        $res = $easemob->createUser($username, md5($user->mobile));
                        if(!isset($res['error']) || !$res['error']){
                            $easemob->editNickname($username, $user->nickname);
                            DB::table('users')->where('id', $user->id)->update(['hx_id'=>$username,'xg_id'=>$username]);
                        }
                    }
                    $present = 1000;
                    Wallet::firstOrCreate(['user_id'=>$user->id]);
                    Wallet::addUserTransaction([
                        'user_id' => $user->id,
                        'order_id' => '',
                        'money' => $present,
                        'title' => '赠送',
                        'desc' => '平台赠送',
                        'type' => 0,
                        'status' => 1,
                        'created_at' => TIME
                    ]);
                }
                if (!($token = Auth::login($user))) {
                    $response['code'] = '5002';
                    $response['msg'] = '无法响应请求，服务端异常';
                } else {
                    $response['data']['user_id'] = strval($user->id);
                    $response['data']['access_token'] = $token;
                    $response['data']['expires_in'] = strval(time() + 86400);
                }
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }
        return response()->json($response);
    }

    /**
     * QQ登录
     * @author AdamTyn
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     * @throws \App\Exceptions\AuthException;
     */
    public function qq(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        if (!($request->has('qq_id'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`qq_id`参数';
        } else {
            try {
                $user = User::LoginByQQ($request->input('qq_id'));
                if(is_bool($user) && !$user){
                    $response['msg'] = '当前QQ未绑定手机号';
                    $response['data']['user_id'] = '0';
                    $response['data']['access_token'] = '';
                    $response['data']['expires_in'] = 0;
                    return response()->json($response);
                }
                if(!$user->hx_id || !$user->xg_id){
                    $options['client_id'] = config('easemob.client_id');
                    $options['client_secret'] = config('easemob.client_secret');
                    $options['org_name'] = config('easemob.org_name');
                    $options['app_name'] = config('easemob.app_name');
                    $easemob = new \Easemob($options);
                    $username = $user->mobile . '-' . $user->id;
                    $res = $easemob->getUser($username);
                    if(!isset($res['error']) || !$res['error']){
                        DB::table('users')->where('id', $user->id)->update(['hx_id'=>$username,'xg_id'=>$username]);
                        $easemob->editNickname($username, $user->nickname);
                    } else {
                        $res = $easemob->createUser($username, md5($user->mobile));
                        if(!isset($res['error']) || !$res['error']){
                            $easemob->editNickname($username, $user->nickname);
                            DB::table('users')->where('id', $user->id)->update(['hx_id'=>$username,'xg_id'=>$username]);
                        }
                    }
                    $present = 1000;
                    Wallet::firstOrCreate(['user_id'=>$user->id]);
                    Wallet::addUserTransaction([
                        'user_id' => $user->id,
                        'order_id' => '',
                        'money' => $present,
                        'title' => '赠送',
                        'desc' => '平台赠送',
                        'type' => 0,
                        'status' => 1,
                        'created_at' => TIME
                    ]);
                }
                if (!($token = Auth::login($user))) {
                    $response['code'] = '5002';
                    $response['msg'] = '无法响应请求，服务端异常';
                } else {
                    $response['data']['user_id'] = strval($user->id);
                    $response['data']['access_token'] = $token;
                    $response['data']['expires_in'] = strval(time() + 86400);
                }

            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }
        return response()->json($response);
    }

    /**
     * 用户登出
     * @author AdamTyn
     * @return \Illuminate\Http\Response;
     */
    public function logout()
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        Auth::invalidate(true);
        return response()->json($response);
    }

    /**
     * 更新用户Token
     * @author AdamTyn
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function refreshToken()
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        if (!$token = Auth::refresh(true, true)) {
            $response['code'] = '5000';
            $response['msg'] = '系统错误，无法生成令牌';
        } else {
            $response['data']['access_token'] = $token;
            $response['data']['expires_in'] = strval(time() + 86400);
        }
        return response()->json($response);
    }
}