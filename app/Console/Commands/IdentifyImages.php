<?php

namespace App\Console\Commands;


use App\Models\Resource;
use Illuminate\Console\Command;


date_default_timezone_set('PRC');
class IdentifyImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'identifyImages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '鉴定黄色图片';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $appid = "1257042421";
        $secret_id = "AKID9rSvHTyHUcbpz1zQr7B5oy2vfcUvANKH";
        $expired = time() + 2592000;
        $current = time();
        $rdm = rand();
        $secret_key = "yU5bw6iDnCPRJDvFjEXebLQRHbbVQCrM";

        $srcStr = 'a='.$appid.'&b='.'&k='.$secret_id.'&t='.$current.'&e='.$expired.'&r='.$rdm.'&f=';
        $signStr = base64_encode(hash_hmac('SHA1', $srcStr, $secret_key, true).$srcStr);

        $page = 1;
        while (true) {
            $resources = Resource::findListWhere([['type','=',0],['danger_class','<',1]],$page,300);
            if(empty($resources)){
                break;
            }
            foreach ($resources as $resource) {
                $path = $resource['ori_path'];
                if($path) {
                    $data = self::curlRequest('http://recognition.image.myqcloud.com/detection/porn_detect',
                        ['appid'=>'1257042421','url_list'=>[$path]],
                        ['host: recognition.image.myqcloud.com','content-type: application/json','authorization: '.$signStr]
                    );
                    $data = (array)json_decode($data,true);
                    if(isset($data['result_list'])){
                        $info = current($data['result_list']);
                        $danger_class = isset($info['data']['result']) ? (int)$info['data']['result'] : 0;
                        switch ($danger_class){
                            case 1://黄图
                                Resource::upInfoWhere(['danger_class'=>2],['id'=>$resource['id']]);
                                break;
                            case 2://疑似
                                Resource::upInfoWhere(['danger_class'=>1],['id'=>$resource['id']]);
                                break;
                            default;
                        }
                    }
                }
            }
            $page++;
        }
    }


    /**
     * @param $url
     * @param array $vars
     * @param string $method
     * @param int $timeout
     * @param bool $CA
     * @param string $cacert
     * @return int|mixed|string
     */
    static function curlRequest($url, $vars = array(),$header = array(), $method = 'POST', $timeout = 10, $CA = false, $cacert = '')
    {
        $method = strtoupper($method);
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        if ($method == 'GET' && !empty($vars)) {
            $params = is_array($vars) ? http_build_query($vars) : $vars;
            $url = rtrim($url, '?');
            if (false === strpos($url . $params, '?')) {
                $url = $url . '?' . ltrim($params, '&');
            } else {
                $url = $url . $params;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout - 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($SSL && $CA && $cacert) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else if ($SSL && !$CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($vars));
        }
        $result = curl_exec($ch);
        $error_no = curl_errno($ch);
        if (!$error_no) {
            $result = trim($result);
        } else {
            $result = $error_no;
        }

        curl_close($ch);
        return $result;
    }

}
