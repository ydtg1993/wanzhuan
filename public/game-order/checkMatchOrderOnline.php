<?php
require_once __DIR__.'/log.php';
date_default_timezone_set('PRC');
$logHandler= new CLogFileHandler(__DIR__.'/../logs/'.date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

class Checkmatch
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
		$deleteSql = "delete from yuewan_user_list where create_time < '{$datatime}'";
        $pdo->query($deleteSql);

        $deleteSql = "delete from yuewan_user_list where status > 0";
        $pdo->query($deleteSql);
    }
}

$instance = new Checkmatch();
$instance->run();