<?php
require_once 'cmq/cmq_api.php';
require_once CMQAPI_ROOT_PATH . '/account.php';
require_once CMQAPI_ROOT_PATH . '/queue.php';
require_once CMQAPI_ROOT_PATH . '/cmq_exception.php';
require_once __DIR__ . '/../xinge-api-php/XingeApp.php';
require_once __DIR__ . '/log.php';
date_default_timezone_set('PRC');

include __DIR__.'/../../config/fuck.php';


$logHandler = new CLogFileHandler(__DIR__ . '/../logs/' . date('Y-m-d') . '.log');
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
        //腾讯cmq
        $queue_name = CMQ_PRENAME."socket-event";
        $my_account = new Account($this->endPoint, $this->secretId, $this->secretKey);
        $my_queue = $my_account->get_queue($queue_name);
        $queue_meta = new QueueMeta();

        while (true) {
            try {
                $recv_msg = $my_queue->receive_message(3);
                $receveData = json_decode($recv_msg, true);
                $orderData = json_decode($receveData['msgBody'], true);
                //print_r($orderData);
                try {
                    //pdo数据库
                    $dsn = 'mysql:dbname='.MYSQL_DBNAME.';host='.MYSQL_HOST.';port='.MYSQL_PORT;
                    $username = MYSQL_USERNAME;
                    $password = MYSQL_PWD;
                    $pdo = new PDO($dsn, $username, $password);

                    //连接redis
                    $host = REDIS1_HOST;
                    $port = REDIS1_PORT;
                    $instanceid = REDIS1_INSTENCEID;
                    $pwd = REDIS1_PWD;
                    $redis = new Redis();
                    $redis->connect($host, $port);
                    $redis->auth($instanceid . ":" . $pwd);
                    $redis->select(1);

                    //如果未接受必要参数则跳出循环并剔除消息
                    if (!isset($orderData['user_id']) || !$orderData['user_id']) {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    if (!isset($orderData['order_id']) || !$orderData['order_id']) {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    if (!isset($orderData['event_name']) || !$orderData['event_name']) {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    $user_id = $orderData['user_id'];
                    $order_id = $orderData['order_id'];
                    $event_name = $orderData['event_name'];
                    //判断订单是否存在
                    $selectSql = "select * from appointment_order where order_id = '" . $orderData['order_id'] . "'";
                    $stmt = $pdo->query($selectSql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $order = $stmt->fetch();
                    if (!$order) {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }
                    switch ($event_name) {
                        case 'createorder':
                            $client = new swoole_client(SWOOLE_SOCK_TCP);
                            $client->set([
                                'open_eof_split' => true,
                                'package_eof' => "\r\n",
                            ]);

                            //如果是多台服务器，则创建多个socket连接
                            $redisServerKey = 'socket:server:ip';
                            $server = $redis->smembers($redisServerKey);
                            var_dump($server);
                            foreach ($server as $skey => $sval) {
                                $serverInfo = explode(":", $sval);
                                $serverIp = $serverInfo[0];
                                $serverPort = $serverInfo[1];
                                $client->connect($serverIp, $serverPort, 0.5);
                                $sendData['type'] = 'createorder';
                                $sendData['data'] = ['user_id' => $user_id, 'order_id' => $order_id];
                                $sendPackage = (string)json_encode($sendData) . "\r\n";
                                $client->send($sendPackage);
                                $client->close();
                            }

                            $my_queue->delete_message($recv_msg->receiptHandle);
                            break;
                        case 'cancelorder':
                            $redisOrderListKey = 'order:list';
                            $redis->zrem($redisOrderListKey, $order_id);
                            $redisOrderKey = 'order:' . $order_id;
                            $redis->del($redisOrderKey);

                            $client = new swoole_client(SWOOLE_SOCK_TCP);
                            $client->set([
                                'open_eof_split' => true,
                                'package_eof' => "\r\n",
                            ]);

                            //如果是多台服务器，则创建多个socket连接
                            $redisServerKey = 'socket:server:ip';
                            $server = $redis->smembers($redisServerKey);
                            foreach ($server as $skey => $sval) {
                                $serverInfo = explode(":", $sval);
                                $serverIp = $serverInfo[0];
                                $serverPort = $serverInfo[1];
                                $client->connect($serverIp, $serverPort, 0.5);
                                $sendData['type'] = 'updateorder';
                                $sendData['data'] = ['user_id' => $user_id, 'order_id' => $order_id];
                                $sendPackage = (string)json_encode($sendData) . "\r\n";
                                $client->send($sendPackage);
                                $client->close();
                            }
                            $my_queue->delete_message($recv_msg->receiptHandle);
                            break;
                        case 'graborder':
                            if ($order['order_status'] != 1) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                                continue;
                            }
                            if ($order['game_status'] != 0) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                                continue;
                            }

                            //查询下单人socket信息, appointment_order 为下单人user_id
                            $selectSql = "select user_id from appointment_order where order_id = '{$order_id}' limit 1";
                            $stmt = $pdo->query($selectSql);
                            $stmt->setFetchMode(PDO::FETCH_ASSOC);
                            $createOrderInfo = $stmt->fetch();

                            if (!$createOrderInfo) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                                continue;
                            }

                            $createOrderUserId = $createOrderInfo['user_id'];
                            $redisUserFdKey = 'user:fd:' . $createOrderUserId;
                            $res = $redis->exists($redisUserFdKey);
                            if (!$res) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                                continue;
                            }

                            $userSocketInfo = $redis->hgetall($redisUserFdKey);
                            $client = new swoole_client(SWOOLE_SOCK_TCP);
                            $client->set([
                                'open_eof_split' => true,
                                'package_eof' => "\r\n",
                            ]);
                            $client->connect($userSocketInfo['ip'], 9044, 0.5);
                            $sendData['type'] = 'graborder';
                            $sendData['data'] = ['user_id' => $user_id, 'order_id' => $order_id];
                            $sendPackage = (string)json_encode($sendData) . "\r\n";
                            $res = $client->send($sendPackage);
                            if ($res) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                            }
                            $client->close();
                            break;
                        case 'appointmentStatus':
                            if ($order['order_status'] != 1) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                                continue;
                            }
                            if ($order['game_status'] != 0) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                                continue;
                            }

                            //查询下单人socket信息, appointment_order 为下单人user_id
                            $selectSql = "select user_id from appointment_order where order_id = '{$order_id}' limit 1";
                            $stmt = $pdo->query($selectSql);
                            $stmt->setFetchMode(PDO::FETCH_ASSOC);
                            $createOrderInfo = $stmt->fetch();

                            if (!$createOrderInfo) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                                continue;
                            }

                            $createOrderUserId = $createOrderInfo['user_id'];
                            $redisUserFdKey = 'user:fd:' . $createOrderUserId;
                            $res = $redis->exists($redisUserFdKey);
                            if (!$res) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                                continue;
                            }

                            $userSocketInfo = $redis->hgetall($redisUserFdKey);
                            $client = new swoole_client(SWOOLE_SOCK_TCP);
                            $client->set([
                                'open_eof_split' => true,
                                'package_eof' => "\r\n",
                            ]);

                            $client->connect($userSocketInfo['ip'], 9044, 0.5);
                            $sendData['type'] = 'appointmentStatus';
                            $sendData['data'] = ['user_id' => $user_id, 'order_id' => $order_id];
                            $sendPackage = (string)json_encode($sendData) . "\r\n";
                            $res = $client->send($sendPackage);
                            if ($res) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                            }
                            $client->close();
                            break;
                        case 'acceptorder':
                            $redisOrderListKey = 'order:list';
                            $redis->zrem($redisOrderListKey, $order_id);
                            $redisOrderKey = 'order:' . $order_id;
                            $redis->del($redisOrderKey);

                            //查询抢单人socket信息
                            $redisUserFdKey = 'user:fd:' . $user_id;
                            $res = $redis->exists($redisUserFdKey);
                            if (!$res) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                                continue;
                            }
                            $userSocketInfo = $redis->hgetall($redisUserFdKey);
                            $client = new swoole_client(SWOOLE_SOCK_TCP);
                            $client->set([
                                'open_eof_split' => true,
                                'package_eof' => "\r\n",
                            ]);
                            $client->connect($userSocketInfo['ip'], 9044, 0.5);
                            $sendData['type'] = 'acceptorder';
                            $sendData['data'] = ['user_id' => $user_id, 'order_id' => $order_id];
                            $sendPackage = (string)json_encode($sendData) . "\r\n";
                            $res = $client->send($sendPackage);

                            $client = new swoole_client(SWOOLE_SOCK_TCP);
                            $client->set([
                                'open_eof_split' => true,
                                'package_eof' => "\r\n",
                            ]);

                            //如果是多台服务器，则创建多个socket连接
                            $redisServerKey = 'socket:server:ip';
                            $server = $redis->smembers($redisServerKey);
                            foreach ($server as $skey => $sval) {
                                $serverInfo = explode(":", $sval);
                                $serverIp = $serverInfo[0];
                                $serverPort = $serverInfo[1];
                                $client->connect($serverIp, $serverPort, 0.5);
                                $sendData['type'] = 'updateorder';
                                $sendData['data'] = ['user_id' => $user_id, 'order_id' => $order_id];
                                $sendPackage = (string)json_encode($sendData) . "\r\n";
                                $client->send($sendPackage);
                                $client->close();
                            }

                            if ($res) {
                                $my_queue->delete_message($recv_msg->receiptHandle);
                            }
                            break;
                        default:
                            $my_queue->delete_message($recv_msg->receiptHandle);
                            break;
                    }

                } catch (PDOException $e) {
                }
            } catch (CMQExceptionBase $e) {
            }
        }
    }
}

$secretId = "AKID9rSvHTyHUcbpz1zQr7B5oy2vfcUvANKH";
$secretKey = "yU5bw6iDnCPRJDvFjEXebLQRHbbVQCrM";
$endPoint = "http://cmq-queue-cd.api.qcloud.com";

$instance = new Matching($secretId, $secretKey, $endPoint);
$instance->run();