<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 上午 11:34
 */

namespace App\Http\Controllers\PersonalOrder;

use App\Http\Controllers\Controller;
use App\Http\InterFaces\PaymentInterface;
use App\Models\MasterGameOrder;
use App\Models\PlayOrder;
use App\Models\User;
use Yansongda\Pay\Pay;

class WechatPayPersonalOrder extends Controller implements PaymentInterface
{
    private $order_data;

    public function ahead($data)
    {
        $this->order_data = $data;
    }

    public function pay()
    {
        $id = PlayOrder::insertGetId($this->order_data);
        if (!$id) {
            throw new \Exception('系统繁忙，请稍后重试');
        }

        $wechatConfig = [
            'appid' => config('pay.wechat.appid'),
            'mch_id' => config('pay.wechat.mch_id'),
            'key' => config('pay.wechat.key'),
            'notify_url' => config('pay.wechat.order_notify_url')
        ];

        if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
            $wechatConfig = [
                'appid' => config('pay.wechat.appid'),
                'mch_id' => config('pay.wechat.mch_id'),
                'key' => config('pay.wechat.key'),
                'notify_url' => config('pay.wechat.order_notify_url_online')
            ];
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

        $user_info = User::getBasic($this->order_data['user_id']);
        $normal_order = PlayOrder::getOrderById($this->order_data['order_id']);
        $order_info = json_encode([
            'nickname' => $user_info->nickname,
            'sexy' => $user_info->sexy,
            'avatar' => $user_info->avatar,
            'game_id' => $normal_order->game_id,
            'game_name' => $normal_order->game_name,
            'server_id' => $normal_order->server_id,
            'server_name' => $normal_order->server_name,
            'level_id' => $normal_order->level_id,
            'level_name' => $normal_order->level_name,
            'level_type' => $normal_order->level_type,
            'unit' => $normal_order->unit,
            'unit_price' => $normal_order->unit_price,
            'game_num' => $normal_order->game_num,
            'match_num' => $normal_order->match_num,
            'status' => $normal_order->status,
            'created_at' => $normal_order->created_at,
        ], JSON_UNESCAPED_UNICODE);
        MasterGameOrder::add([
            'master_id'=>$this->order_data['master_user_id'],
            'order_id'=>$this->order_data['order_id'],
            'order_status' => 0,
            'order_info' => $order_info,
            'is_exclusive' => 1
        ]);

        return $data;
    }

    public function behind()
    {
        
    }
}