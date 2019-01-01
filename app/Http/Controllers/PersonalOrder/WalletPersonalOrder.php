<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 上午 11:33
 */

namespace App\Http\Controllers\PersonalOrder;

use App\Http\Controllers\Controller;
use App\Http\InterFaces\PaymentInterface;
use App\Models\MasterGameOrder;
use App\Models\NormalOrder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Wallet;

require_once __DIR__ . '/../../../Libraries/cmq-sdk/cmq_api.php';

class WalletPersonalOrder extends Controller implements PaymentInterface
{
    private $order_data;

    /**
     * @param $data
     * @throws \Exception
     */
    public function ahead($data)
    {
        $this->order_data = $data;
        if ($this->order_data['pay_sum'] > 0) {
            $wallet = Wallet::getWallet($this->order_data['user_id']);
            if ($wallet['cash'] < $this->order_data['pay_sum']) {
                throw new \Exception('钱包余额不足');
            }
        }
    }

    /**
     * @return array
     * @throws \App\Exceptions\TicketException
     * @throws \App\Exceptions\UpdateException
     */
    public function pay()
    {
        if ($this->order_data['ticket_id'] > 0) {
            Ticket::useTicket(['ticket_id' => $this->order_data['ticket_id']]);
        }

        $transaction = [];
        $transaction['user_id'] = $this->order_data['user_id'];
        $transaction['order_id'] = $this->order_data['order_id'];
        $transaction['money'] = 0 - $this->order_data['pay_sum'];
        $transaction['title'] = '消费';
        $transaction['desc'] = '带练消费';
        $transaction['type'] = 1;
        $transaction['status'] = 1;
        $transaction['created_at'] = $_SERVER['REQUEST_TIME'];
        Wallet::addUserTransaction($transaction);

        $this->order_data['status'] = 1;
        $id = NormalOrder::saveOrder($this->order_data);
        if ($id) {
            $data = [
                'order_id' => $this->order_data['order_id'],
                'pay_sum' => $this->order_data['pay_sum']
            ];
        } else {
            throw new \Exception('服务器错误，请稍后重试');
        }

        $user_info = User::getBasic($this->order_data['user_id']);
        $normal_order = NormalOrder::getOrderById($this->order_data['order_id']);
        $order_info = json_encode([
            'nickname' => $user_info->nickname,
            'sexy' => $user_info->sexy,
            'avatar' => $user_info->avatar,
            'system' => $user_info->system,
            'xg_id' => $user_info->xg_id,
            'game_id' => $normal_order->game_id,
            'master_type' => $normal_order->master_type,
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
            'order_id' => $this->order_data['order_id'],
            'is_exclusive' => 1,
        ], JSON_UNESCAPED_UNICODE);
        MasterGameOrder::add([
           'master_id'=>$this->order_data['master_user_id'],
           'order_id'=>$this->order_data['order_id'],
           'order_status' => 0,
           'order_info' => $order_info,
           'is_exclusive' => 1
        ]);

        // $matchMaster['id']           = time() + mt_rand(1,100);
        // $matchMaster['master_id']    = $this->order_data['master_user_id'];
        // $matchMaster['order_id']     = $this->order_data['order_id'];
        // $matchMaster['order_status'] = 0;
        // $matchMaster['order_info']   = $order_info;
        // $matchMaster['is_exclusive'] = 1;
        // $matchMaster['create_time']  = date('Y-m-d H:i:s');

        // $redisMatchMasterOrderKey  = 'm_o_' . $this->order_data['master_user_id'];
        // $this->redis->sadd($redisMatchMasterOrderKey, serialize($matchMaster));
        return $data;
    }

    public function behind()
    {
        
    }
}