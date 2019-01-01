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
$now = time();
$difftime = $now - 300;
foreach ($masterList as $k => $v) {
	$redisMatchMasterOrderKey  = 'm_o_' . $v['user_id'];
	$redisMatchMasterOrder = $redis->smembers($redisMatchMasterOrderKey);
	
	foreach ($redisMatchMasterOrder as $kk => $vv) {
		$unserializeVal = unserialize($vv);
		$order_id = $unserializeVal['order_id'];
		$redisOrderStatusKey = $order_id . '_status';
		if($redis->exists($redisOrderStatusKey)){
			$master_id = $redis->hget($redisOrderStatusKey, 'master_user_id');
			if($master_id != $v['user_id']){
				echo "user:{$v['user_id']} - master:{$master_id} do it \n";
				$redis->srem($redisMatchMasterOrderKey, $vv);
			}
		}else{
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
			if($difftime > strtotime($unserializeVal['create_time'])){
				echo "do it \n";
				$redis->srem($redisMatchMasterOrderKey, $vv);
			}
		}
	}

}
