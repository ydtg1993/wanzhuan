<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\PlayOrder;
use Yansongda\Pay\Pay;
use App\Models\NormalOrder;
use Illuminate\Support\Facades\DB;
require_once __DIR__.'/../../Libraries/cmq-sdk/cmq_api.php';


class PaymentController extends Controller
{
    protected $config = [];

    public function wechatOrder(Request $request)
    {
        $wechatConfig = [
            'appid'  => config('pay.wechat.appid'),
            'mch_id' => config('pay.wechat.mch_id'),
            'key'    => config('pay.wechat.key'),
            'notify_url' => config('pay.wechat.order_notify_url')
        ];
        if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
            $wechatConfig = [
                'appid'  => config('pay.wechat.appid'),
                'mch_id' => config('pay.wechat.mch_id'),
                'key'    => config('pay.wechat.key'),
                'notify_url' => config('pay.wechat.order_notify_url_online')
            ];
        }
        $wechat = Pay::wechat($wechatConfig);
        try{
            $data = $wechat->verify();
            (new LogController())->addLog($data);
            if ($data->return_code == 'SUCCESS') {
                //取首字母转大写
                $first = strtoupper(substr($data->out_trade_no,0,1));
                if($first == 'W'){
                    $order = DB::table('normal_orders_temp')->where('order_id', $data->out_trade_no)->first();
                    if ($order->status == 1) {
                        return $wechat->success()->send();
                    }
                    $this->dealOrder($data->out_trade_no);
                }
                if($first == 'P'){
                    $order = DB::table('yuewan_orders_temp')->where('order_id', $data->out_trade_no)->first();
                    if ($order->status == 1) {
                        return $wechat->success()->send();
                    }
                    $this->dealYuewanOrder($data->out_trade_no);
                }
                return $wechat->success()->send();
            }
        } catch (Exception $e) {
            Log::error('recharge: ' . json_encode($_POST));
            Log::error('wechat verify', $e->getMessage());
        }
    }

    public function alipayOrder(Request $request)
    {
        $alipayConfig = [
            'app_id' => config('pay.alipay.app_id'),
            'notify_url' => config('pay.alipay.order_notify_url'),
            'return_url' => '',
            'ali_public_key' => config('pay.alipay.ali_public_key'),
            'private_key' => config('pay.alipay.private_key')
        ];

        if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
            $alipayConfig = [
                'app_id' => config('pay.alipay.app_id'),
                'notify_url' => config('pay.alipay.order_notify_url_online'),
                'return_url' => '',
                'ali_public_key' => config('pay.alipay.ali_public_key'),
                'private_key' => config('pay.alipay.private_key')
            ];
        }
        $alipay = Pay::alipay($alipayConfig);
        try{
            $data = $alipay->verify();
            if (($data->trade_status == 'TRADE_FINISHED') OR ($data->trade_status == 'TRADE_SUCCESS')) {
                //取首字母转大写
                $first = strtoupper(substr($data->out_trade_no,0,1));
                if($first == 'W'){
                    $order = DB::table('normal_orders_temp')->where('order_id', $data->out_trade_no)->first();
                    if ($order->status == 1) {
                        return $alipay->success()->send();
                    }
                        $this->dealOrder($data->out_trade_no);
                    }
                if($first == 'P'){
                    $order = DB::table('yuewan_orders_temp')->where('order_id', $data->out_trade_no)->first();
                    if ($order->status == 1) {
                        return $alipay->success()->send();
                    }
                    $this->dealYuewanOrder($data->out_trade_no);
                }
                return $alipay->success()->send();
            }
        } catch (Exception $e) {
            Log::error('game pay: ' . json_encode($_POST));
            Log::error('alipay verify', $e->getMessage());
        }
    }

    private function dealOrder($order_id)
    {
        $order = PlayOrder::where('order_id', $order_id)->first();
        $now = $_SERVER['REQUEST_TIME'];
        PlayOrder::where('order_id', $order->order_id)->update(['status' => 1,'pay_at' => $now]);
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
        try
        {
            $cmqData['user_id'] = $orderData['user_id'];
            $cmqData['master_user_id'] = $orderData['master_user_id'];
            $cmqData['order_id'] = $orderData['order_id'];
            $msg = new \Qcloudcmq\Message(json_encode($cmqData));
            $re_msg = $my_queue->send_message($msg);                                               
        }
        catch (CMQExceptionBase $e)
        {
                                             
        }
        ///////////发送消息到队列///////////
    }

    private function dealYuewanOrder($order_id)
    {
        $order = DB::table('yuewan_orders_temp')->where('order_id', $order_id)->first();
        $now = $_SERVER['REQUEST_TIME'];
        DB::table('yuewan_orders_temp')->where('order_id', $order->order_id)->update(['status' => 1,'pay_at' => $now]);
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
            'updated_at' => $now,
        ];
        DB::table('yuewan_orders')->insert($orderData);

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
        try
        {
            $cmqData['user_id'] = $orderData['user_id'];
            $cmqData['order_id'] = $orderData['order_id'];
            $msg = new \Qcloudcmq\Message(json_encode($cmqData));
            $re_msg = $my_queue->send_message($msg);                                               
        }
        catch (CMQExceptionBase $e)
        {
                                             
        }
        ///////////发送消息到队列///////////
    }

    public function alipayRechargeOrder()
    {
        $alipayConfig = [
            'app_id' => config('pay.alipay.app_id'),
            'notify_url' => config('pay.alipay.notify_url'),
            'return_url' => '',
            'ali_public_key' => config('pay.alipay.ali_public_key'),
            'private_key' => config('pay.alipay.private_key')
        ];

        if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
            $alipayConfig = [
                'app_id' => config('pay.alipay.app_id'),
                'notify_url' => config('pay.alipay.notify_url_online'),
                'return_url' => '',
                'ali_public_key' => config('pay.alipay.ali_public_key'),
                'private_key' => config('pay.alipay.private_key')
            ];
        }

        $alipay = Pay::alipay($alipayConfig);
        try{
            $data = $alipay->verify();
            if (($data->trade_status == 'TRADE_FINISHED') OR ($data->trade_status == 'TRADE_SUCCESS')) {
                $order = DB::table('recharge_orders')->where('order_id', $data->out_trade_no)->first();
                if ($order->status == 1) {
                    return $alipay->success()->send();
                }
                $now = time();
                $userTransactionSql = "insert into user_transaction(user_id,order_id,money,`title`,`desc`,`type`,status,created_at)values ({$order->user_id},'{$order->order_id}',{$order->money},'余额充值','支付宝余额充值',3,1,{$now})";
                DB::insert($userTransactionSql);

                $updateRechargeSql = "UPDATE recharge_orders SET status=1, updated_at = {$now} WHERE order_id = '{$order->order_id}'";
                DB::update($updateRechargeSql);

                $updateWalletSql = "UPDATE wallets SET cash = cash + {$order->money} WHERE user_id = {$order->user_id}";
                DB::update($updateWalletSql);

                return $alipay->success()->send();
            }
        } catch (Exception $e) {
            Log::error('recharge: ' . json_encode($_POST));
            Log::error('alipay verify', $e->getMessage());
        }
    }

    public function wechatRechargeOrder()
    {
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
        $wechat = Pay::wechat($wechatConfig);
        try{
            $data = $wechat->verify();
            if ($data->return_code == 'SUCCESS') {
                $order = DB::table('recharge_orders')->where('order_id', $data->out_trade_no)->first();
                if ($order->status == 1) {
                    return $wechat->success()->send();
                }
                $now = time();
                $userTransactionSql = "insert into user_transaction(user_id,order_id,money,`title`,`desc`,`type`,status,created_at)values ({$order->user_id},'{$order->order_id}',{$order->money},'余额充值','微信余额充值',3,1,{$now})";
                DB::insert($userTransactionSql);

                $updateRechargeSql = "UPDATE recharge_orders SET status=1, updated_at = {$now} WHERE order_id = '{$order->order_id}'";
                DB::update($updateRechargeSql);

                $updateWalletSql = "UPDATE wallets SET cash = cash + {$order->money} WHERE user_id = {$order->user_id}";
                DB::update($updateWalletSql);

                return $wechat->success()->send();
            }
        } catch (Exception $e) {
            Log::error('recharge: ' . json_encode($_POST));
            Log::error('wechat verify', $e->getMessage());
        }
    }

    public function alipayBondOrder()
    {
        $alipayConfig = [
            'app_id' => config('pay.alipay.app_id'),
            'notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/alipayBondNotify',
            'return_url' => '',
            'ali_public_key' => config('pay.alipay.ali_public_key'),
            'private_key' => config('pay.alipay.private_key')
        ];
        if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn'){
            $alipayConfig = [
                'app_id' => config('pay.alipay.app_id'),
                'notify_url' => 'http://api.wanzhuanhuyu.cn/v1/payment/alipayBondNotify',
                'return_url' => '',
                'ali_public_key' => config('pay.alipay.ali_public_key'),
                'private_key' => config('pay.alipay.private_key')
            ];
        }
        $alipay = Pay::alipay($alipayConfig);
        try{
            $data = $alipay->verify();
            if (($data->trade_status == 'TRADE_FINISHED') OR ($data->trade_status == 'TRADE_SUCCESS')) {
                $order = DB::table('bond_orders')->where('order_id', $data->out_trade_no)->first();
                if ($order->status == 1) {
                    return $alipay->success()->send();
                }
                $money = $order->money * -1;
                $now = time();
                $userTransactionSql = "insert into user_transaction(user_id,order_id,money,`title`,`desc`,`type`,status,created_at)values ({$order->user_id},'{$order->order_id}',{$money},'缴纳保证金','支付宝缴纳保证金',5,1,{$now})";
                DB::insert($userTransactionSql);

                $updateRechargeSql = "UPDATE bond_orders SET status=1, updated_at = {$now} WHERE order_id = '{$order->order_id}'";
                DB::update($updateRechargeSql);

                $user = DB::table('users')->where('id', $order->user_id)->first();
                $authorizesInfo = DB::table('authorizes')->where('user_id', $order->user_id)->where('status', 2)->orderBy('id', 'desc')->first();
                $skillData['master_user_id'] = $authorizesInfo->user_id;
                $skillData['game_id'] = $authorizesInfo->game_id;
                $skillData['game_name'] = $authorizesInfo->game_name;
                $skillData['server_id'] = $authorizesInfo->server_id;
                $skillData['game_server'] = $authorizesInfo->game_server;
                $skillData['level_id'] = $authorizesInfo->level_id;
                $skillData['game_level'] = $authorizesInfo->game_level;
                $skillData['unit'] = '小时';
                $skillData['created_at'] = time();
                $skillData['status'] = 1;

                $level_type = 1;
                if($user->sexy == '男'){
                    $priceInfo = DB::table('game_man_charge')->where('game_id', $authorizesInfo->game_id)->where('level_id', $authorizesInfo->level_id)->first();
                    if($priceInfo){
                        $skillData['unit'] = $priceInfo->unit;
                        if($user->user_level == 1){
                            $level_type = 1;
                            $skillData['price'] = $priceInfo->normal_price;
                        }
                        if($user->user_level == 2){
                            $level_type = 2;
                            $skillData['price'] = $priceInfo->better_price;
                        }
                        if($user->user_level == 3){
                            $level_type = 3;
                            $skillData['price'] = $priceInfo->super_price;
                        }
                    }
                }
                if($user->sexy == '女'){
                    $priceInfo = DB::table('game_woman_charge')->where('game_id', $authorizesInfo->game_id)->where('server_id', $authorizesInfo->server_id)->first();
                    if($priceInfo){
                        $level_type = 1;
                        $skillData['unit'] = $priceInfo->unit;
                        $skillData['price'] = $priceInfo->normal_price;
                    }
                }
                DB::table('skills')->insert($skillData);
                
                $updateBondSql = "UPDATE authorizes SET status=4 WHERE user_id = {$order->user_id} and status=2";
                DB::update($updateBondSql);

                $updateMasterSql = "UPDATE users SET isMaster=1 WHERE id = {$order->user_id}";
                DB::update($updateMasterSql);

                $master = DB::table('masters')->where('user_id', $user->id)->first();
                if(!$master){
                    $masterTransactionSql = "insert into masters(user_id,sex,order_count,arg_score,level_type,status)values({$user->id},'{$user->sexy}',0,0,{$level_type},1)";
                    DB::insert($masterTransactionSql);
                }
                
                return $alipay->success()->send();
            }
        } catch (Exception $e) {
            Log::error('bond: ' . json_encode($_POST));
            Log::error('alipay verify', $e->getMessage());
        }
    }

    public function wechatBondOrder()
    {
        $wechatConfig = [
            'appid'  => config('pay.wechat.appid'),
            'mch_id' => config('pay.wechat.mch_id'),
            'key'    => config('pay.wechat.key'),
            'notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/wechatBondNotify'
        ];
        if($_SERVER['SERVER_NAME'] == 'api.wanzhuanhuyu.cn')
        {
            $wechatConfig = [
                'appid'  => config('pay.wechat.appid'),
                'mch_id' => config('pay.wechat.mch_id'),
                'key'    => config('pay.wechat.key'),
                'notify_url' => 'http://api.wanzhuanhuyu.cn/v1/payment/wechatBondNotify'
            ];
        }
        $wechat = Pay::wechat($wechatConfig);
        try{
            $data = $wechat->verify();
            if ($data->return_code == 'SUCCESS') {
                $order = DB::table('bond_orders')->where('order_id', $data->out_trade_no)->first();
                if ($order->status == 1) {
                    return $wechat->success()->send();
                }
                $money = $order->money * -1;
                $now = time();
                $skillData = [];
                $userTransactionSql = "insert into user_transaction(user_id,order_id,money,`title`,`desc`,`type`,status,created_at)values ({$order->user_id},'{$order->order_id}',{$money},'缴纳保证金','微信缴纳保证金',5,1,{$now})";
                DB::insert($userTransactionSql);

                $updateBondSql = "UPDATE bond_orders SET status=1, updated_at = {$now} WHERE order_id = '{$order->order_id}'";
                DB::update($updateBondSql);

                $user = DB::table('users')->where('id', $order->user_id)->first();
                $authorizesInfo = DB::table('authorizes')->where('user_id', $order->user_id)->where('status', 2)->orderBy('id', 'desc')->first();
                $skillData['master_user_id'] = $authorizesInfo->user_id;
                $skillData['game_id'] = $authorizesInfo->game_id;
                $skillData['game_name'] = $authorizesInfo->game_name;
                $skillData['server_id'] = $authorizesInfo->server_id;
                $skillData['game_server'] = $authorizesInfo->game_server;
                $skillData['level_id'] = $authorizesInfo->level_id;
                $skillData['game_level'] = $authorizesInfo->game_level;
                $skillData['unit'] = '小时';
                $skillData['created_at'] = time();
                $skillData['status'] = 1;

                $level_type = 1;
                if($user->sexy == '男'){
                    $priceInfo = DB::table('game_man_charge')->where('game_id', $authorizesInfo->game_id)->where('level_id', $authorizesInfo->level_id)->first();
                    if($priceInfo){
                        $skillData['unit'] = $priceInfo->unit;
                        if($user->user_level == 1){
                            $level_type = 1;
                            $skillData['price'] = $priceInfo->normal_price;
                        }
                        if($user->user_level == 2){
                            $level_type = 2;
                            $skillData['price'] = $priceInfo->better_price;
                        }
                        if($user->user_level == 3){
                            $level_type = 3;
                            $skillData['price'] = $priceInfo->super_price;
                        }
                    }
                }
                if($user->sexy == '女'){
                    $priceInfo = DB::table('game_woman_charge')->where('game_id', $authorizesInfo->game_id)->where('server_id', $authorizesInfo->server_id)->first();
                    if($priceInfo){
                        $level_type = 1;
                        $skillData['unit'] = $priceInfo->unit;
                        $skillData['price'] = $priceInfo->normal_price;
                    }
                }
                DB::table('skills')->insert($skillData);

                $updateAuthorSql = "UPDATE authorizes SET status=4 WHERE user_id = '{$order->user_id}' and status=2";
                DB::update($updateAuthorSql);

                $updateMasterSql = "UPDATE users SET isMaster=1 WHERE id = {$order->user_id}";
                DB::update($updateMasterSql);

                $master = DB::table('masters')->where('user_id', $user->id)->first();
                if(!$master){
                    $masterTransactionSql = "insert into masters(user_id,sex,order_count,arg_score,level_type,status)values({$user->id},'{$user->sexy}',0,0,{$level_type},1)";
                    DB::insert($masterTransactionSql);
                }

                return $wechat->success()->send();
            }
        } catch (Exception $e) {
            Log::error('bond: ' . json_encode($_POST));
            Log::error('wechat verify', $e->getMessage());
        }
    }

}