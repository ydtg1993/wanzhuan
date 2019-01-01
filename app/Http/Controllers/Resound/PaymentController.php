<?php

namespace App\Http\Controllers\Resound;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LogController;
use App\Http\Traits\DealOrder;
use App\Libraries\helper\Helper;
use App\Models\AppleStream;
use App\Models\AppleVirtualGoods;
use App\Models\BaskStream;
use App\Models\BondOrder;
use App\Models\RechargeOrder;
use App\Models\ResondErro;
use App\Models\User;
use App\Models\UserTransaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yansongda\Pay\Pay;


require_once __DIR__ . '/../../../Libraries/cmq-sdk/cmq_api.php';


class PaymentController extends Controller
{
    use DealOrder;

    public function wechatOrder()
    {
        $config = [
            'appid' => config('pay.wechat.appid'),
            'mch_id' => config('pay.wechat.mch_id'),
            'key' => config('pay.wechat.key'),
            'notify_url' => config('pay.wechat.order_notify_url')
        ];
        if ($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn') {
            $config['notify_url'] = config('pay.wechat.order_notify_url_online');
        }

        self::$PAY_MODEL = Pay::wechat($config);
        try {
            DB::beginTransaction();
            $data = self::$PAY_MODEL->verify();
            if ($data->return_code == 'SUCCESS') {
                DealOrder::dealOrder($data->out_trade_no);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('recharge: ' . json_encode($_POST));
            Log::error('wechat verify', $e->getMessage());
        }

        DB::commit();
        return self::$PAY_MODEL->success()->send();
    }

    public function alipayOrder()
    {
        $config = [
            'app_id' => config('pay.alipay.app_id'),
            'notify_url' => config('pay.alipay.order_notify_url'),
            'return_url' => '',
            'ali_public_key' => config('pay.alipay.ali_public_key'),
            'private_key' => config('pay.alipay.private_key')
        ];
        if ($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn') {
            $config['notify_url'] = config('pay.alipay.order_notify_url_online');
        }

        self::$PAY_MODEL = Pay::alipay($config);
        try {
            DB::beginTransaction();
            $data = self::$PAY_MODEL->verify();
            if (($data->trade_status == 'TRADE_FINISHED') OR ($data->trade_status == 'TRADE_SUCCESS')) {
                DealOrder::dealOrder($data->out_trade_no);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('game pay: ' . json_encode($_POST));
            Log::error('alipay verify', $e->getMessage());
        }

        DB::commit();
        return self::$PAY_MODEL->success()->send();
    }

    public function alipayRechargeOrder()
    {
        $config = [
            'app_id' => config('pay.alipay.app_id'),
            'notify_url' => config('pay.alipay.notify_url'),
            'return_url' => '',
            'ali_public_key' => config('pay.alipay.ali_public_key'),
            'private_key' => config('pay.alipay.private_key')
        ];
        if ($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn') {
            $config['notify_url'] = config('pay.alipay.notify_url_online');
        }

        self::$PAY_MODEL = Pay::alipay($config);
        try {
            DB::beginTransaction();
            $data = self::$PAY_MODEL->verify();
            if (($data->trade_status == 'TRADE_FINISHED') OR ($data->trade_status == 'TRADE_SUCCESS')) {
                DealOrder::dealRechargeOrder($data, '支付宝余额充值');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('recharge: ' . json_encode($_POST));
            Log::error('alipay verify', $e->getMessage());
        }

        DB::commit();
        return self::$PAY_MODEL->success()->send();
    }

    public function wechatRechargeOrder()
    {
        $config = [
            'appid' => config('pay.wechat.appid'),
            'mch_id' => config('pay.wechat.mch_id'),
            'key' => config('pay.wechat.key'),
            'notify_url' => config('pay.wechat.notify_url')
        ];
        if ($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn') {
            $config['notify_url'] = config('pay.wechat.notify_url_online');
        }

        self::$PAY_MODEL = Pay::wechat($config);
        try {
            DB::beginTransaction();
            $data = self::$PAY_MODEL->verify();
            if ($data->return_code == 'SUCCESS') {
                $order = RechargeOrder::getInfoWhere(['order_id' => $data->out_trade_no]);
                if ($order->status == 1) {
                    return self::$PAY_MODEL->success()->send();
                }
                DealOrder::dealRechargeOrder($data, '微信余额充值');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('recharge: ' . json_encode($_POST));
            Log::error('wechat verify', $e->getMessage());
        }

        DB::commit();
        return self::$PAY_MODEL->success()->send();
    }

    public function alipayBondOrder()
    {
        $config = [
            'app_id' => config('pay.alipay.app_id'),
            'notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/alipayBondNotify',
            'return_url' => '',
            'ali_public_key' => config('pay.alipay.ali_public_key'),
            'private_key' => config('pay.alipay.private_key')
        ];
        if ($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn') {
            $config['notify_url'] = 'http://api.wanzhuanhuyu.cn/v1/payment/alipayBondNotify';
        }

        self::$PAY_MODEL = Pay::alipay($config);
        try {
            DB::beginTransaction();
            $data = self::$PAY_MODEL->verify();
            if (($data->trade_status == 'TRADE_FINISHED') OR ($data->trade_status == 'TRADE_SUCCESS')) {
                DealOrder::dealBondOrder($data);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('bond: ' . json_encode($_POST));
            Log::error('alipay verify', $e->getMessage());
        }

        DB::commit();
        return self::$PAY_MODEL->success()->send();
    }

    public function wechatBondOrder()
    {
        $config = [
            'appid' => config('pay.wechat.appid'),
            'mch_id' => config('pay.wechat.mch_id'),
            'key' => config('pay.wechat.key'),
            'notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/wechatBondNotify'
        ];
        if ($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn') {
            $config['notify_url'] = 'http://api.wanzhuanhuyu.cn/v1/payment/wechatBondNotify';
        }

        self::$PAY_MODEL = Pay::wechat($config);
        try {
            DB::beginTransaction();
            $data = self::$PAY_MODEL->verify();
            if ($data->return_code == 'SUCCESS') {
                DealOrder::dealBondOrder($data);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('bond: ' . json_encode($_POST));
            Log::error('wechat verify', $e->getMessage());
        }

        DB::commit();
        return self::$PAY_MODEL->success()->send();
    }

    public function baskAlipayOrder()
    {
        $config = [
            'app_id' => config('pay.alipay.app_id'),
            'notify_url' => config('pay.alipay.bask_notify_url'),
            'return_url' => '',
            'ali_public_key' => config('pay.alipay.ali_public_key'),
            'private_key' => config('pay.alipay.private_key')
        ];

        if ($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn') {
            $config['notify_url'] = config('pay.alipay.bask_notify_url_online');
        }
        (new LogController())->addLog($_POST);
        try {
            DB::beginTransaction();
            self::$PAY_MODEL = Pay::alipay($config);
            $data = self::$PAY_MODEL->verify();

            if (($data->trade_status == 'TRADE_FINISHED') OR ($data->trade_status == 'TRADE_SUCCESS')) {
                DealOrder::dealBaskOrder($data);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('bask: ' . json_encode($_POST));
            $_POST['mess'] = $e->getMessage();
            (new LogController())->addLog($_POST);
            if (!empty($data)) {
                ResondErro::add($data->out_trade_no, (array)$data, $e->getMessage());
            }
            return;
        }

        DB::commit();
        return self::$PAY_MODEL->success()->send();
    }

    public function baskWechatOrder()
    {
        $config = [
            'appid' => config('pay.wechat.appid'),
            'mch_id' => config('pay.wechat.mch_id'),
            'key' => config('pay.wechat.key'),
            'notify_url' => config('pay.wechat.bask_notify_url')
        ];
        if ($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn') {
            $config['notify_url'] = config('pay.wechat.bask_notify_url_online');
        }
        (new LogController())->addLog($_POST);
        self::$PAY_MODEL = Pay::wechat($config);
        try {
            DB::beginTransaction();
            $data = self::$PAY_MODEL->verify();

            if ($data->return_code == 'SUCCESS') {
                DealOrder::dealBaskOrder($data);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('bask: ' . json_encode($_POST));
            $_POST['mess'] = $e->getMessage();
            (new LogController())->addLog($_POST);
            if (!empty($data)) {
                ResondErro::add($data->out_trade_no, (array)$data, $e->getMessage());
            }
            return;
        }

        DB::commit();
        return self::$PAY_MODEL->success()->send();
    }

    public function applyPay(Request $request)
    {
        if (!$request->has('recipt_data')) {
            return self::$RESPONSE_CODE->code(4000);
        }
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->code(4000);
        }
        try {
            $receipt_data = $request->input('recipt_data');
            $user_id = $request->input('user_id');

            info($receipt_data);

            $url = 'https://buy.itunes.apple.com/verifyReceipt';
            $data = ['receipt-data' => $receipt_data];
            $response = Helper::curlRequest($url, json_encode($data));

            info($response);

            $response = json_decode($response, true);
            if ($response['status'] != 0) {
                return self::$RESPONSE_CODE->Code(5006, $response);
            }

            $info = isset($response['receipt']['in_app']) ? current($response['receipt']['in_app']) : [];
            $stream_data = AppleStream::getInfoWhere(['transaction_id' => $info['transaction_id']]);
            if ($stream_data && $stream_data->status == 1) {
                return self::$RESPONSE_CODE->setMsg('订单已完成')->Code(5005, $response);
            }

            $product_info = AppleVirtualGoods::getInfoWhere(['product_id' => $info['product_id']]);
            $product_info = $product_info->toArray();
            $currency = $product_info['product_value'] * $info['quantity'];
            $money = $product_info['product_price'] * $info['quantity'];

            $apply_stream = [
                'receipt_data' => $receipt_data,
                'user_id' => $user_id,
                'response_info' => json_encode($response),
                'product_info' => json_encode($product_info),
                'currency' => $currency,
                'money' => $money,
                'transaction_id' => $info['transaction_id']
            ];
            $stream_id = AppleStream::add($apply_stream);

            \DB::beginTransaction();
            Wallet::addUserTransaction([
                'user_id' => $user_id,
                'order_id' => $stream_id,
                'money' => $currency,
                'title' => '苹果充值',
                'desc' => '苹果内购充值',
                'type' => 9,
                'status' => 1,
                'created_at' => TIME
            ]);

            AppleStream::upInfoWhere(['status' => 1], ['id' => $stream_id]);

        } catch (\Exception $e) {
            info($e->getMessage());
            \DB::rollBack();
            Log::error('applePay: ' . json_encode($request->input()));
            (new LogController())->addLog(['message'=>$e->getMessage(),$request->input()]);
            return self::$RESPONSE_CODE->Code(5002, (object)[]);
        }

        \DB::commit();
        return self::$RESPONSE_CODE->Code(0, $response);
    }

}