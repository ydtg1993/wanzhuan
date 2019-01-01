<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9 0009
 * Time: 上午 11:47
 */

namespace App\Http\Traits;

use App\Models\Authorize;
use App\Models\BaskStream;
use App\Models\BondOrder;
use App\Models\ManCharge;
use App\Models\Master;
use App\Models\NormalOrder;
use App\Models\NormalOrderTmp;
use App\Models\RechargeOrder;
use App\Models\User;
use App\Models\UserTransaction;
use App\Models\Wallet;
use App\Models\WomanCharge;
use App\Models\YueOrderTmp;
use Illuminate\Support\Facades\DB;

require_once PROGECT_ROOT_PATH.'/../public/xinge-api-php/XingeApp.php';

trait DealOrder
{
    public static $MALE = '男';//男
    public static $FA_MALE = '女';//女
    /**
     * 支付实例
     * @var
     */
    public static $PAY_MODEL;

    public static function dealOrder($order_id)
    {
        $initial = strtoupper(substr($order_id, 0, 1));
        switch ($initial) {
            case 'W':
                $order = NormalOrderTmp::getInfoWhere(['order_id' => $order_id]);
                if ($order->status == 1) {
                    throw new \Exception(self::$PAY_MODEL->success()->send());
                }
                self::dealAccompanyOrder($order);
                break;

            case 'p':
                $order = YueOrderTmp::getInfoWhere(['order_id' => $order_id]);
                if ($order->status == 1) {
                    throw new \Exception(self::$PAY_MODEL->success()->send());
                }
                self::dealAppointmentOrder($order);
                break;

            default:
                return;
        }
    }

    public static function dealRechargeOrder($data, $desc)
    {
        $order = RechargeOrder::getInfoWhere(['order_id' => $data->out_trade_no]);
        if ($order->status == 1) {
            throw new \Exception(self::$PAY_MODEL->success()->send());
        }

        RechargeOrder::updateWhere([
            'status' => 1,
            'updated_at' => TIME
        ], ['order_id' => $order->order_id]);

        $transation = [
            'user_id' => $order->user_id,
            'order_id' => $order->order_id,
            'money' => $order->money,
            'title' => '余额充值',
            'desc' => $desc,
            'type' => 3,
            'status' => 1,
            'created_at' => TIME
        ];
        Wallet::addUserTransaction($transation);
    }

    private static function dealAccompanyOrder($order)
    {
        NormalOrderTmp::upInfoWhere([
            'status' => 1,
            'pay_at' => TIME
        ], ['order_id' => $order->order_id]);

        $orderData = [
            'order_id' => $order->order_id,
            'order_type' => $order->order_type,
            'master_type' => $order->master_type,
            'user_id' => $order->user_id,
            'master_user_id' => $order->master_user_id,
            'game_id' => $order->game_id,
            'level_type' => $order->level_type,
            'server_id' => $order->server_id,
            'level_id' => $order->level_id,
            'ticket_id' => $order->ticket_id,
            'game_name' => $order->game_name,
            'server_name' => $order->server_name,
            'level_name' => $order->level_name,
            'unit' => $order->unit,
            'unit_price' => $order->unit_price,
            'game_num' => $order->game_num,
            'room_id' => $order->room_id,
            'status' => 1,
            'pay_at' => $order->pay_at,
            'pay_type' => $order->pay_type,
            'pay_sum' => $order->pay_sum,
            'money_sum' => $order->money_sum,
            'is_exclusive' => $order->is_exclusive,
            'created_at' => $order->created_at,
        ];
        NormalOrder::insert($orderData);

        ///////////发送消息到队列///////////
        $secretId = config('cloud.cloud_secret_id');
        $secretKey = config('cloud.cloud_secret_key');
        $endPoint = config('cloud.bj_end_point');

        $queue_name = "matching-master";
        if ($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn') {
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
            $cmqData['user_id'] = $orderData['user_id'];
            $cmqData['master_user_id'] = $orderData['master_user_id'];
            $cmqData['order_id'] = $orderData['order_id'];
            $msg = new \Qcloudcmq\Message(json_encode($cmqData));
            $re_msg = $my_queue->send_message($msg);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private static function dealAppointmentOrder($order)
    {
        YueOrderTmp::upInfoWhere([
            'status' => 1,
            'pay_at' => TIME
        ], ['order_id' => $order->order_id]);

        $orderData = [
            'order_id' => $order->order_id,
            'order_status' => $order->order_status,
            'search_sexy' => $order->search_sexy,
            'user_id' => $order->user_id,
            'pay_number' => $order->pay_number,
            'game_id' => $order->game_id,
            'server_id' => $order->server_id,
            'game_name' => $order->game_name,
            'server_name' => $order->server_name,
            'ticket_id' => $order->ticket_id,
            'unit_price' => $order->unit_price,
            'status' => 1,
            'back_sum' => $order->back_sum,
            'back_type' => $order->back_type,
            'back_status' => $order->back_status,
            'pay_at' => $order->pay_at,
            'pay_status' => $order->pay_status,
            'pay_type' => $order->pay_type,
            'pay_sum' => $order->pay_sum,
            'money_sum' => $order->money_sum,
            'created_at' => $order->created_at,
            'updated_at' => TIME,
        ];
        \DB::table('yuewan_orders')->insert($orderData);

        ///////////发送消息到队列///////////
        $secretId = config('cloud.cloud_secret_id');
        $secretKey = config('cloud.cloud_secret_key');
        $endPoint = config('cloud.bj_end_point');
        $queue_name = "matching-yuwan";
        if ($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn') {
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
            $cmqData['user_id'] = $orderData['user_id'];
            $cmqData['order_id'] = $orderData['order_id'];
            $msg = new \Qcloudcmq\Message(json_encode($cmqData));
            $re_msg = $my_queue->send_message($msg);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function dealBondOrder($data)
    {
        $order = BondOrder::getInfoWhere(['order_id' => $data->out_trade_no]);
        if ($order->status == 1) {
            throw new \Exception(self::$PAY_MODEL->success()->send());
        }
        $money = $order->money * -1;

        UserTransaction::add([
            'user_id' => $order->user_id,
            'order_id' => $order->order_id,
            'money' => $money,
            'title' => '缴纳保证金',
            'desc' => '支付宝缴纳保证金',
            'type' => 5,
            'status' => 1,
            'created_at' => TIME
        ]);

        BondOrder::upInfoWhere([
            'status' => 1,
            'updated_at' => TIME
        ], ['order_id' => $order->order_id]);

        $user = User::getBasic($order->user_id);
        $authorizesInfo = Authorize::getInfoWhere([
            'user_id' => $order->user_id,
            'status' => 2
        ]);

        DB::table('skills')->insert([
            'master_user_id' => $authorizesInfo->user_id,
            'game_id' => $authorizesInfo->game_id,
            'game_name' => $authorizesInfo->game_name,
            'server_id' => $authorizesInfo->server_id,
            'game_server' => $authorizesInfo->game_server,
            'level_id' => $authorizesInfo->level_id,
            'game_level' => $authorizesInfo->game_level,
            'unit' => '',
            'created_at' => TIME,
            'status' => 1,
            'price' => 0
        ]);

        Authorize::upInfoWhere(['status' => 4],
            ['user_id' => $order->user_id, 'status' => 2]);

        User::upInfoWhere(['isMaster' => 1], ['id' => $order->user_id]);

        $master = Master::masterInfo($user->id);
        if (!$master) {
            Master::addMaster([
                'user_id' => $user->id,
                'sex' => $user->sexy,
                'order_count' => 0,
                'arg_score' => 0,
                'level_type' => $user->user_level,
                'status' => 1
            ]);
        }
    }

    public static function dealBaskOrder($data)
    {
        $order = BaskStream::getInfoWhere(['order_id' => $data->out_trade_no,'status'=>0]);
        if ($order->status == 1) {
            throw new \Exception(self::$PAY_MODEL->success()->send());
        }

        BaskStream::upInfoWhere(['status' => 1], ['order_id' => $order->order_id]);

        //被打赏人加钱
        Wallet::addUserTransaction([
            'user_id' => $order->bask_user_id,
            'order_id' => $order->order_id,
            'money' => $order->pay_sum * $order->proportions,
            'title' => '收入',
            'desc' => '打赏收入',
            'type' => 7,
            'status' => 1,
            'created_at' => TIME
        ]);

        self::sendHxMessage($order);
    }

    protected static function sendHxMessage($order)
    {
        $user_info = User::getBasic($order->user_id);
        $bask_user_info = User::getBasic($order->bask_user_id);
        $conf = config('xinge');
        if($bask_user_info->system == 'andriod'){
            \xinge\XingeApp::PushAccountAndroid($conf['andorid_access_id'], $conf['andorid_secret_key'], "有玩家打赏", "有玩家打赏", $bask_user_info->xg_id);
        }else{
            \xinge\XingeApp::PushAccountIos($conf['ios_access_id'], $conf['ios_secret_key'], "有玩家打赏", $bask_user_info->xg_id, \xinge\XingeApp::IOSENV_DEV);
        }

        //环信推送
        $options['client_id'] = config('easemob.client_id');
        $options['client_secret'] = config('easemob.client_secret');
        $options['org_name'] = config('easemob.org_name');
        $options['app_name'] = config('easemob.app_name');
        $easemob = new \Easemob($options);

        $target_type = 'users';
        $target = array($bask_user_info->hx_id);
        $from = 'system';

        $pay_sum = $order->pay_sum / 10;
        $flatform_divide = ($order->pay_sum * (1 - $order->proportions)) / 10;
        $content = <<<EOF
玩家{$user_info->nickname}打赏了你:{$pay_sum}玩币|平台分成:{$flatform_divide}玩币
EOF;

        $ext['title'] = '打赏';
        $ext['type'] = '3';
        $ext['orderInfo'] = json_encode((array)$order);
        $ext['redirectInfo'] = 'bask';
        $ext['nickname'] = '系统消息';
        $ext['avatar'] = 'http://image.wanzhuanhuyu.cn/game-icon/system.png';
        $easemob->sendText($from, $target_type, $target, $content, $ext);
    }

}