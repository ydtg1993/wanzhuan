<?php
//基于游戏、性别生成可以接单的用户列表
//master_sex_gameid_?_?:m_s_g_?_?
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

//1男、2女
$sex = [1=>'男', 2=>'女'];
$maserSexList = [];
foreach ($sex as $k => $v){
	$selectSql = "select user_id from masters where status = 2 and sex = '{$v}'";
	$stmt = $pdo->query($selectSql);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$masterList = $stmt->fetchAll();
	$masterUserId = array_column($masterList, 'user_id');
	$maserSexList[$k] = $masterUserId;
}

$selectSql = "select id,name from games where game_type = 1";         
$stmt = $pdo->query($selectSql);
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$gameList = $stmt->fetchAll();

foreach ($gameList as $key => $val) {
	$selectSql = "select master_id from master_game_search_range where game_id = {$val['id']}";
	$stmt = $pdo->query($selectSql);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$masterGameList = $stmt->fetchAll();
	$masterGameUserId = array_column($masterGameList, 'master_id');
	$masterGameUserId = array_unique($masterGameUserId);
	
	foreach ($maserSexList as $k => $v) {
		$redisMaster = [];
		$redisKey = 'pool:sex:'. $k .':game:' . $val['id'];
		$redis->del($redisKey);
		foreach ($v as $vv) {
			if(in_array($vv, $masterGameUserId)){
				//$redisMaster[] = $vv;
				$redis->lpush($redisKey, $vv);
			}
		}
		$redis->expire($redisKey, 60);
	}
}