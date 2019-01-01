<?php

namespace App\Http\Common;

use Illuminate\Http\Request;

/**
 * Class LogController
 * @package App\Http\Controllers
 */
class LogController
{
    public function addLog($data)
    {
        $request_url = isset($_SERVER['REQUEST_URI'])? $_SERVER['REQUEST_URI']:'';
        $path = $this->mkDir($request_url,'server_add');
        $file = $path . DIRECTORY_SEPARATOR . 'error.log';

        file_put_contents($file, date('Y-m-d H;i:s')."\t".json_encode($data) . PHP_EOL, FILE_APPEND);
    }

    public function resposeLog($data)
    {
        $request_url = isset($_SERVER['REQUEST_URI'])? $_SERVER['REQUEST_URI']:'';
        $path = $this->mkDir($request_url);
        $file = $path . DIRECTORY_SEPARATOR . 'error.log';

        file_put_contents($file, date('Y-m-d H;i:s')."\t".json_encode($data) . PHP_EOL, FILE_APPEND);
    }

    private function mkDir($request_url, $name = 'server')
    {
        $y = date('Y');
        $m = date('m');
        $d = date('d');
        $path = base_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR .
            $name . DIRECTORY_SEPARATOR . $y . DIRECTORY_SEPARATOR . $m . DIRECTORY_SEPARATOR . $d .
            DIRECTORY_SEPARATOR . str_replace("\\", "/", preg_replace("/\?.*/","",$request_url));

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

}