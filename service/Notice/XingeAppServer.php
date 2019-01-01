<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/18
 * Time: 18:47
 */

namespace Service\Notice;


use Service\Notice\Xinge\Message;
use Service\Notice\Xinge\MessageIOS;
use Service\Notice\Xinge\TimeInterval;
use Service\Notice\Xinge\XingeApp;

class XingeAppServer
{
    private $config;

    private $message;

    private $app_id;

    private $secret_key;

    private $access_id;

    private $access_key;

    private $environment;

    public function __construct($config)
    {
        if (empty($config) || !is_array($config)) {
            throw new \Exception('配置文件不合法');
        }
        $this->config = $config;
    }

    public function andorid($messageType = Message::TYPE_NOTIFICATION)
    {
        $this->app_id = $this->config['andorid']['app_id'];
        $this->access_id = $this->config['andorid']['access_id'];
        $this->secret_key = $this->config['andorid']['secret_key'];
        $this->access_key = $this->config['andorid']['access_key'];


        $this->message = new Message();
        $this->message->setType($messageType);
        $this->message->setExpireTime(86400);
        $this->message->setSendTime(date('Y-m-d H:i:s'));

        return $this;
    }

    public function ios($messageType = MessageIOS::TYPE_APNS_NOTIFICATION)
    {
        $this->app_id = $this->config['ios']['app_id'];
        $this->access_id = $this->config['ios']['access_id'];
        $this->secret_key = $this->config['ios']['secret_key'];
        $this->access_key = $this->config['ios']['access_key'];
        $this->environment = $this->config['environment'];

        $this->message = new MessageIOS();
        $this->message->setType($messageType);
        $this->message->setExpireTime(86400);
        $this->message->setSendTime(date('Y-m-d H:i:s'));
        $this->message->setBadge(1);
        $acceptTime1 = new TimeInterval(0, 0, 23, 59);
        $this->message->addAcceptTime($acceptTime1);

        return $this;
    }

    public function push($accounts, $title, $content)
    {
        $push = new XingeApp($this->app_id, $this->secret_key, $this->access_id);
        if (!$accounts || empty($accounts)) {
            $ret['err_msg'] = 'account not valid';
            return $ret;
        }

        if (!$title || !$content) {
            $ret['err_msg'] = "ios message environment invalid";
            return $ret;
        }

        //检查IOS消息环境
        if ($this->message instanceof MessageIOS) {
            if ($this->environment != XingeApp::IOSENV_DEV && $this->environment != XingeApp::IOSENV_PROD) {
                $ret['err_msg'] = "ios message environment invalid";
                return $ret;
            }
        }
        $this->message->setTitle($title);
        $this->message->setContent($content);

        $params = [];
        $params['audience_type'] = 'account_list';
        $params['account_list'] = is_array($accounts) ? $accounts : array($accounts);
        $params['expire_time'] = $this->message->getExpireTime();
        $params['send_time'] = $this->message->getSendTime();
        if ($this->message instanceof Message) {
            $params['platform'] = 'android'; //android：安卓, ios：苹果, all：安卓&&苹果，仅支持全量推送和标签推送
            $params['multi_pkg'] = $this->message->getMultiPkg();
        }
        if ($this->message instanceof MessageIOS) {
            $params['platform'] = 'ios';
            $params['environment'] = $this->environment;
        }
        $params['message_type'] = $this->message->getType();
        $params['message'] = $this->message->toJson();
        $params['timestamp'] = time();
        $params['seq'] = time();

        return $push->callRestful($push::RESTAPI_PUSH, $params);
    }
}