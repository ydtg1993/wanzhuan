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

        $ios_access_id = 2200302271;
        $ios_secret_key = '82477f2c9b7956386583dc094ddef0a1';
        //腾讯cmq
        $queue_name                      = "matching-redis-online";
        $my_account                      = new Account($this->endPoint, $this->secretId, $this->secretKey);
        $my_queue                        = $my_account->get_queue($queue_name);
        $queue_meta                      = new QueueMeta();
        $queue_meta->queueName           = $queue_name;
        $queue_meta->pollingWaitSeconds  = 10;
        $queue_meta->visibilityTimeout   = 10;
        $queue_meta->maxMsgSize          = 4096;
        $queue_meta->msgRetentionSeconds = 600;

        while (true) {
            try
            {
                $recv_msg = $my_queue->receive_message(3);
                $receveData = json_decode($recv_msg, true);
                $orderData = json_decode($receveData['msgBody'], true);
                $orderInfo = '';
                try{
                    //pdo数据库  
                    $dsn            = 'mysql:dbname=wanzhuan;host=10.0.0.9;port=3306';
                    $username       = 'root';
                    $password       = 'wzkj2018';
                    $pdo            = new PDO($dsn, $username, $password);

                    //redis信息
                    $host           = "10.0.0.5";
                    $port           = 6379;
                    $instanceid     = "crs-ba89hzva";
                    $pwd            = "wzhy@2018#";
                    $redis          = new Redis();
                    $redis->connect($host, $port);
                    $redis->auth($instanceid . ":" . $pwd);
                    $redis->select(0);
                    
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
                    $selectSql = "select user_id,order_id,game_id,game_name,server_id,server_name,master_type,level_id,level_name,level_type,unit,unit_price,game_num,match_num,status,is_exclusive,created_at from normal_orders where order_id = '" . $orderData['order_id'] . "'";
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
                    //延时3秒匹配
                    $created_at = $normalOrder['created_at'];
                    $do_at  = $created_at + 3;
                    $run_at = time();
                    if($run_at < $do_at)
                    {
                        continue;
                    }
                    print_r($normalOrder);
                    //查询下单人信息
                    $redisUserKey = 'u_' . $orderData['user_id'];
                    $redisUser = $redis->hgetall($redisUserKey);
                    if($redisUser){
                        $userInfo = $redisUser;
                    }else{
                        $selectSql = "select id,nickname,sexy,avatar,xg_id,system from users where id = " . $orderData['user_id'];
                        $stmt = $pdo->query($selectSql);
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $userInfo = $stmt->fetch();
                        if(!$userInfo)
                        {
                            $my_queue->delete_message($recv_msg->receiptHandle);
                            continue;
                        }
                        $redis->hmset($redisUserKey, $userInfo);
                        $redis->expire($redisUserKey, 3600);
                    }
                    $orderInfo = array_merge($userInfo, $normalOrder);

                    $matchUserId = [];
                    $matchSex = $normalOrder['master_type'];
                    $matchGameId = $normalOrder['game_id'];
                    //获取基于性别和游戏id的列表
                    $redisMatchSexGameIdKey = 'm_s_g_' . $matchSex . '_' . $matchGameId;
                    echo $redisMatchSexGameIdKey . "\n";
                    $matchUserList = $redis->lrange($redisMatchSexGameIdKey, 0, -1);
                    if(count($matchUserList)){
                        echo "match start\n";
                        foreach ($matchUserList as $v) {
                           $redisMatchUserRangeKey = 'm_r_' . $v;
                           $redisMatchUserRange = $redis->smembers($redisMatchUserRangeKey);
                           echo $redisMatchUserRangeKey . "\n";
                           if(count($redisMatchUserRange)){
                                $redisMatchUserRangeInfo = array_map("unserializeValue",$redisMatchUserRange);
                                foreach ($redisMatchUserRangeInfo as $key => $val) {
                                    $redisMasterKey = 'm_' . $val['master_id'];
                                    $redisMasterStatus = $redis->hget($redisMasterKey, 'status');
                                    if(!$redisMasterStatus || ($redisMasterStatus != 2)){
                                        continue;
                                    }
                                    if($normalOrder['user_id'] == $v){
                                        continue;
                                    }
                                    if($normalOrder['game_id'] != $val['game_id']){
                                        continue;
                                    }
                                    if($normalOrder['server_id'] != $val['server_id']){
                                        continue;
                                    }
                                    if($normalOrder['level_type'] > $val['master_level']){
                                        continue;
                                    }
                                    if($matchSex == 1){
                                        if($normalOrder['level_id'] != $val['level_id']){
                                            continue;
                                        }   
                                    }
                                    echo 'match info ' . $val['game_id'] .'-'. $val['server_id'] .'-'. $val['level_id'] .'-'. $val['master_level'] . "\n";
                                    echo "match ok\n";
                                    $matchUserId[] = $val['master_id'];
                                }
                           }
                        }
                        print_r($matchUserId);
                        if(count($matchUserId)){
                            $matchMasterList = [];
                            $orderInfo = json_encode($orderInfo, JSON_UNESCAPED_UNICODE);
                            foreach ($matchUserId as $key=>$val) {
                                $matchMaster['id']           = time() + $key;
                                $matchMaster['master_id']    = $val;
                                $matchMaster['order_id']     = $orderData['order_id'];
                                $matchMaster['order_status'] = 0;
                                $matchMaster['order_info']   = $orderInfo;
                                $matchMaster['is_exclusive'] = 0;
                                $matchMaster['create_time']  = date('Y-m-d H:i:s');
                                $matchMasterList[] = $matchMaster;
                                $redisMatchMasterOrderKey  = 'm_o_' . $val;
                                $redis->sadd($redisMatchMasterOrderKey, serialize($matchMaster));
                            }
                            foreach ($matchMasterList as $val) {
                                $redisMasterKey = 'u_' . $val['master_id'];
                                $redisMaster = $redis->hgetall($redisMasterKey);
                                if($redisMaster){
                                    $masterInfo = $redisMaster;
                                }else{
                                    $selectSql = "select id,nickname,sexy,avatar,xg_id,system from users where id = " . $val['master_id'];
                                    $stmt = $pdo->query($selectSql);
                                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                                    $masterInfo = $stmt->fetch();
                                    if($masterInfo)
                                    {
                                        $redis->hmset($redisMasterKey, $masterInfo);
                                        $redis->expire($redisMasterKey, 3600);
                                    }
                                }
                                if($masterInfo['system'] == 'andriod'){
                                    $res = \xinge\XingeApp::PushAccountAndroid($andorid_access_id, $andorid_secret_key, "玩家下单", "有玩家下单", $masterInfo['xg_id']);
                                    var_dump($res);
                                }
                                if($masterInfo['system'] == 'ios'){
                                    $res = \xinge\XingeApp::PushAccountIos($ios_access_id, $ios_secret_key, "有玩家下单", $masterInfo['xg_id'], \xinge\XingeApp::IOSENV_DEV);
                                    var_dump($res);
                                }
                            }
                        }else{
                            $updateSql = "update normal_orders set match_num = match_num + 1 where order_id = '" . $orderData['order_id'] . "'";
                            $pdo->query($updateSql);
                        }
                        echo "match end\n";
                    }else{
                        $updateSql = "update normal_orders set match_num = match_num + 1 where order_id = '" . $orderData['order_id'] . "'";
                        $pdo->query($updateSql);
                    }
                    var_dump($recv_msg->receiptHandle);
                    $res = $my_queue->delete_message($recv_msg->receiptHandle);
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

function unserializeValue($value)
{
  $value = unserialize($value);
  return $value;
}
