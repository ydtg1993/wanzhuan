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
        $queue_name                      = "end-order";
        $my_account                      = new Account($this->endPoint, $this->secretId, $this->secretKey);
        $my_queue                        = $my_account->get_queue($queue_name);
        
        while (true) {
            try
            {
                $recv_msg = $my_queue->receive_message(3);
                $receveData = json_decode($recv_msg, true);
                $orderData = json_decode($receveData['msgBody'], true);
                echo date('Y-m-d H:i:s') . "\n";
                print_r($orderData);
                try{
                    //pdo数据库  
                    $dsn                = 'mysql:dbname=wanzhuan;host=10.0.0.9;port=3306';
                    $username           = 'root';
                    $password           = 'wzkj2018';
                    $pdo                = new PDO($dsn, $username, $password);

                    //连接redis
                    $host       = "10.0.0.5";
                    $port       = 6379;
                    $instanceid = "crs-ba89hzva";
                    $pwd        = "wzhy@2018#";
                    $redis      = new Redis();
                    $redis->connect($host, $port);
                    $redis->auth($instanceid . ":" . $pwd);
                    $redis->select(1);
					
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
                    if(!isset($orderData['event_name']) || !$orderData['event_name'])
                    {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    $order_id = $orderData['order_id'];
                    $event_name = $orderData['event_name'];
                    //判断订单是否存在
                    $selectSql = "select * from appointment_order where order_id = '" . $orderData['order_id'] . "'";
                    $stmt = $pdo->query($selectSql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $order = $stmt->fetch();
                    if(!$order)
                    {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }

                    if(!$order['game_status'] == 2)
                    {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }

                    $user_id = $order['user_id'];
                    $accept_user_id = $order['accept_user_id'];

                    $redisUserFdKey = 'user:fd:' . $user_id;
                    $res = $redis->exists($redisUserFdKey);
                    if($res){
                        $userSocketInfo = $redis->hgetall($redisUserFdKey);
                        $client = new swoole_client(SWOOLE_SOCK_TCP);
                        $client->set([
                            'open_eof_split' => true,
                            'package_eof' => "\r\n",
                        ]);
                        $client->connect($userSocketInfo['ip'], 9044, 0.5);
                        $sendData['type'] = 'endorder';
                        $sendData['data'] = ['user_id'=>$user_id, 'order_id'=>$order_id];
                        $sendPackage = (string)json_encode($sendData) . "\r\n";
                        $res = $client->send($sendPackage);
                    }else{
                        $selectSql = "select id,xg_id,system from users where id = {$user_id}";
                        $stmt = $pdo->query($selectSql);
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $userInfo = $stmt->fetch();
                        if($userInfo['system'] == 'andriod'){
                            $res = \xinge\XingeApp::PushAccountAndroid($andorid_access_id, $andorid_secret_key, "服务结束", "服务结束", $userInfo['xg_id']);
                        }
                        if($userInfo['system'] == 'ios'){
                            $res = \xinge\XingeApp::PushAccountIos($ios_access_id, $ios_secret_key, "服务结束", $userInfo['xg_id'], \xinge\XingeApp::IOSENV_DEV);
                        }
                    }

                    $redisAcceptUserFdKey = 'user:fd:' . $accept_user_id;
                    $res = $redis->exists($redisAcceptUserFdKey);
                    if($res){
                        $userAcceptSocketInfo = $redis->hgetall($redisAcceptUserFdKey);
                        $client = new swoole_client(SWOOLE_SOCK_TCP);
                        $client->set([
                            'open_eof_split' => true,
                            'package_eof' => "\r\n",
                        ]);
                        $client->connect($userAcceptSocketInfo['ip'], 9044, 0.5);
                        $sendData['type'] = 'endorder';
                        $sendData['data'] = ['user_id'=>$accept_user_id, 'order_id'=>$order_id];
                        $sendPackage = (string)json_encode($sendData) . "\r\n";
                        $res = $client->send($sendPackage);
                    }else{
                        $selectSql = "select id,xg_id,system from users where id = {$accept_user_id}";
                        $stmt = $pdo->query($selectSql);
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $userInfo = $stmt->fetch();
                        if($userInfo['system'] == 'andriod'){
                            $res = \xinge\XingeApp::PushAccountAndroid($andorid_access_id, $andorid_secret_key, "服务结束", "服务结束", $userInfo['xg_id']);
                        }
                        if($userInfo['system'] == 'ios'){
                            $res = \xinge\XingeApp::PushAccountIos($ios_access_id, $ios_secret_key, "服务结束", $userInfo['xg_id'], \xinge\XingeApp::IOSENV_DEV);
                        }
                    }

                    $my_queue->delete_message($recv_msg->receiptHandle);
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