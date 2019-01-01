<?php
/**
 * Created by PhpStorm.
 * User: donggege
 * Date: 2018/11/6
 * Time: 15:00
 */

namespace App\Libraries\Applet;

use App\Http\Controllers\LogController;

class AppletServer
{
    private $config;

    public function __construct()
    {
        $this->config = config('applet');
    }

    /**
     * 检验数据的真实性，返回解密后的明文.
     *
     * @param $data
     * @return bool|string
     */
    public function dataDecryption(array $data)
    {
        try {
            $aesKey = base64_decode(array_get($data, 'session_key'));

            $aesIV = base64_decode(array_get($data, 'iv'));

            $aesCipher = base64_decode(array_get($data, 'encrypted_data'));

            $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

            $dataObj = json_decode($result);
            (new LogController())->resposeLog($dataObj);
            (new LogController())->resposeLog($dataObj);
            
            if ($dataObj == NULL || $dataObj->watermark->appid != array_get($this->config, 'APPID')) {
                return false;
            }
            return $dataObj;
        } catch (\Exception $e) {
            (new LogController())->resposeLog($e->getMessage());
        }

    }
}