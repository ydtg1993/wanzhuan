<?php
date_default_timezone_set('PRC');

//创建Server对象，监听 172.21.0.3:9501端口
$serv = new swoole_server("10.0.0.2", 9044);

//设置参数
$serv->set(array(
	'reactor_num' => 1,
	'worker_num' => 1,
	'task_worker_num' => 1,
	'max_conn ' => 10000,
	'max_request' => 100,
	'heartbeat_check_interval' => 60,
	'heartbeat_idle_time' => 300,
));

//监听连接进入事件
$serv->on('connect', function ($serv, $fd) {
	$sendData['type'] = 'connect';
	$sendData['data'] = json_encode(['fd'=>$fd, 'title'=>'connect', 'message'=>'lol sb'], true);
	$sendData['time'] = date('Y-m-d H:i:s');
    $serv->send($fd, json_encode($sendData));
});

//监听数据接收事件
$serv->on('receive', function ($serv, $fd, $from_id, $data) {
	echo "{$data}\r\n";
	$sendData['type'] = 'receive';
	$sendData['data'] = json_encode(['fd'=>$fd, 'title'=>'receive', 'message'=>'lol sb', 'client_message'=>$data], true);
	$sendData['time'] = date('Y-m-d H:i:s');
    $serv->send($fd, json_encode($sendData));
	sleep(2);
	$param = array('fd' => $fd);
	$serv->task(json_encode($param));
});

//处理异步任务
$serv->on('task', function ($serv, $task_id, $from_id, $data) {
	$source = json_decode($data, true);
   	
	$sendData['type'] = 'task';
	$sendData['data'] = json_encode(['fd'=>$source['fd'], 'title'=>'task', 'message'=>'lol sb'], true);
	$sendData['time'] = date('Y-m-d H:i:s');
	sleep(5);
    $serv->send($source['fd'], json_encode($sendData));
	sleep(5);
    $serv->send($source['fd'], json_encode($sendData));
	sleep(5);
    $serv->send($source['fd'], json_encode($sendData));
	sleep(5);
	$serv->finish($data);
});

//处理异步任务的结果
$serv->on('finish', function ($serv, $task_id, $data) {
    $source = json_decode($data, true);

	$sendData['type'] = 'finish';
	$sendData['data'] = json_encode(['fd'=>$source['fd'], 'title'=>'finish', 'message'=>'lol sb'], true);
	$sendData['time'] = date('Y-m-d H:i:s');
	sleep(5);
	$serv->send($source['fd'], json_encode($sendData));
});

//监听连接关闭事件
$serv->on('close', function ($serv, $fd) {
	echo "close {$fd}\n";
});

//启动服务器
$serv->start();