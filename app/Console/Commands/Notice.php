<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Qcloudcmq\Account;

require_once __DIR__ . '/../../Libraries/cmq-sdk/cmq_api.php';

class Notice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get cmq message queue handle and push notice to terminal';

    /**
     * 支持的动作 开始 停止 重新启动 重新加载
     *
     * @var array
     */
    protected $queues;

    protected $cmq;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $secretId = config('cloud.cloud_secret_id');
        $secretKey = config('cloud.cloud_secret_key');
        $endPoint = config('cloud.cd_end_point');
        /**
         * 需要处理的队列
         */
        $queueList = ['xg_push'];

        $this->cmq = new \Qcloudcmq\Account($endPoint, $secretId, $secretKey);

        foreach ($queueList as $item) {
            $this->queues[] = $this->cmq->get_queue(CMQ_PRENAME . $item);
        }
    }

    /**
     *
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*while (true) {
            try {

            } catch (\Exception $e) {
                throw new \Exception('处理失败');
            }
        }*/
        die(0);
    }
}
