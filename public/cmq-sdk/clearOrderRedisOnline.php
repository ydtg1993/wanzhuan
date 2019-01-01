<?php
//用户接单序列化信息
//master_range_?:m_r_?
date_default_timezone_set('PRC');
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

$list = $redis->keys('*_status');
foreach ($list as $key => $value) {
	$tempData = explode('_', $value);
	$order_id = $tempData[0];
	$selectSql = "select * from normal_orders where order_id = '{$order_id}'";
    $stmt = $pdo->query($selectSql);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $orderInfo = $stmt->fetch();
    if($orderInfo['status'] >=2 ){
    	$redis->del($value);
    }

    if($orderInfo['game_status'] == 1){
    	$redis->del($value);
    }
}


$list = $redis->keys('*_grabbed');
foreach ($list as $key => $value) {
	$tempData = explode('_', $value);
	$order_id = $tempData[0];
	$selectSql = "select * from normal_orders where order_id = '{$order_id}'";
    $stmt = $pdo->query($selectSql);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $orderInfo = $stmt->fetch();
    if($orderInfo['status'] >=2 ){
    	$redis->del($value);
    }

    if($orderInfo['game_status'] == 1){
    	$redis->del($value);
    }
}