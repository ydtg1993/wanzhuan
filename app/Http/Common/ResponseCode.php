<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 下午 7:26
 */

namespace App\Http\Common;

class ResponseCode
{
    private static $config = null;

    private static $set_msg = '';

    private static $instance = null;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function Code($code,$data = array())
    {
        if(self::$config === null){
            self::$config = config('code');
        }

        if(self::$set_msg == ''){
            $msg = self::$config[$code];
        }else{
            $msg = self::$set_msg;
            self::$set_msg = '';//销毁
        }
        if($code){
            (new LogController())->resposeLog($data);
        }
        return response()->json(['code'=>$code,'msg'=>$msg,'data'=>$data],200,array('Access-Control-Allow-Origin' => '*'));
    }

    /**
     * @param $msg
     * @return $this
     */
    public function setMsg($msg)
    {
        self::$set_msg = $msg;
        return $this;
    }
}