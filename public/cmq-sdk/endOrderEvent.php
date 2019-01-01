<?php
require_once 'cmq/cmq_api.php';
require_once CMQAPI_ROOT_PATH . '/account.php';
require_once CMQAPI_ROOT_PATH . '/queue.php';
require_once CMQAPI_ROOT_PATH . '/cmq_exception.php';
require_once __DIR__ . '/../xinge-api-php/XingeApp.php';
require_once __DIR__ . '/../emchat-server-php/Easemob.class.php';
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
        //信鸽推送
        $andorid_access_id = 2100300435;
        $andorid_secret_key = '96774184c1d236fe0cfb37744b9c0515';

        $ios_access_id = 2200302271;
        $ios_secret_key = '82477f2c9b7956386583dc094ddef0a1';
        //腾讯cmq
        $queue_name = CMQ_PRENAME."end-order";
        $my_account = new Account($this->endPoint, $this->secretId, $this->secretKey);
        $my_queue = $my_account->get_queue($queue_name);

        $options['client_id'] = 'YXA6CMKXsH6JEein442lKsUphw';
        $options['client_secret'] = 'YXA6UMkqhZfv6C65DgPYZRYbpAfcZwk';
        $options['org_name'] = '1126180703253618';
        $options['app_name'] = 'wanzhuan';

        while (true) {
            try {
                $recv_msg = $my_queue->receive_message(3);
                $receveData = json_decode($recv_msg, true);
                $orderData = json_decode($receveData['msgBody'], true);
                echo date('Y-m-d H:i:s') . "\n";
                print_r($orderData);
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

                    if (!$order['game_status'] == 2) {
                        $my_queue->delete_message($recv_msg->receiptHandle);
                        continue;
                    }

                    $easemob = new Easemob($options);
                    $user_id = $order['user_id'];
                    $accept_user_id = $order['accept_user_id'];

                    $redisUserFdKey = 'user:fd:' . $user_id;
                    $res = $redis->exists($redisUserFdKey);
                    if ($res) {
                        $userSocketInfo = $redis->hgetall($redisUserFdKey);
                        $client = new swoole_client(SWOOLE_SOCK_TCP);
                        $client->set([
                            'open_eof_split' => true,
                            'package_eof' => "\r\n",
                        ]);
                        $client->connect($userSocketInfo['ip'], 9044, 0.5);
                        $sendData['type'] = 'endorder';
                        $sendData['data'] = [
                            'user_id' => $user_id,
                            'order_id' => $order_id,
                            'pay_sum' => $order['pay_sum']
                        ];
                        echo '已通知下单人';
                        $sendPackage = (string)json_encode($sendData) . "\r\n";
                        $res = $client->send($sendPackage);
                    }

                    $redisAcceptUserFdKey = 'user:fd:' . $accept_user_id;
                    $res = $redis->exists($redisAcceptUserFdKey);
                    if ($res) {
                        $userAcceptSocketInfo = $redis->hgetall($redisAcceptUserFdKey);
                        $client = new swoole_client(SWOOLE_SOCK_TCP);
                        $client->set([
                            'open_eof_split' => true,
                            'package_eof' => "\r\n",
                        ]);
                        $client->connect($userAcceptSocketInfo['ip'], 9044, 0.5);
                        $sendData['type'] = 'endorder';
                        $sendData['data'] = ['user_id' => $accept_user_id, 'order_id' => $order_id, 'pay_sum' => $order['pay_sum']];
                        $sendPackage = (string)json_encode($sendData) . "\r\n";
                        echo '已通知接单人';
                        $res = $client->send($sendPackage);
                    }

                    $selectSql = "select id,xg_id,hx_id,nickname,avatar,system from users where id = {$user_id}";
                    $stmt = $pdo->query($selectSql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $userInfo = $stmt->fetch();

                    $selectSql = "select id,xg_id,hx_id,nickname,avatar,system from users where id = {$accept_user_id}";
                    $stmt = $pdo->query($selectSql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $acceptUserInfo = $stmt->fetch();

                    $target_type = 'users';
                    $target = array($acceptUserInfo['hx_id']);
                    $from = $userInfo['hx_id'];
                    $content = "订单结束";
                    $ext['title'] = '订单结束';
                    $ext['type'] = '4';
                    $ext['tip'] = '1';
                    $ext['orderInfo'] = json_encode($order);
                    $ext['redirectInfo'] = '';
                    $ext['nickname'] = $userInfo['nickname'];
                    $ext['avatar'] = $userInfo['avatar'];
                    $res = $easemob->sendText($from, $target_type, $target, $content, $ext);
                    $my_queue->delete_message($recv_msg->receiptHandle);

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