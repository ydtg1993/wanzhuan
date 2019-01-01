<?php
date_default_timezone_set('PRC');
include __DIR__ . '/../../config/fuck.php';
//创建Server对象，监听 10.0.0.2:9502端口
require_once __DIR__ . '/../xinge-api-php/XingeApp.php';
$serv = new swoole_server("0.0.0.0", 9044);

//设置参数
$serv->set(array(
    'task_worker_num' => 2,
    'max_conn ' => 10000,
    'max_request' => 100,
    'heartbeat_check_interval' => 30,
    'heartbeat_idle_time' => 600,
    'package_eof' => "\r\n",
    'daemonize' => 1,
    // 'open_eof_split' => true,
    // 'open_length_check' => true,
    // 'package_max_length' => 81920,
    // 'package_length_type' => 'N',
    // 'package_length_offset' => 0,
    // 'package_body_offset' => 4
));

//监听连接进入事件
$serv->on('connect', function ($serv, $fd) {
    echo "connect {$fd}\n";
});

//监听数据接收事件
$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    $receiveString = trim($data, '\r\n');
    $receiveData = json_decode($receiveString, true);
    $type = $receiveData['type'];
    $typeArray = ['connect', 'appointmentStatus', 'keepalive', 'createorder', 'graborder', 'acceptorder', 'endorder', 'updateorder'];
    if (in_array($type, $typeArray)) {
        $taskDaram = ['fd' => $fd, 'type' => $type, 'data' => $receiveData['data']];
        $isExist = $serv->exist($fd);
        if ($isExist) {
            $serv->task(json_encode($taskDaram));
        }
    }
});

//处理异步任务
$serv->on('task', function ($serv, $task_id, $from_id, $data) {
    //pdo数据库
    $dsn = 'mysql:dbname=' . MYSQL_DBNAME . ';host=' . MYSQL_HOST . ';port=' . MYSQL_PORT;
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

    $receiveTaskData = json_decode($data, true);
    $fd = $receiveTaskData['fd'];
    $type = $receiveTaskData['type'];
    $taskData = $receiveTaskData['data'];
    $logFile = '/data/release/wz_api/storage/logs/tcpServer.log';
    switch ($type) {
        //socket长链接建立成功，查询user如果user存在则关联tcp链接资源
        case 'connect':
            $selectSql = "select id from users where id = {$taskData['user_id']}";
            $stmt = $pdo->query($selectSql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $userInfo = $stmt->fetch();
            if ($userInfo) {
                $redisUserFdKey = 'user:fd:' . $taskData['user_id'];
                $localInfo = swoole_get_local_ip();
                $hashData['ip'] = $localInfo['eth0'];
                $hashData['fd'] = $fd;
                $redis->hmset($redisUserFdKey, $hashData);
                $redis->expire($redisUserFdKey, 6000);
                $redis->set('user:connect:' . $localInfo['eth0'] . ':' . $fd, $taskData['user_id'], 6000);

                $pdo->query("update users set logout_time = 0 where id = {$userInfo['id']}");
            } else {
                $serv->close($fd);
            }
            $isExist = $serv->exist($fd);
            if ($isExist) {
                $serv->finish($data);
            }
            break;
        //socket心跳检测，更新redis关联信息过期时间
        case 'keepalive':
            $redisUserFdKey = 'user:fd:' . $taskData['user_id'];
            $res = $redis->exists($redisUserFdKey);
            if ($res) {
                $redis->expire($redisUserFdKey, 6000);
                $localInfo = swoole_get_local_ip();
                $redis->expire('user:connect:' . $localInfo['eth0'] . ':' . $fd, 6000);
                $isExist = $serv->exist($fd);
                if ($isExist) {
                    $serv->finish($data);
                }
            } else {
                $serv->close($fd);
            }
            break;
        //连接关闭处理
        case 'close':
            $local_ip = swoole_get_local_ip();
            $userId = $redis->get('user:connect:' . $local_ip['eth0'] . ':' . $fd);
            $time = time();
            $updateSql = "update users set logout_time = {$time} where id = {$userId}";
            $pdo->query($updateSql);
            break;
        //socket下单触发事件
        case 'createorder':
            $user = $order = [];
            $user_id = $taskData['user_id'];
            $order_id = $taskData['order_id'];
            $selectSql = "select * from users where id = {$user_id} limit 1";
            $stmt = $pdo->query($selectSql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $userInfo = $stmt->fetch();
            if ($userInfo) {
                $user['id'] = $user_id;
                $user['nickname'] = $userInfo['nickname'];
                $user['sexy'] = $userInfo['sexy'];
                $user['avatar'] = $userInfo['avatar'];
                $user['longitude'] = $userInfo['longitude'];
                $user['latitude'] = $userInfo['latitude'];
                $user['intro_mp3'] = '';
                $selectSql = "select * from resources where kind = 6 and user_id = {$user_id} limit 1";
                $stmt = $pdo->query($selectSql);
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $userResourcesInfo = $stmt->fetch();
                if ($userResourcesInfo) {
                    $user['intro_mp3'] = $userResourcesInfo['path'];
                }

                $selectSql = "select * from appointment_order where order_status = 1 and order_id = '{$order_id}' limit 1";
                $stmt = $pdo->query($selectSql);
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $orderInfo = $stmt->fetch();
                if ($orderInfo) {
                    $order['user_id'] = $orderInfo['user_id'];
                    $order['order_id'] = $orderInfo['order_id'];
                    $order['game_id'] = $orderInfo['game_id'];
                    $order['game_name'] = $orderInfo['game_name'];
                    $order['server_id'] = $orderInfo['server_id'];
                    $order['server_name'] = '';
                    $order['gender_limit'] = $orderInfo['gender_limit'];
                    $order['pay_sum'] = $orderInfo['pay_sum'];
                    $order['created_at'] = $orderInfo['created_at'];
                    $order['service_time'] = $orderInfo['service_time'];
                    $order['game_status'] = $orderInfo['game_status'];
                    $selectSql = "select * from game_server where id = {$orderInfo['server_id']} and game_id = {$orderInfo['game_id']} limit 1";
                    $stmt = $pdo->query($selectSql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $userServerInfo = $stmt->fetch();
                    if ($userServerInfo) {
                        $order['server_name'] = $userServerInfo['server_name'];
                    }
                    $order['user_info'] = json_encode($user);
                }
            }
            if (!empty($order)) {
                $now = time();
                $redisOrderListKey = 'order:list';
                $redis->zadd($redisOrderListKey, $now, $order_id);
                $redisOrderKey = 'order:' . $order_id;
                $redis->hmset($redisOrderKey, $order);
                $redis->expire($redisOrderKey, 3600);
                //echo "create {$user_id} {$order_id} \n";
                file_put_contents($logFile, "用户：{$user_id} 创建订单：{$order_id} \n");
                //获取订单列表，组装格式
                $orderIdList = $redis->zrevrange($redisOrderListKey, 0, -1, 'withScore');
                $orderList = [];
                $pay_sum = [];
                foreach ($orderIdList as $k => $v) {
                    $redisTempOrderKey = 'order:' . $k;
                    $orderInfo = $redis->hgetall($redisTempOrderKey);
                    if (count($orderInfo)) {
                        if (isset($orderInfo['user_info'])) {
                            $orderInfo['user_info'] = json_decode($orderInfo['user_info'], true);
                        }
                        $orderList[] = $orderInfo;
                        $pay_sum[] = $orderInfo['pay_sum'];
                    }
                }
                //遍历所有socket资源，推送订单消息
                $sendData['type'] = 'orderlist';
                array_multisort($pay_sum, SORT_DESC, $orderList);
                $sendData['data'] = $orderList;
                $sendPackage = (string)json_encode($sendData) . "\r\n";
                foreach ($serv->connections as $fd) {
                    $serv->send($fd, $sendPackage);
                }
                //玩币大于500 信鸽推送

                $selectUserSql = "select xg_id,system from users where id <> {$user_id} ";

                if ($order['gender_limit'] == 1) {
                    $selectUserSql .= "and sexy = '男'";
                } elseif ($order['gender_limit'] == 2) {
                    $selectUserSql .= "and sexy = '女'";
                }

                $stmt = $pdo->query($selectUserSql);
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $users = $stmt->fetchAll();

                //信鸽推送
                $andorid_access_id = 2100300435;
                $andorid_secret_key = '96774184c1d236fe0cfb37744b9c0515';

                $ios_access_id = 2200302271;
                $ios_secret_key = '82477f2c9b7956386583dc094ddef0a1';
                $andorids = $ios = [];
                foreach ($users as $user) {
                    if (!isset($user) || empty($user['xg_id'])) {
                        continue;
                    }
                    if ($user['system'] == 'andriod') {
                        $andorids[] = $user['xg_id'];
                    }
                    if ($user['system'] == 'ios') {
                        $ios[] = $user['xg_id'];
                    }
                }

                if (count($andorids)) {
                    $andoridsArray = array_chunk($andorids, 99, true);
                    foreach ($andoridsArray as $item){
                        $mess = new \xinge\Message();
                        $mess->setExpireTime(86400);
                        $mess->setTitle('接单啦');
                        $mess->setContent('有一份天价赏金等你来赚，点击看看。');
                        $mess->setType(\xinge\Message::TYPE_NOTIFICATION);
                        $xinggeAandorid = new \xinge\XingeApp($andorid_access_id, $andorid_secret_key);
                        $res = $xinggeAandorid->PushAccountList(0, $item, $mess);
                    }
                }
                if (count($ios)) {
                    $push = new \xinge\XingeApp($ios_access_id, $ios_secret_key);
                    $mess = new \xinge\MessageIOS();
                    $mess->setExpireTime(86400);
                    $mess->setAlert("有一份天价赏金等你来赚，点击看看。");
                    //$mess->setAlert(array('key1'=>'value1'));
                    $mess->setBadge(1);

                    $acceptTime1 = new \xinge\TimeInterval(0, 0, 23, 59);
                    $mess->addAcceptTime($acceptTime1);
                    $res = $push->PushAccountList(0, $ios, $mess, \xinge\XingeApp::IOSENV_DEV);
                }
            }
            break;
        //socket更新订单触发事件
        case 'updateorder':
            $user = $order = [];
            $user_id = $taskData['user_id'];
            $order_id = $taskData['order_id'];
            $now = time();
            //echo "update {$user_id} {$order_id} \n";
            file_put_contents($logFile, "用户：{$user_id} 更新订单：{$order_id} \n");
            $redisOrderListKey = 'order:list';

            //获取订单列表，组装格式
            $orderIdList = $redis->zrevrange($redisOrderListKey, 0, -1, 'withScore');
            $orderList = [];
            foreach ($orderIdList as $k => $v) {
                $redisTempOrderKey = 'order:' . $k;
                $orderInfo = $redis->hgetall($redisTempOrderKey);
                if (count($orderInfo)) {
                    if (isset($orderInfo['user_info'])) {
                        $orderInfo['user_info'] = json_decode($orderInfo['user_info'], true);
                    }
                    $orderList[] = $orderInfo;
                }
            }
            //遍历所有socket资源，推送订单消息
            $sendData['type'] = 'orderlist';
            $sendData['data'] = $orderList;
            $sendPackage = (string)json_encode($sendData) . "\r\n";
            foreach ($serv->connections as $fd) {
                $serv->send($fd, $sendPackage);
            }

            break;
        //socket抢单触发事件
        case 'graborder':
            $user = $order = [];
            $user_id = $taskData['user_id'];
            $order_id = $taskData['order_id'];
            //echo "grab {$user_id} {$order_id} \n";
            file_put_contents($logFile, "用户：{$user_id} 抢单订单：{$order_id} \n");
            //查询订单信息
            $selectSql = "select user_id,order_id,max_accept_num from appointment_order where order_id = '{$order_id}' limit 1";
            $stmt = $pdo->query($selectSql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $orderInfo = $stmt->fetch();
            if (!$orderInfo) {
                break;
            }

            $createOrderUserId = $orderInfo['user_id'];
            $redisUserFdKey = 'user:fd:' . $createOrderUserId;
            $res = $redis->exists($redisUserFdKey);
            if (!$res) {
                break;
            }
            $userSocketInfo = $redis->hgetall($redisUserFdKey);

            $selectSql = "select accept_user_id from appointment_grab_order where order_id = '{$order_id}'";
            $stmt = $pdo->query($selectSql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $grabOrderList = $stmt->fetchAll();

            if (count($grabOrderList)) {
                $acceptUserIdArray = array_column($grabOrderList, 'accept_user_id');
                $acceptUserId = implode(',', $acceptUserIdArray);
                $selectSql = "select id,nickname,sexy,longitude,latitude,profession,avatar,hx_id,about,(SELECT path FROM resources WHERE type = 2 and user_id = users.id ORDER BY id desc limit 1) as mp3 from users where id in ({$acceptUserId})";
                $stmt = $pdo->query($selectSql);
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $grabUserList = $stmt->fetchAll();
                //随机取3个抢单信息
                if (count($grabUserList) > $orderInfo['max_accept_num']) {
                    $random_keys = array_rand($grabUserList, $orderInfo['max_accept_num']);
                    $grabRandomUserList[] = $grabUserList[$random_keys[0]];
                    $grabRandomUserList[] = $grabUserList[$random_keys[1]];
                    $grabRandomUserList[] = $grabUserList[$random_keys[2]];
                } else {
                    $grabRandomUserList = $grabUserList;
                }
                foreach ($grabRandomUserList as $key => $value) {
                    $grabRandomUserList[$key]['order_info'] = $orderInfo;
                }
                $sendData['type'] = 'graborder';
                $sendData['data'] = $grabRandomUserList;
                $sendPackage = (string)json_encode($sendData) . "\r\n";
                $serv->send($userSocketInfo['fd'], $sendPackage);
            } else {
                $sendData['type'] = 'graborder';
                $sendData['data'] = [];
                $sendPackage = (string)json_encode($sendData) . "\r\n";
                $serv->send($userSocketInfo['fd'], $sendPackage);
            }

            break;
        case 'appointmentStatus':
            $user = $order = [];
            $user_id = $taskData['user_id'];
            $order_id = $taskData['order_id'];
            //echo "appointmentStatus {$user_id} {$order_id} \n";
            file_put_contents($logFile, "用户：{$user_id} 拉取订单状态：{$order_id} \n");
            //查询订单信息
            $selectSql = "select user_id,order_id,max_accept_num from appointment_order where order_id = '{$order_id}' limit 1";
            $stmt = $pdo->query($selectSql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $orderInfo = $stmt->fetch();
            if (!$orderInfo) {
                break;
            }

            $createOrderUserId = $orderInfo['user_id'];
            $redisUserFdKey = 'user:fd:' . $createOrderUserId;
            $res = $redis->exists($redisUserFdKey);
            if (!$res) {
                break;
            }
            $userSocketInfo = $redis->hgetall($redisUserFdKey);

            $selectSql = "select accept_user_id from appointment_grab_order where order_id = '{$order_id}'";
            $stmt = $pdo->query($selectSql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $grabOrderList = $stmt->fetchAll();

            if (count($grabOrderList)) {
                $acceptUserIdArray = array_column($grabOrderList, 'accept_user_id');
                $acceptUserId = implode(',', $acceptUserIdArray);
                $selectSql = "select id,nickname,sexy,longitude,latitude,profession,avatar,hx_id,about,(SELECT path FROM resources WHERE type = 2 and user_id = users.id ORDER BY id desc limit 1) as mp3 from users where id in ({$acceptUserId})";
                $stmt = $pdo->query($selectSql);
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $grabUserList = $stmt->fetchAll();
                //随机取3个抢单信息
                if (count($grabUserList) > $orderInfo['max_accept_num']) {
                    $random_keys = array_rand($grabUserList, $orderInfo['max_accept_num']);
                    $grabRandomUserList[] = $grabUserList[$random_keys[0]];
                    $grabRandomUserList[] = $grabUserList[$random_keys[1]];
                    $grabRandomUserList[] = $grabUserList[$random_keys[2]];
                } else {
                    $grabRandomUserList = $grabUserList;
                }
                foreach ($grabRandomUserList as $key => $value) {
                    $grabRandomUserList[$key]['order_info'] = $orderInfo;
                }
                $sendData['type'] = 'graborder';
                $sendData['data'] = $grabRandomUserList;
                $sendPackage = (string)json_encode($sendData) . "\r\n";
                $serv->send($userSocketInfo['fd'], $sendPackage);
            } else {
                $sendData['type'] = 'graborder';
                $sendData['data'] = [];
                $sendPackage = (string)json_encode($sendData) . "\r\n";
                $serv->send($userSocketInfo['fd'], $sendPackage);
            }
            break;
        case 'acceptorder':
            $user = $order = [];
            $user_id = $taskData['user_id'];
            $order_id = $taskData['order_id'];
            //echo "accept {$user_id} {$order_id} \n";
            file_put_contents($logFile, "用户：{$user_id} 选人：{$order_id} \n");

            //查询下单人socket信息
            $selectSql = "select user_id,order_id,max_accept_num from appointment_order where order_id = '{$order_id}' limit 1";
            $stmt = $pdo->query($selectSql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $createOrderInfo = $stmt->fetch();
            $createOrderUserId = $createOrderInfo['user_id'];

            $selectSql = "select id,nickname,sexy,longitude,latitude,profession,avatar,hx_id from users where id = {$createOrderUserId} limit 1";
            $stmt = $pdo->query($selectSql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $createUserInfo = $stmt->fetch();

            $redisUserFdKey = 'user:fd:' . $user_id;
            $res = $redis->exists($redisUserFdKey);
            if (!$res) {
                break;
            }
            $userSocketInfo = $redis->hgetall($redisUserFdKey);

            $sendData['type'] = 'acceptorder';
            $sendData['data'] = ['order_id' => $order_id, 'matching_status' => 1, 'user_info' => $createUserInfo];
            $sendPackage = (string)json_encode($sendData) . "\r\n";
            $serv->send($userSocketInfo['fd'], $sendPackage);

            $selectSql = "select accept_user_id from appointment_grab_order where order_id = '{$order_id}' and accept_user_id != {$user_id}";
            $stmt = $pdo->query($selectSql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $grabOrderList = $stmt->fetchAll();
            foreach ($grabOrderList as $k => $v) {
                $redisTempUserFdKey = 'user:fd:' . $v['accept_user_id'];
                $res = $redis->exists($redisTempUserFdKey);
                if ($res) {
                    $userTempSocketInfo = $redis->hgetall($redisTempUserFdKey);
                    $sendData['type'] = 'acceptorder';
                    $sendData['data'] = ['order_id' => $order_id, 'matching_status' => 0, 'user_info' => (object)null];
                    $sendPackage = (string)json_encode($sendData) . "\r\n";
                    $serv->send($userTempSocketInfo['fd'], $sendPackage);
                }
            }

            break;
        //socket订单结束触发事件
        case 'endorder':
            $user = $order = [];
            $user_id = $taskData['user_id'];
            $order_id = $taskData['order_id'];
            //echo "end {$user_id} {$order_id} \n";
            file_put_contents($logFile, "用户：{$user_id}  结束订单：{$order_id} \n");

            $redisUserFdKey = 'user:fd:' . $user_id;
            $res = $redis->exists($redisUserFdKey);
            if (!$res) {
                break;
            }
            $userSocketInfo = $redis->hgetall($redisUserFdKey);

            $sendData['type'] = 'endorder';
            $shareData = [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'share_time' => time(),
            ];
            $sendData['data'] = [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'title' => '玩转-呼叫你附近的高颜值队友',
                'image' => 'https://static-1257042421.cos.ap-chengdu.myqcloud.com/img/log-300-300.png',
                'description' => '找你附近的小姐姐一起吃鸡',
                'amount' => $taskData['pay_sum'] * 0.1,
                'loc' => '',
                'link' => 'https://h5.wanzhuanhuyu.cn/appointmentShare/' . bin2hex(json_encode($shareData)),
            ];
            $sendPackage = (string)json_encode($sendData) . "\r\n";
            $serv->send($userSocketInfo['fd'], $sendPackage);
            break;

        default:
            break;
    }
});

//处理异步任务的结果
$serv->on('finish', function ($serv, $task_id, $data) {
    //连接redis
    $host = REDIS1_HOST;
    $port = REDIS1_PORT;
    $instanceid = REDIS1_INSTENCEID;
    $pwd = REDIS1_PWD;
    $redis = new Redis();
    $redis->connect($host, $port);
    $redis->auth($instanceid . ":" . $pwd);
    $redis->select(1);

    $receiveTaskData = json_decode($data, true);
    $fd = $receiveTaskData['fd'];
    $type = $receiveTaskData['type'];
    $taskData = $receiveTaskData['data'];

    $sendData['time'] = date('Y-m-d H:i:s');
    switch ($type) {
        case 'connect':
            $redisOrderListKey = 'order:list';

            $orderIdList = $redis->zrevrange($redisOrderListKey, 0, -1, 'withScore');
            $orderList = [];
            $pay_sum = [];
            foreach ($orderIdList as $k => $v) {
                $redisTempOrderKey = 'order:' . $k;
                $orderInfo = $redis->hgetall($redisTempOrderKey);
                if (count($orderInfo)) {
                    if (isset($orderInfo['user_info'])) {
                        $orderInfo['user_info'] = json_decode($orderInfo['user_info'], true);
                    }
                    $orderList[] = $orderInfo;
                    $pay_sum[] = $orderInfo['pay_sum'];
                }
            }
            //遍历所有socket资源，推送订单消息
            $sendData['type'] = 'orderlist';
            array_multisort($pay_sum, SORT_DESC, $orderList);
            $sendData['data'] = $orderList;
            $sendPackage = (string)json_encode($sendData) . "\r\n";
            break;
        case 'keepalive':
            $sendData['type'] = 'keepalive';
            $sendData['data'] = ['title' => 'connect', 'message' => 'connect keep alive', 'status' => 1];
            $sendPackage = (string)json_encode($sendData) . "\r\n";
            break;
        default:
            break;
    }
    $isExist = $serv->exist($fd);
    if ($isExist) {
        $serv->send($fd, $sendPackage);
    }
});

//监听连接关闭事件
$serv->on('close', function ($serv, $fd) {
    $taskDaram = ['fd' => $fd, 'type' => 'close', 'data' => []];
    $isExist = $serv->exist($fd);
    if ($isExist) {
        $serv->task(json_encode($taskDaram));
    }
});

//启动服务器
$serv->start();
