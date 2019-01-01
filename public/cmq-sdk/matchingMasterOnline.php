<?php
require_once 'cmq/cmq_api.php';
require_once CMQAPI_ROOT_PATH . '/account.php';
require_once CMQAPI_ROOT_PATH . '/queue.php';
require_once CMQAPI_ROOT_PATH . '/cmq_exception.php';
require_once __DIR__.'/../xinge-api-php/XingeApp.php';
require_once __DIR__.'/log.php';
date_default_timezone_set('PRC');

$logHandler= new CLogFileHandler(__DIR__.'/../logs/'.date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

class Matching
{
    private $secretId;
    private $secretKey;
    private $endPoint;

    public function __construct($secretId, $secretKey, $endPoint)
    {
        $this->secretId = $secretId;
        $this->secretKey = $secretKey;
        $this->endPoint = $endPoint;
    }

    public function run()
    {   
        //信鸽推送
        $andorid_access_id  = 2100300435;
        $andorid_secret_key = '96774184c1d236fe0cfb37744b9c0515';
        
        $ios_access_id      = 2200302271;
        $ios_secret_key = '82477f2c9b7956386583dc094ddef0a1';
        //腾讯cmq
        $queue_name                      = "matching-master-online";
        $my_account                      = new Account($this->endPoint, $this->secretId, $this->secretKey);
        $my_queue                        = $my_account->get_queue($queue_name);
        $queue_meta                      = new QueueMeta();
        $queue_meta->queueName           = $queue_name;
        $queue_meta->pollingWaitSeconds  = 10;
        $queue_meta->visibilityTimeout   = 10;
        $queue_meta->maxMsgSize          = 4096;
        $queue_meta->msgRetentionSeconds = 600;

        while (true) {
            sleep(1);
            try
            {
                $recv_msg = $my_queue->receive_message(3);
                $receveData = json_decode($recv_msg, true);
                $orderData = json_decode($receveData['msgBody'], true);
                $orderInfo = '';
                try{
                    //pdo数据库  
                    $dsn                = 'mysql:dbname=wanzhuan;host=10.0.0.9;port=3306';
                    $username           = 'root';
                    $password           = 'wzkj2018';
                    $pdo                = new PDO($dsn, $username, $password);
                    //如果未接受必要参数则跳出循环并剔除消息
                    if(!isset($orderData['user_id']) || !$orderData['user_id'])
                    {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    if(!isset($orderData['order_id']) || !$orderData['order_id'])
                    {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    if(!isset($orderData['master_user_id']))
                    {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    //判断订单是否支付
                    $selectSql = "select game_id,game_name,server_id,server_name,master_type,level_id,level_name,level_type,unit,unit_price,game_num,match_num,status,is_exclusive,created_at from normal_orders where order_id = '" . $orderData['order_id'] . "'";
                    $stmt = $pdo->query($selectSql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $normalOrder = $stmt->fetch();
                    if(!$normalOrder)
                    {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    if($normalOrder['status'] != 1){
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    if($normalOrder['is_exclusive'] == 1){
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    if($normalOrder['match_num'] >= 3){
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    $created_at = $normalOrder['created_at'];
                    $do_at = $created_at + 4;
                    $run_at = time();
                    if($run_at < $do_at)
                    {
                        continue;
                    }
                    //查询下单人信息
                    $selectSql = "select nickname,sexy,avatar from users where id = " . $orderData['user_id'];
                    $stmt = $pdo->query($selectSql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $userInfo = $stmt->fetch();
                    if(!$userInfo)
                    {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    $orderInfo = array_merge($userInfo, $normalOrder);
                    if($normalOrder['is_exclusive'] == 0){
                        //更新订单匹配次数
                        $updateSql = "update normal_orders set match_num = match_num + 1 where order_id = '" . $orderData['order_id'] . "'";
                        $pdo->query($updateSql);
                        //暴娘单只找女性
                        if($normalOrder['master_type'] == 2){
                            $selectSql = "select a.master_id from master_game_search_range a left join masters b on a.master_id = b.user_id where a.game_id = {$orderInfo['game_id']} and a.server_id = {$orderInfo['server_id']} and a.master_level >= {$orderInfo['level_type']} and b.status = 2 and b.sex = '女'";
                        }else{ 
                            $selectSql = "select a.master_id from master_game_search_range a left join masters b on a.master_id = b.user_id where a.game_id = {$orderInfo['game_id']} and a.server_id = {$orderInfo['server_id']} and a.level_id = {$orderInfo['level_id']} and a.master_level >= {$orderInfo['level_type']} and b.status = 2 and b.sex = '男'";
                        }
                        $stmt = $pdo->query($selectSql);
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $masterList = $stmt->fetchAll();
                        $masterId = $masterOrderId = [];
                        $masterIdString = '';

                        $selectSql = "select id,master_id from master_gameorder_list where order_id = '{$orderData['order_id']}'";
                        $stmt = $pdo->query($selectSql);
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $masterOrderList = $stmt->fetchAll();
                        $masterOrderId = array_column($masterOrderList, 'master_id');
                        foreach ($masterList as $key => $val) {
                            if($val['master_id'] == $orderData['user_id']){
                                continue;
                            }
                            if(!in_array($val['master_id'], $masterOrderId)){
                                $masterId[] = $val['master_id'];
                                $masterIdString .= $val['master_id'] . ',';
                            }
                        }
                        $masterIdString = trim($masterIdString, ',');
                        print_r($masterId);
                        if(count($masterId)){
                            //插入导师
                            $orderInfo = json_encode($orderInfo, JSON_UNESCAPED_UNICODE);
                            $prepareSql = "insert into master_gameorder_list set master_id=?, order_id=?, order_status=?, order_info=?";
                            $result = $pdo->prepare($prepareSql);
                            foreach ($masterId as $key => $val) {
                                $master_id    = $val;
                                $order_id     = $orderData['order_id'];
                                $order_status = 0;
                                $order_info   = $orderInfo;
                                $result->bindParam(1, $master_id);
                                $result->bindParam(2, $order_id);
                                $result->bindParam(3, $order_status);
                                $result->bindParam(4, $order_info);
                                $result->execute();
                            }

                            //发送推送消息 
                            $selectSql = "select id,xg_id,system from users where id in ({$masterIdString})";
                            $stmt = $pdo->query($selectSql);
                            $stmt->setFetchMode(PDO::FETCH_ASSOC);
                            $masterList = $stmt->fetchAll();
                            foreach ($masterList as $key => $val) {
                                if($val['system'] == 'andriod'){
                                    $res = \xinge\XingeApp::PushAccountAndroid($andorid_access_id, $andorid_secret_key, "玩家下单", "有玩家下单", $val['xg_id']);
                                }
                                if($val['system'] == 'ios'){
                                    $res = \xinge\XingeApp::PushAccountIos($ios_access_id, $ios_secret_key, "有玩家下单", $val['xg_id'], \xinge\XingeApp::IOSENV_DEV);
                                }
                            }
                        } 
                    }
                }
                catch(PDOException $e)
                {}
            }
            catch (CMQExceptionBase $e)
            {}
        }
    }
}

$secretId = "AKID9rSvHTyHUcbpz1zQr7B5oy2vfcUvANKH";
$secretKey = "yU5bw6iDnCPRJDvFjEXebLQRHbbVQCrM";
$endPoint = "http://cmq-queue-cd.api.qcloud.com";

$instance = new Matching($secretId, $secretKey, $endPoint);
$instance->run();