<?php

namespace App\Console\Commands;

use App\Libraries\Swoole\WebSocktHandler;
use Illuminate\Console\Command;
use Swoole\Process;

class Socket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:socket {type=tcp} {--Q|action=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'swoole socket server';

    /**
     * 支持socket类型 目前支持2种
     *
     * @var array
     */
    protected $type = ['tcp', 'web'];

    /**
     * 支持的动作 开始 停止 重新启动 重新加载
     *
     * @var array
     */
    protected $actions = ['start', 'stop', 'restart', 'reload'];

    /**
     * 进程pid
     *
     * @var null
     */
    protected $pid = null;

    //socket服务
    protected $server = null;
    //处理类
    protected $handler = null;

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
        if (!in_array($this->argument('type'), $this->type)) {
            $this->info("不支持的`socket`类型!\n仅支持 `tcp` , `web`");
            die(0);
        }
        $this->type = $this->argument('type');
        $action = $this->option('action');
        if (!in_array($action, $this->actions)) {
            $this->info("不支持的动作!\n仅支持 `start` `stop` `restart` `reload`");
            die(0);
        }
        $this->$action();
        die(0);
    }

    /**
     * 绑定回调,启动服务
     */
    protected function start()
    {
        if ($this->type == 'tcp') {
            $this->server = new \swoole_server(config('swoole.tcpsocket.host'), config('swoole.tcpsocket.host'));
            $this->server->set(array_except(config('swoole.tcpsocket'), ['host', 'port']));

        } elseif ($this->type == 'web') {
            $this->server = new \swoole_websocket_server(config('swoole.websocket.host'), config('swoole.websocket.port'));
            $this->server->set(array_except(config('swoole.websocket'), ['host', 'port']));

        }
        $this->server->on('start', function () {
            try {
                $pidFile = base_path('storage/logs/' . $this->type . 'socket.pid');
                file_put_contents($pidFile, $this->server->master_pid);
            } catch (\Throwable $e) {
                $this->info('The PID write failed');
                exit(0);
            }
            swoole_set_process_name($this->type . 'socket_master_process');
        });
        /**
         * 此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用。
         * onWorkerStart/onStart是并发执行的，没有先后顺序
         */
        $this->server->on('workerstart', function () {
            try {
                $this->handler = WebSocktHandler::getInstance();
            } catch (\Exception $e) {
                $this->info($e->getMessage());
                $this->info('进程退出！');
                $this->server->shutdown();
            }

        });

        /**
         * 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。
         */
        $this->server->on('open', function (\swoole_websocket_server $server, \swoole_http_request $req) {
            $this->handler->open($server, $req);
        });

        if ($this->type == 'tcp') {
            /**
             * 有新的连接进入时，在worker进程中回调
             */
            $this->server->on('connect', function (\swoole_server $server, int $fd, int $reactorId) {
                $this->handler->connect($server, $fd, $reactorId);
            });
        }

        /**
         * 当服务器收到来自websocket客户端的数据帧时会回调此函数。
         */
        $this->server->on('message', function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) {
            $this->handler->message($server, $frame);
        });
        /**
         * tcp服务器 接收到数据时回调此函数，发生在worker进程中。
         */
        $this->server->on('receive', function (\swoole_server $server, int $fd, int $reactor_id, string $data) {
            $this->handler->receive($server, $fd, $reactor_id, $data);
        });
        /**
         * 在task_worker进程内被调用。
         * worker进程可以使用swoole_server_task函数向task_worker进程投递新的任务。
         * 当前的Task进程在调用onTask回调函数时会将进程状态切换为忙碌，这时将不再接收新的Task，当onTask函数返回时会将进程状态切换为空闲然后继续接收新的Task。
         */
        $this->server->on('task', function (\swoole_server $server, int $task_id, int $src_worker_id, $data) {
            $this->handler->task($server, $task_id, $src_worker_id, $data);
        });
        /**
         * 当worker进程投递的任务在task_worker中完成时，task进程会通过swoole_server->finish()方法将任务处理的结果发送给worker进程
         */
        $this->server->on('finish', function (\swoole_server $server, int $task_id, string $data) {
            $this->handler->finish($server, $task_id, $data);
        });
        /**
         * 客户端连接关闭后，在worker进程中回调此函数。
         */
        $this->server->on('close', function (\swoole_server $server, int $fd, int $reactorId) {
            $this->handler->close($server, $fd, $reactorId);
        });

        $this->info('服务启动成功!');
        $this->server->start();
    }

    /**
     * 停止服务
     */
    protected function stop()
    {
        $pid = $this->getPid();

        if (!$this->isRunning($pid)) {
            $this->error('服务没有运行!');
            die(0);
        }

        $isRunning = $this->killProcess($pid, SIGTERM, 15);

        if ($isRunning) {
            $this->error('服务停止失败!');
            die(0);
        }
        $this->info('服务停止成功!');
    }

    /**
     * 重新启动
     */
    protected function restart()
    {
        $pid = $this->getPid();

        if ($this->isRunning($pid)) {
            $this->stop();
        }
        $this->info('服务重启成功!');
        $this->start();
    }

    /**
     * 重新载入
     */
    protected function reload()
    {
        $pid = $this->getPid();

        if (!$this->isRunning($pid)) {
            $this->error('服务没有运行!');
            exit(1);
        }
        $isRunning = $this->killProcess($pid, SIGUSR1);

        if (!$isRunning) {
            $this->error('重载失败!');
            exit(1);
        }

        $this->info('重新加载成功!');
    }

    /**
     * Get pid.
     *
     * @return int|null
     */
    protected function getPid()
    {
        $pid = null;

        $path = base_path('storage/logs/' . $this->type . 'socket.pid');

        if (file_exists($path)) {
            $pid = (int)file_get_contents($path);

            if (!$pid && file_exists($path)) {
                unlink($path);
            }
        }
        return $pid;
    }

    /**
     * If Swoole process is running.
     *
     * @param int $pid
     * @return bool
     */
    protected function isRunning($pid)
    {
        if (!$pid) {
            return false;
        }
        try {
            return Process::kill($pid, 0);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Kill process.
     *
     * @param int $pid
     * @param int $sig
     * @param int $wait
     * @return bool
     */
    protected function killProcess($pid, $sig, $wait = 0)
    {
        Process::kill($pid, $sig);
        if ($wait) {
            $start = time();

            do {
                if (!$this->isRunning($pid)) {
                    break;
                }
                usleep(100000);
            } while (time() < $start + $wait);
        }

        return $this->isRunning($pid);
    }
}
