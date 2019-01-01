<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 上午 11:33
 */

namespace App\Http\Controllers\AccompanyOrder;

use App\Http\Controllers\Controller;
use App\Http\InterFaces\PaymentInterface;
use App\Models\NormalOrder;
use App\Models\Ticket;
use App\Models\Wallet;

require_once __DIR__ . '/../../../Libraries/cmq-sdk/cmq_api.php';

class WalletAccompanyOrder extends Controller implements PaymentInterface
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

        return $data;
    }

    public function behind()
    {
        ///////////发送消息到队列///////////
        $secretId = config('cloud.cloud_secret_id');
        $secretKey = config('cloud.cloud_secret_key');
        $endPoint = config('cloud.bj_end_point');

        $queue_name = "matching-master";
        if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
            $queue_name = "matching-master-online";
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
                'master_user_id' => $this->order_data['master_user_id'],
                'order_id' => $this->order_data['order_id']
            ];
            $msg = new \Qcloudcmq\Message(json_encode($cmqData));
            $my_queue->send_message($msg);
        } catch (\Exception $e) {
            throw new \Exception('服务器消息推送错误，请稍后重试');
        }
        
        //redis匹配消息队列
        /*
        $queue_name = "matching-redis-online";
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
                'master_user_id' => $this->order_data['master_user_id'],
                'order_id' => $this->order_data['order_id']
            ];
            $msg = new \Qcloudcmq\Message(json_encode($cmqData));
            $my_queue->send_message($msg);
        } catch (\Exception $e) {
            throw new \Exception('服务器消息推送错误，请稍后重试');
        }
        */
    }
}