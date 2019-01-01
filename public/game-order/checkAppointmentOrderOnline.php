<?php
require_once __DIR__.'/log.php';
date_default_timezone_set('PRC');
$logHandler= new CLogFileHandler(__DIR__.'/../logs/'.date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

class Checkmaster
{
    public function __construct()
    {
        
    }

    public function run()
    { 
        //pdo数据库  
        $dsn                = 'mysql:dbname=wanzhuan;host=10.0.0.9;port=3306';
		$username           = 'root';
		$password           = 'wzkj2018';
        $pdo                = new PDO($dsn, $username, $password);
        
		//连接redis
		$host 		= "10.0.0.5";
		$port 		= 6379;
		$instanceid = "crs-ba89hzva";
		$pwd 		= "wzhy@2018#";
		$redis 		= new Redis();
		$redis->connect($host, $port);
		$redis->auth($instanceid . ":" . $pwd);
		$redis->select(1);
		$selectSql = "select order_id,service_time,created_at,game_start_at from appointment_order where game_status < 2";
        $stmt = $pdo->query($selectSql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $orderList = $stmt->fetchAll();
		$orderIdArray = [];
		$now = time();
		$redisOrderListKey = 'order:list';
        foreach ($orderList as $v) {
			$order_id = $v['order_id'];
			$service_time = intval($v['service_time']);
			$game_start_at = intval($v['game_start_at']);
			$created_at = intval(strtotime($v['created_at']));
			if($game_start_at){
				$limittime = $game_start_at + $service_time * 60;
				if($now > $limittime){
					$orderIdArray[] = $order_id;
					$updateSql = "update appointment_order set game_status = 2 where order_id = '{$order_id}'";
					$pdo->query($updateSql);
					$redis->zrem($redisOrderListKey, $order_id);
					$redisOrderKey = 'order:' . $order_id;
					$redis->del($redisOrderKey);
				}
			}else{
				$limittime = $created_at + 3600;
				if($now > $limittime){
					$orderIdArray[] = $order_id;
					$updateSql = "update appointment_order set game_status = 2 where order_id = '{$order_id}'";
					$pdo->query($updateSql);
					$redis->zrem($redisOrderListKey, $order_id);
					$redisOrderKey = 'order:' . $order_id;
					$redis->del($redisOrderKey);
				}
			}
			
		}
		if(count($orderIdArray)){
			$orderIds = implode("','" ,$orderIdArray);
			$deleteSql = "delete from appointment_grab_order where order_id in ('{$orderIds}')";
			$pdo->query($deleteSql);
			$deleteSql = "delete from appointment_status where order_id in ('{$orderIds}')";
			$pdo->query($deleteSql);
		}
    }
}

$instance = new Checkmaster();
$instance->run();