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


$selectSql = "select user_id from masters";
$stmt = $pdo->query($selectSql);
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$masterList = $stmt->fetchAll();
foreach ($masterList as $k => $v) {
	$redisKey = 'master:range:' . $v['user_id'];
	$redisMasterKey = 'master:' . $v['user_id'];
	$redis->del($redisKey);
	$redis->del($redisMasterKey);
	$selectSql = "select * from master_game_search_range where master_id = {$v['user_id']}";
	$stmt = $pdo->query($selectSql);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$masterRangeList = $stmt->fetchAll();
	foreach ($masterRangeList as $key => $value) {
		$redis->sadd($redisKey, json_encode($value));
	}
	$redis->expire($redisKey, 60);

	$selectSql = "select * from masters where user_id = {$v['user_id']}";
    $stmt = $pdo->query($selectSql);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $masterInfo = $stmt->fetch();
    $redis->hmset($redisMasterKey, $masterInfo);
	$redis->expire($redisMasterKey, 60);
}