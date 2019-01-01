<?php
require_once __DIR__.'/log.php';
date_default_timezone_set('PRC');
$logHandler= new CLogFileHandler(__DIR__.'/../logs/'.date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

class checkTicketPeriodOnline
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
        
        $datatime = date("Y-m-d");
        $updateSql = "update tickets SET status = 3 where status in(0,1) and period < '{$datatime}'";
        $pdo->query($updateSql);
    }
}

$instance = new checkTicketPeriodOnline();
$instance->run();