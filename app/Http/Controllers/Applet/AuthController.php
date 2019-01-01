<?php

namespace App\Http\Controllers\Applet;


use App\Exceptions\ModelSaveException;
use App\Http\Controllers\LogController;
use App\Libraries\Applet\AppletServer;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * 通过js_code获取openid登录
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function codeLogin(Request $request)
    {
        $this->validate($request, ['js_code' => 'required']);
        $client = new Client();
        $querstData = [
            'appid' => config('applet.APPID'),
            'secret' => config('applet.APPSECRET'),
            'js_code' => $request->input('js_code'),
            'grant_type' => 'authorization_code'

        ];
        $url = 'https://api.weixin.qq.com/sns/jscode2session?' . http_build_query($querstData);

        $res = $client->request('GET', $url);

        if ($res->getStatusCode() !== 200) {
            return self::$RESPONSE_CODE->Code(5006);
        }

        $resData = json_decode((string)$res->getBody(), TRUE);

        if (array_get($resData, 'errcode')) {
            return self::$RESPONSE_CODE->Code(5006, $resData);
        }

        $user = User::where('xcx_id', array_get($resData, 'openid'))->first();
        if (!$user) {
            return self::$RESPONSE_CODE->Code(1001, $resData);
        }
        if (!($token = Auth::login($user))) {
            return self::$RESPONSE_CODE->setMsg('系统错误，无法生成令牌')->Code(5000);
        }
        $data = [
            'user_id' => strval($user->id),
            'access_token' => $token,
            'expires_in' => strval(time() + 86400)
        ];
        return self::$RESPONSE_CODE->Code(0, $data);

    }

    /**
     * 解密微信数据得到手机号登陆
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function decryptLogin(Request $request)
    {
        $rules = [
            'session_key' => 'required|size:24',
            'encrypted_data' => 'required',
            'iv' => 'required|size:24',
            'openid' => 'required',
        ];

        $this->validate($request, $rules);

        $appletServer = new AppletServer();


        $decryptData = $appletServer->dataDecryption($request->only('session_key', 'encrypted_data', 'iv'));
        (new LogController())->resposeLog($decryptData);
        if ($decryptData == false) {
            return self::$RESPONSE_CODE->Code(5006);
        }

        $user = User::where('mobile', $decryptData->purePhoneNumber)->first();
        if (!$user) {
            return self::$RESPONSE_CODE->Code(1001, ['mobile' => $decryptData->purePhoneNumber]);
        }
        //保存小程序OPENID，生成token
        if (!$user->update(['xcx_id' => $request->openid]) || !($token = Auth::login($user))) {
            return self::$RESPONSE_CODE->setMsg('系统错误，无法生成令牌')->Code(5000);
        }

        $data = [
            'user_id' => strval($user->id),
            'access_token' => $token,
            'expires_in' => strval(time() + 86400)
        ];
        return self::$RESPONSE_CODE->Code(0, $data);

    }

    /**
     * 新用户注册
     *
     * @param Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request)
    {
        $rules = [
            'mobile' => 'required|regex:/^(0?1[0-9]\d{9})$/|unique:users',
            'sexy' => 'required',
            'nickname' => 'required|min:1',
            'avatar' => 'required',
            'xcx_id' => 'required',
        ];

        $this->validate($request, $rules);

        try {
            $request->offsetSet('created_at', time());

            $user = User::create($request->all());
            if (!$user) {
                throw new ModelSaveException;
            }
            $options['client_id'] = config('easemob.client_id');
            $options['client_secret'] = config('easemob.client_secret');
            $options['org_name'] = config('easemob.org_name');
            $options['app_name'] = config('easemob.app_name');
            $easemob = new \Easemob($options);

            if (!$user->hx_id || !$user->xg_id) {
                $username = $user->mobile . '-' . $user->id;
                $res = $easemob->getUser($username);
                if (!isset($res['error']) || !$res['error']) {
                    DB::table('users')->where('id', $user->id)->update(['hx_id' => $username, 'xg_id' => $username]);
                    $easemob->editNickname($username, $user->nickname);
                } else {
                    $res = $easemob->createUser($username, md5($user->mobile));
                    if (!isset($res['error']) || !$res['error']) {
                        $easemob->editNickname($username, $user->nickname);
                        DB::table('users')->where('id', $user->id)->update(['hx_id' => $username, 'xg_id' => $username]);
                    }
                }
                $present = 1000;
                Wallet::firstOrCreate(['user_id' => $user->id]);
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
                return self::$RESPONSE_CODE->setMsg('系统错误，无法生成令牌')->Code(5000);
            }
            $returndata = [
                'user_id' => strval($user->id),
                'access_token' => $token,
                'expires_in' => strval(time() + 86400)
            ];

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

            return self::$RESPONSE_CODE->Code(0, $returndata);

        } catch (ModelSaveException $e) {
            return self::$RESPONSE_CODE->Code(5002);
        }
    }
}