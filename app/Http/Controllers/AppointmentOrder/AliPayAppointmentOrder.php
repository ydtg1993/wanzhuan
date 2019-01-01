<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 上午 11:34
 */

namespace App\Http\Controllers\AppointmentOrder;

use App\Http\Controllers\Controller;
use App\Http\InterFaces\PaymentInterface;
use Yansongda\Pay\Pay;

class AliPayAppointmentOrder extends Controller implements PaymentInterface
{
    private $order_data;

    public function ahead($data)
    {
        $this->order_data = $data;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function pay()
    {
        $id = \DB::table('yuewan_orders_temp')->insertGetId($this->order_data);
        if (!$id) {
            throw new \Exception('系统繁忙，请稍后重试');
        }

        $alipayConfig = [
            'app_id' => config('pay.alipay.app_id'),
            'notify_url' => config('pay.alipay.order_notify_url'),
            'return_url' => config('pay.alipay.return_url'),
            'ali_public_key' => config('pay.alipay.ali_public_key'),
            'private_key' => config('pay.alipay.private_key')
        ];
        
        if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
            $alipayConfig = [
                'app_id' => config('pay.alipay.app_id'),
                'notify_url' => config('pay.alipay.order_notify_url_online'),
                'return_url' => config('pay.alipay.return_url_online'),
                'ali_public_key' => config('pay.alipay.ali_public_key'),
                'private_key' => config('pay.alipay.private_key')
            ];
        }

        $order = [
            'out_trade_no' => $this->order_data['order_id'],
            'total_amount' => number_format($this->order_data['pay_sum'] / 100,2),
            'subject' => '订单支付',
        ];
        $rechargeJson = Pay::alipay($alipayConfig)->app($order);

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