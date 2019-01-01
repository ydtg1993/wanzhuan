<?php

namespace App\Jobs;

use App\Models\Verify;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SmsJob extends Job
{
    protected $data = null;
    public $queue='sendSms';

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        $now_time = time();

        $sign = sms_sign($this->data['mobile'], config('cloud.sms_app_key'), $this->data['random'], $now_time);

        $body = array(
            'ext' => '',
            'extend' => '',
            'msg' => '您的注册验证码是' . $this->data['random'] . '，请于15' . '分钟内填写。如非本人操作，请忽略本短信。',
            'sig' => $sign,
            'tel' => [
                'mobile' => $this->data['mobile'],
                'nationcode' => '86'
            ],
            'time' => $now_time,
            'type' => 0
        );

        $client = new Client([
            'base_uri' => 'https://yun.tim.qq.com/v5/tlssmssvr',
            'timeout' => 2.0,
        ]);

        try {
            $res = $client->post('https://yun.tim.qq.com/v5/tlssmssvr/sendsms?sdkappid=' . config('cloud.sms_app_id') . '&random=' . $this->data['random'], [
                'json' => $body,
                'verify' => false
            ]);

            $res = json_decode($res->getBody()->getContents(), true);

            if (!$res && $res['result'] !== 0) {

            } else {
                Verify::setVerify($this->data['mobile'], $this->data['random']);
            }
        } catch (\Exception $e) {
            if ($e instanceof RequestException){

            }
        }
    }
}