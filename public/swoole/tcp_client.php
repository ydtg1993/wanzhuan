<?php
date_default_timezone_set('PRC');

//连接redis
$host 		= "10.0.0.5";
$port 		= 6379;
$instanceid = "crs-ba89hzva";
$pwd 		= "wzhy@2018#";
$redis 		= new Redis();
$redis->connect($host, $port);
$redis->auth($instanceid . ":" . $pwd);
$redis->select(1);

$redisOrderListKey = 'order:list';
$orderIdList = $redis->zrevrange($redisOrderListKey, 0, -1,'withScore');

print_r($orderIdList);

foreach ($orderIdList as $k=>$v) {
	$redisTempOrderKey = 'order:' . $k;
	$orderInfo = $redis->hgetall($redisTempOrderKey);
	if(count($orderInfo)){
		$orderList[] = $orderInfo;
	}
}

print_r($orderList);