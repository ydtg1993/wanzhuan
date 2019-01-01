<?php
/**
 * Created by PhpStorm.
 * User: donggege
 * Date: 2018/11/15
 * Time: 19:37
 */

namespace App\Libraries\Swoole;


class WebSocktHandler
{
    private static $instance;    //保存类实例的私有静态成员变量

    private function __construct()
    {
    }

    //定义私有的__clone()方法，确保单例类不能被复制或克隆
    private function __clone()
    {
    }

    //对外提供获取唯一实例的方法
    public static function getInstance()
    {
        //检测类是否被实例化
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 收到消息处理
     *
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     */
    public function message(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        info($frame->data);
        info(json_decode($frame->data,true));

        /**
         * 检查数据格式
         */
        if (is_null(json_decode($frame->data))) {
            return $server->push($frame->fd, $frame->data);
        }

        if ($server->exist($frame->fd)) {
            $server->task($frame);
        }
    }

    /**
     * webSocket 会调用
     * @param \swoole_websocket_server $server
     * @param $req
     */
    public function open(\swoole_websocket_server $server, $req)
    {
        $server->send($req->fd, \swoole_websocket_server::pack('hello'));
    }

    //tcpSocket 会调用
    public function connect($server, $fd, $reactorId)
    {
        $server->send($fd, \swoole_websocket_server::pack('hello'));
    }

    public function task(\swoole_server $server, int $task_id, int $src_worker_id, $frame)
    {
        /*$events = ['connect','keepalive','createorder','graborder','acceptorder','endorder','updateorder'];*/
        $jsonData = json_decode($frame->data, true);

        $type = array_get($jsonData, 'type');

        if (method_exists(SocketServer::class, $type)) {
            $socketServer = new SocketServer();
            call_user_func([$socketServer, $type], $server, $frame->fd, $jsonData['data']);
        }
    }

    public function finish(\swoole_server $server, $task_id, $data)
    {
        $taskData = json_decode($data, true);
        $fd = $taskData['fd'];
        $type = $taskData['type'];
        $sendData['time'] = date('Y-m-d H:i:s');

        switch ($type) {
            case 'connect':

                $sendData['type'] = 'connect';
                $sendData['data'] = ['title' => 'connect', 'message' => 'connect success', 'status' => 1];
                $sendPackage = (string)json_encode($sendData) . "\r\n";
                break;
            case 'keepalive':
                $sendData['type'] = 'keepalive';
                $sendData['data'] = ['title' => 'connect', 'message' => 'connect keep alive', 'status' => 1];
                $sendPackage = (string)json_encode($sendData) . "\r\n";
                break;
            default:
                break;
        }
        $isExist = $server->exist($fd);
        if ($isExist) {
            $server->send($fd, \swoole_websocket_server::pack($sendPackage));
        }
    }

    public function close()
    {

    }
}