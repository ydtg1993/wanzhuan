<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 上午 11:34
 */

namespace App\Http\Controllers\Bask;

use App\Http\Controllers\Controller;
use App\Http\InterFaces\PaymentInterface;
use App\Models\BaskStream;
use App\Models\PlayOrder;
use App\Models\Wallet;
use Yansongda\Pay\Pay;

class WechatBask extends Controller implements PaymentInterface
{
    private $order_data;

    public function ahead($data)
    {
        $this->order_data = $data;
    }

    public function pay()
    {
        $this->order_data['status'] = 0;
        $id = BaskStream::add($this->order_data);
        if (!$id) {
            throw new \Exception('系统繁忙，请稍后重试');
        }

        $wechatConfig = [
            'appid' => config('pay.wechat.appid'),
            'mch_id' => config('pay.wechat.mch_id'),
            'key' => config('pay.wechat.key'),
            'notify_url' => config('pay.wechat.bask_notify')
        ];
        if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
            $wechatConfig['notify_url'] = config('pay.wechat.bask_notify_url_online');
        }
        
        $order = [
            'out_trade_no' => $this->order_data['order_id'],
            'body' => '订单支付',
            'total_fee' => $this->order_data['pay_sum']
        ];
        $rechargeJson = Pay::wechat($wechatConfig)->app($order);

        $data = [
            'recharge' => $rechargeJson->getContent(),
            'order_id' => $this->order_data['order_id']
        ];

        return $data;
    }

    public function behind()
    {
        
    }
}