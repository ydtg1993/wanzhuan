<?php
/**
 * Created by PhpStorm.
 * User: donggege
 * Date: 2018/11/19
 * Time: 14:23
 */
return [
    
    'websocket' => [
        'host' => env('WEBSOCKET_HOST', '0.0.0.0'),
        'port' => env('WEBSOCKET_PORT', '9501'),
        'worker_num' => env('WEBSOCKET_WORKER_NUM', 4),
        'daemonize' => env('WEBSOCKET_DAEMONIZE', 0),//1,0
        'log_file' => env('WEBSOCKET_LOG_FILE', storage_path('logs/swoole.log')),
        'max_request' => env('WEBSOCKET_MAX_REQUEST', 100),
        'dispatch_mode' => env('WEBSOCKET_DISPATCH_MODE', 2),
        'task_worker_num' => env('WEBSOCKET_TASK_WORKER_NUM', 4),
        'debug_mode' => env('WEBSOCKET_DEBUG_MODE', 1)
    ],
    
    'tcpscoket' => [

    ],
];