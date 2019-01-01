<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yansongda\Pay\Pay;
use Illuminate\Database\QueryException;

class WalletController extends Controller
{
    /**
     * 查看用户钱包
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function showWallet(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        try {
            $response['data'] = Wallet::getWallet($request->input('user_id'));
            $response['withdraw_cash'] = 0;//允许提现
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response,200,array('Access-Control-Allow-Origin' => '*'));
    }

    /**
     * 用户交易记录
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithPaginate;
     * @middleware \App\Http\Middleware\WithType;
     *
     * @author AdamTyn
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function showContract(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        if (!($request->has('type'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`type`参数';
        } else {
            try {
                $response['data'] = Wallet::getContract($request->only('user_id', 'type', 'paginate'));
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }

        }

        return response()->json($response,200,array('Access-Control-Allow-Origin' => '*'));
    }

    /**
     * 充值钱包接口
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithPayNumber;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function recharge(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        if (!($request->has('money'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`money`参数';
        } else {
            $money = (int)$request->input(['money']);
            if ($money < 1) {
                $response['code'] = '4000';
                $response['msg'] = '请正确输入金额';
                return response()->json($response);
            }
            try {
                $recharge = Wallet::recharge($request->only('user_id', 'pay_type', 'money'));
                if($request->input('pay_type') == 2){
                    $wechatConfig = [
                        'appid'  => config('pay.wechat.appid'),
                        'mch_id' => config('pay.wechat.mch_id'),
                        'key'    => config('pay.wechat.key'),
                        'notify_url' => config('pay.wechat.notify_url')
                    ];
                    if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
                        $wechatConfig = [
                            'appid'  => config('pay.wechat.appid'),
                            'mch_id' => config('pay.wechat.mch_id'),
                            'key'    => config('pay.wechat.key'),
                            'notify_url' => config('pay.wechat.notify_url_online')
                        ];
                    }
                    $order = [
                        'out_trade_no' => $recharge['order_id'],
                        'body' => '余额充值',
                        'total_fee' => $recharge['money']
                    ];
                    $rechargeJson = Pay::wechat($wechatConfig)->app($order);
                    $response['data']['recharge'] = $rechargeJson->getContent();
                    $response['data']['order_id'] = $recharge['order_id'];
;               }
                if($request->input('pay_type') == 1){
                    $alipayConfig = [
                        'app_id' => config('pay.alipay.app_id'),
                        'notify_url' => config('pay.alipay.notify_url'),
                        'return_url' => config('pay.alipay.return_url'),
                        'ali_public_key' => config('pay.alipay.ali_public_key'),
                        'private_key' => config('pay.alipay.private_key')
                    ];
                    if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
                        $alipayConfig = [
                            'app_id' => config('pay.alipay.app_id'),
                            'notify_url' => config('pay.alipay.notify_url_online'),
                            'return_url' => config('pay.alipay.return_url_online'),
                            'ali_public_key' => config('pay.alipay.ali_public_key'),
                            'private_key' => config('pay.alipay.private_key')
                        ];
                    }
                    $order = [
                        'out_trade_no' => $recharge['order_id'],
                        'total_amount' => sprintf("%.2f",$recharge['money']/100),
                        'subject'      => '余额充值',
                    ];
                    info($order);

                    $rechargeJson =  Pay::alipay($alipayConfig)->app($order);
                    ;

                    $response['data']['recharge'] = $rechargeJson->getContent();
                    $response['data']['order_id'] = $recharge['order_id'];
                }
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }

        return response()->json($response,200,array('Access-Control-Allow-Origin' => '*'));
    }

    /**
     * 用户交保证金
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithPayNumber;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function bond(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        if (!($request->has('money'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`money`参数';
        } else {
            //$money = (int)$request->input(['money']);
            $money = 1;
            if ($money < 1) {
                $response['code'] = '4000';
                $response['msg'] = '请正确输入金额';
                return response()->json($response);
            }
            try {
                //$recharge
                $bond = Wallet::bond($request->only('user_id', 'pay_type', 'money'));
                if($request->input('pay_type') == 3){
                    $money = (int)$request->input('money');
                    $WalletInfo = Wallet::getWallet($request->input('user_id'));
                    if($WalletInfo['cash'] < $money){
                        $response['code'] = '4000';
                        $response['msg']  = '余额不足';
                        return response()->json($response);
                    }
                    Wallet::bondWallet($bond);
                    $response['data']['bond'] = '';
                }
                if($request->input('pay_type') == 2){
                    $wechatConfig = [
                        'appid'  => config('pay.wechat.appid'),
                        'mch_id' => config('pay.wechat.mch_id'),
                        'key'    => config('pay.wechat.key'),
                        'notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/wechatBondNotify'
                    ];
                    if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
                        $wechatConfig = [
                            'appid'  => config('pay.wechat.appid'),
                            'mch_id' => config('pay.wechat.mch_id'),
                            'key'    => config('pay.wechat.key'),
                            'notify_url' => 'http://api.wanzhuanhuyu.cn/v1/payment/wechatBondNotify'
                        ];
                    }
                    $order = [
                        'out_trade_no' => $bond['order_id'],
                        'body' => '缴纳保证金',
                        'total_fee' => $bond['money']
                    ];
                    $bondJson = Pay::wechat($wechatConfig)->app($order);
                    $response['data']['bond'] = $bondJson->getContent();
                    $response['data']['order_id'] = $bond['order_id'];
                }
                if($request->input('pay_type') == 1){
                    $alipayConfig = [
                        'app_id' => config('pay.alipay.app_id'),
                        'notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/alipayBondNotify',
                        'return_url' => config('pay.alipay.return_url'),
                        'ali_public_key' => config('pay.alipay.ali_public_key'),
                        'private_key' => config('pay.alipay.private_key')
                    ];
                    if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
                        $alipayConfig = [
                            'app_id' => config('pay.alipay.app_id'),
                            'notify_url' => 'http://api.wanzhuanhuyu.cn/v1/payment/alipayBondNotify',
                            'return_url' => config('pay.alipay.return_url_online'),
                            'ali_public_key' => config('pay.alipay.ali_public_key'),
                            'private_key' => config('pay.alipay.private_key')
                        ];
                    }
                    $order = [
                        'out_trade_no' => $bond['order_id'],
                        'total_amount' => round($bond['money']/100, 2),
                        'subject'      => '缴纳保证金',
                    ];
                    $bondJson =  Pay::alipay($alipayConfig)->app($order);
                    $response['data']['bond'] = $bondJson->getContent();
                    $response['data']['order_id'] = $bond['order_id'];
                }
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }

        return response()->json($response,200,array('Access-Control-Allow-Origin' => '*'));
    }

    /**
     * 用户查询充值订单
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function queryRechargeStatus(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        if (!($request->has('order_id'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`order_id`参数';
        } else {
            try {
                $response['data']['status'] = 2001;
                $response['data']['message'] = '订单未支付';
                $recharge = Wallet::getRechargeInfo($request->input('order_id'));
                if($recharge){
                    if($recharge->status == 1){
                        $response['data']['status'] = 2000;
                        $response['data']['message'] = '订单支付成功';
                    }
                }
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }
        return response()->json($response,200,array('Access-Control-Allow-Origin' => '*'));
    }


    /**
     * 用户查询保证金订单
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function queryBondStatus(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        if (!($request->has('order_id'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`order_id`参数';
        } else {
            try {
                $response['data']['status'] = 2001;
                $response['data']['message'] = '保证金未支付';
                $recharge = Wallet::getBondInfo($request->input('order_id'));
                if($recharge){
                    if($recharge->status == 1){
                        $response['data']['status'] = 2000;
                        $response['data']['message'] = '保证金支付成功';
                    }
                }
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }
        return response()->json($response,200,array('Access-Control-Allow-Origin' => '*'));
    }

    
   


}