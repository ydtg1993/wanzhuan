<?php
require_once __DIR__.'/../xinge-api-php/XingeApp.php';
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 15:49
 */
//信鸽推送
$andorid_access_id  = 2100300435;
$andorid_secret_key = '96774184c1d236fe0cfb37744b9c0515';

$ios_access_id      = 2200302271;
$ios_secret_key     = '82477f2c9b7956386583dc094ddef0a1';

/*
$selectSql = "select xg_id,system from users where ";
$ssss = 1;

if ($ssss == 1) {
    $selectSql .= "sexy = '男'";
} elseif ($ssss == 2) {
    $selectSql .= "sexy = '女'";
}

$dsn = 'mysql:dbname=wanzhuan;host=127.0.0.1;port=3306';
$username = 'root';
$password = 'wzhy2018';
$pdo = new PDO($dsn, $username, $password);

$stmt = $pdo->query($selectSql);
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$users = $stmt->fetchAll();

//信鸽推送
$andorid_access_id = 2100300435;
$andorid_secret_key = '96774184c1d236fe0cfb37744b9c0515';

$ios_access_id      = 2200300434;
$ios_secret_key     = '389fc2f2e9e05e5d8239cfe3ad510649';
$andorids = $ios = [];

foreach ($users as $user) {
    if ($user['system'] == 'andriod') {
        $andorids[] = $user['xg_id'];
    }
    if ($user['system'] == 'ios') {
        $ios[] = $user['xg_id'];
    }
}*/

$a = ['18081027880-1','17828283937-10043'];

/*$mess = new \xinge\Message();
$mess->setExpireTime(86400);
$mess->setTitle('接单啦');
$mess->setContent('有一份天价赏金等你来赚，点击看看。');
$mess->setType(\xinge\Message::TYPE_NOTIFICATION);
$xinggeAandorid = new \xinge\XingeApp($andorid_access_id, $andorid_secret_key);*/
//$xinggeIos = new \xinge\XingeApp($ios_access_id, $ios_secret_key);
//$res = $xinggeAandorid->PushAccountList(0, $a, $mess);
//print_r($res);
//$xinggeIos->PushAccountList(4, $ios, '有一份天价赏金等你来赚，点击看看。');
$res = \xinge\XingeApp::PushAccountIos($ios_access_id, $ios_secret_key, "附近的妹子/汉子接单啦 点击确认您的呼叫~", '18328502870-10051', \xinge\XingeApp::IOSENV_DEV);

//$res = \xinge\XingeApp::PushAccountAndroid($andorid_access_id, $andorid_secret_key, "有人接单啦", "附近的妹子/汉子接单啦 点击确认您的呼叫~", '17828283937-10043');