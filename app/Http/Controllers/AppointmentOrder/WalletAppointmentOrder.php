<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 上午 11:33
 */

namespace App\Http\Controllers\AppointmentOrder;

use App\Http\Controllers\Controller;
use App\Http\InterFaces\PaymentInterface;
use App\Models\NormalOrder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Wallet;
use App\Models\YueUserList;
require_once __DIR__ . '/../../../Libraries/cmq-sdk/cmq_api.php';

class WalletAppointmentOrder extends Controller implements PaymentInterface
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
        $transaction['desc'] = '匹配消费';
        $transaction['type'] = 1;
        $transaction['status'] = 1;
        $transaction['created_at'] = $_SERVER['REQUEST_TIME'];
        Wallet::addUserTransaction($transaction);
        $this->order_data['status'] = 1;
        $id = \DB::table('yuewan_orders')->insertGetId($this->order_data);
        if ($id) {
            $data = [
                'order_id' => $this->order_data['order_id'],
                'pay_sum' => $this->order_data['pay_sum']
            ];
        } else {
            throw new \Exception('服务器错误，请稍后重试');
        }

        return $data;
    }

    public function behind()
    {
        $user = User::getBasic($this->order_data['user_id']);

        switch ($user->sexy){
            case '男':
                $gender = 1;
                break;
            case '女':
                $gender = 2;
                break;
            default:
                $gender = 0;
                break;
        }
        $data = [
            'user_id' => $this->order_data['user_id'],
            'game_id' => $this->order_data['game_id'],
            'server_id' => $this->order_data['server_id'],
            'order_id' => $this->order_data['order_id'],
            'sexy' => $gender,
            'search_sexy' => $this->order_data['search_sexy']
        ];
        YueUserList::add($data);
        $this->sendCMQ();  
    }

    public function sendCMQ()
    {
        ///////////发送消息到队列///////////
        $secretId = config('cloud.cloud_secret_id');
        $secretKey = config('cloud.cloud_secret_key');
        $endPoint = config('cloud.bj_end_point');
        $queue_name = "matching-yuwan";
        if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
            $queue_name = "matching-yuwan-online";
            $endPoint = config('cloud.cd_end_point');
        }
        $my_account = new \Qcloudcmq\Account($endPoint, $secretId, $secretKey);
        $my_queue = $my_account->get_queue($queue_name);
        $queue_meta = new \Qcloudcmq\QueueMeta();
        $queue_meta->queueName = $queue_name;
        $queue_meta->pollingWaitSeconds = 10;
        $queue_meta->visibilityTimeout = 10;
        $queue_meta->maxMsgSize = 1024;
        $queue_meta->msgRetentionSeconds = 3600;
        try {
            $cmqData = [
                'user_id' => $this->order_data['user_id'],
                'order_id' => $this->order_data['order_id']
            ];
            $msg = new \Qcloudcmq\Message(json_encode($cmqData));
            $my_queue->send_message($msg);
        } catch (\Exception $e) {
            throw new \Exception('服务器消息推送错误，请稍后重试');
        }
    }
}