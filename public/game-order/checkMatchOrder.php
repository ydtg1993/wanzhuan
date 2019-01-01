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
        $dsn                = 'mysql:dbname=wz-dev;host=bj-cdb-5g870uz0.sql.tencentcdb.com;port=63184';
        $username           = 'root';
        $password           = 'wzkj@2018';
        $pdo                = new PDO($dsn, $username, $password);
        
		$difftime = time() - 3600 * 5;
        $datatime = date('Y-m-d H:i:s', $difftime);
        $deleteSql = "delete from yuewan_user_list where create_time < '{$datatime}'";
        $pdo->query($deleteSql);

        $deleteSql = "delete from yuewan_user_list where status > 0";
        $pdo->query($deleteSql);
    }
}

$instance = new Checkmatch();
$instance->run();