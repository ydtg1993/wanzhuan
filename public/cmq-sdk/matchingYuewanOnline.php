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
        $queue_name                      = "matching-yuwan-online";
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
                    //判断订单是否支付
                    $selectSql = "select id,order_id,status from yuewan_orders where order_id = '" . $orderData['order_id'] . "'";
                    $stmt = $pdo->query($selectSql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $yuewanOrder = $stmt->fetch();
                    if(!$yuewanOrder)
                    {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    if($yuewanOrder['status'] >= 2){
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    $selectSql = "select user_id,sexy,game_id,server_id,order_id,search_sexy,match_num from yuewan_user_list where order_id = '" . $orderData['order_id'] . "' limit 1";
                    $stmt = $pdo->query($selectSql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $yuewanUser = $stmt->fetch();
                    if(!$yuewanUser)
                    {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    if($yuewanUser['match_num'] > 8)
                    {
                        $updateSql = "update yuewan_orders set status = 3 where order_id = '{$yuewanUser['order_id']}'";
                        $pdo->query($updateSql);
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    //更新订单匹配次数
                    $updateSql = "update yuewan_user_list set match_num = match_num + 1 where order_id = '" . $orderData['order_id'] . "'";
                    $pdo->query($updateSql);

                    $user_id = $yuewanUser['user_id'];
                    $match_game_id = $yuewanUser['game_id'];
                    $match_server_id = $yuewanUser['server_id'];
                    $match_sexy = $yuewanUser['search_sexy'];
                    $flag = false;

                    $selectSql = "select user_id,sexy,game_id,server_id,order_id,search_sexy from yuewan_user_list where game_id = {$match_game_id} and server_id = {$match_server_id} and sexy = {$match_sexy} and user_id != {$user_id} order by id asc limit 1";
                    $stmt = $pdo->query($selectSql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $matchUser = $stmt->fetch();
                    if($matchUser)
                    {
                        $selectSql = "select id,order_id,game_id,server_id,search_sexy,status from yuewan_orders where order_id = '" . $matchUser['order_id'] . "'";
                        $stmt = $pdo->query($selectSql);
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $matchOrder = $stmt->fetch();
                        if($matchOrder['game_id'] != $yuewanUser['game_id']){
                            continue;
                        }
                        if($matchOrder['server_id'] != $yuewanUser['server_id']){
                            continue;
                        }
                        if($matchOrder['search_sexy'] != $yuewanUser['sexy']){
                            continue;
                        }
                        $flag = true;
                    }

                    if($flag)
                    {
                        $now = time();
                        $prepareSql = "insert into yuewan_match_list set user_id=?, match_user_id=?, game_id=?, server_id=?, order_id=?, match_order_id=?, status=0, create_at=?";
                        $result = $pdo->prepare($prepareSql);
                        
                        $result->bindParam(1, $yuewanUser['user_id']);
                        $result->bindParam(2, $matchUser['user_id']);
                        $result->bindParam(3, $match_game_id);
                        $result->bindParam(4, $match_server_id);
                        $result->bindParam(5, $yuewanUser['order_id']);
                        $result->bindParam(6, $matchUser['order_id']);
                        $result->bindParam(7, $now);
                        $result->execute();

                        $result->bindParam(1, $matchUser['user_id']);
                        $result->bindParam(2, $yuewanUser['user_id']);
                        $result->bindParam(3, $match_game_id);
                        $result->bindParam(4, $match_server_id);
                        $result->bindParam(5, $matchUser['order_id']);
                        $result->bindParam(6, $yuewanUser['order_id']);
                        $result->bindParam(7, $now);
                        $result->execute();
                        
                        $deleteSql = "delete from yuewan_user_list where order_id = '{$matchUser['order_id']}'";
                        $pdo->query($deleteSql);   
                        $deleteSql = "delete from yuewan_user_list where order_id = '{$yuewanUser['order_id']}'";
                        $pdo->query($deleteSql);

                        $updateSql = "update yuewan_orders set status = 2,order_status = 1,match_user_id={$yuewanUser['user_id']} where order_id = '{$matchUser['order_id']}'";
                        $pdo->query($updateSql);
                        $updateSql = "update yuewan_orders set status = 2,order_status = 1,match_user_id={$matchUser['user_id']} where order_id = '{$yuewanUser['order_id']}'";
                        $pdo->query($updateSql);

                        $masterIdString = $yuewanUser['user_id'] . ',' . $matchUser['user_id'];
                        $selectSql = "select id,xg_id,system from users where id in ({$masterIdString})";
                        $stmt = $pdo->query($selectSql);
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $matchUserList = $stmt->fetchAll();
                        foreach ($matchUserList as $key => $val) {
                            if($val['system'] == 'andriod'){
                                \xinge\XingeApp::PushAccountAndroid($andorid_access_id, $andorid_secret_key, "匹配成功", "匹配成功", $val['xg_id']);
                            }
                            if($val['system'] == 'ios'){
                                \xinge\XingeApp::PushAccountIos($ios_access_id, $ios_secret_key, "匹配成功", $val['xg_id'], XingeApp::IOSENV_DEV);
                            }
                        }
                        $my_queue->delete_message($recv_msg->receiptHandle);
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