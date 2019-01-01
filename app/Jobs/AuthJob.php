<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

//use Illuminate\Support\Carbon;

class AuthJob extends Job
{
    protected $request;

    /**
     * Create a new job instance.
     *
     * @param \Illuminate\Http\Request;
     * @return void
     */
    public function __construct(array $request)
    {
        $this->request = $request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (DB::table('identities')->where('number', $this->request['idcard_number'])->exists()) {

        } else {
            $sign = ai_auth(config('cloud.cloud_app_id'), config('cloud.cloud_secret_id'), config('cloud.cloud_secret_key'));

            // 构造【OCR-身份验证】请求体
            $body = array(
                'appid' => config('cloud.cloud_app_id'),
                'card_type' => 0,
                'url_list' => $this->request['file_path']
            );

            $client = new Client([
                'base_uri' => 'https://recognition.image.myqcloud.com',
                'timeout' => 7.0,
            ]);

            try {
                // 设置【OCR-身份验证】请求
                $res1 = $client->post('https://recognition.image.myqcloud.com/ocr/idcard', [
                    'json' => $body,
                    'headers' => [
                        'host' => 'recognition.image.myqcloud.com',
                        'Authorization' => $sign,
                        'Content-Tyope' => 'application/json'
                    ]
                ]);

                $res1 = json_decode($res1->getBody()->getContents(), true)['result_list']['0'];

                if (!$res1 || ($res1['code'] != 0)) {

                } elseif ($this->request['idcard_name'] != $res1['data']['name'] || $this->request['idcard_number'] != $res1['data']['id']) {

                } else {
                    // 构造【人脸核身】请求体
                    $body = array(
                        'appid' => config('cloud.cloud_app_id'),
                        'idcard_number' => $this->request['idcard_number'],
                        'idcard_name' => $this->request['idcard_name'],
                        'url' => $this->request['file_path']
                    );
                    // 设置【人脸核身】请求
                    $res2 = $client->post('https://recognition.image.myqcloud.com/face/idcardcompare', [
                        'json' => $body,
                        'headers' => [
                            'host' => 'recognition.image.myqcloud.com',
                            'Authorization' => $sign,
                            'Content-Tyope' => 'application/json'
                        ]
                    ]);

                    $body = null;
                    $client = null;
                    $sign = null;

                    $res2 = json_decode($res2->getBody()->getContents(), true);
                    if (!$res2 || ($res2['code'] != 0)) {

                    } elseif ($res2['data']['similarity'] < 90) {

                    } else {
                        $now_time = time();

                        // 更新用户身份认证信息
                        DB::table('identities')->insert([
                            'user_id' => $this->request['user_id'],
                            'number' => $this->request['idcard_number'],
                            'realname' => $this->request['idcard_name'],
                            'img_path' => $this->request['file_path'],
                            'sexy' => $res1['data']['sex'],
                            'birth' => $res1['data']['birth'],
                            'created_at' => $now_time,
                            'updated_at' => $now_time,
                        ]);
                        DB::table('users')->where('id', $this->request['user_id'])->update([
                            'sexy' => $res1['data']['sex'],
                            'birth' => $res1['data']['birth'],
                            'updated_at' => $now_time
                        ]);
                    }
                }

            } catch (RequestException $requestException) {
                //
            } catch (ModelNotFoundException $modelNotFoundException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }
    }
}
