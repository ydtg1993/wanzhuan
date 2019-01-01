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
        
		$difftime = time() - 60 * 8;
		$datatime = date('Y-m-d H:i:s', $difftime);
		$deleteSql = "delete from master_gameorder_list where order_status = 0 and create_time < '{$datatime}'";
        $pdo->query($deleteSql);

        $deleteSql = "delete from master_gameorder_list where order_status > 0";
        $pdo->query($deleteSql);
		
        //判断订单是否支付
		/*
        $selectSql = "select a.id from master_gameorder_list a left join normal_orders b on a.order_id = b.order_id  where a.order_status = 0 and b.status = 3";
        $stmt = $pdo->query($selectSql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $gameOrderList = $stmt->fetchAll();
        $gameOrderIdString = '';
        foreach ($gameOrderList as $key => $val) {
            $gameOrderIdString .= $val['id'] . ',';
        }
        $gameOrderIdString = trim($gameOrderIdString, ',');
		if($gameOrderIdString){
			$deleteSql = "delete from master_gameorder_list where id in ({$gameOrderIdString})";
			$pdo->query($deleteSql);
		}
        */
    }
}

$instance = new Checkmaster();
$instance->run();