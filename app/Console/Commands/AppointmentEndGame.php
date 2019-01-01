<?php

namespace App\Console\Commands;

use App\Http\Common\RedisDriver;
use App\Libraries\Swoole\WebSocktHandler;
use App\Models\AppointmentGrabOrderModel;
use App\Models\AppointmentOrderModel;
use App\Models\AppointmentStatusModel;
use App\Models\Sociaty;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

date_default_timezone_set('PRC');

class AppointmentEndGame extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointmentEndGame';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '约玩结束游戏';


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
        $orders = AppointmentOrderModel::getAllWhere(['game_status' => 1, 'order_status' => 1]);

        foreach ($orders as $order) {
            $time = time();
            if ($order['game_start_at'] > 0 && ($order['game_start_at'] + 60 * $order['service_time']) <= $time) {
                try {
                    \DB::beginTransaction();
                    $accept_user = User::getBasic($order['accept_user_id']);
                    $sociaty_info = Sociaty::getInfoWhere(['id' => $accept_user->sociaty_id]);
                    $proportions = $sociaty_info->appointment_proportions;
                    $get_sum = $order['pay_sum'] * $proportions;

                    $result = AppointmentOrderModel::upInfoWhere([
                        'game_status' => 2,
                        'user_id' => $order['user_id'],
                        'accept_user_get_sum' => max($get_sum, 0),
                        'proportions' => $proportions,
                        'game_end_at' => $time,
                    ], ['order_id' => $order['order_id']]);
                    if (!$result) {
                        \DB::rollBack();
                        continue;
                    }

                    //接单人收钱
                    Wallet::addUserTransaction([
                        'user_id' => $order['accept_user_id'],
                        'order_id' => $order['order_id'],
                        'money' => $get_sum,
                        'title' => '收入',
                        'desc' => '呼叫收入',
                        'type' => 10,
                        'status' => 1,
                        'created_at' => $time
                    ]);

                    AppointmentGrabOrderModel::delInfoWhere(['order_id' => $order['order_id']]);
                    AppointmentStatusModel::delInfoWhere(['order_id' => $order['order_id']]);

                    if($order['game_status'] != 2) {
                        info($order);
                        $redisUserFdKey = 'user:fd:' . $order['user_id'];

                        $redis = RedisDriver::getInstance()->redis;
                        $res = $redis->exists($redisUserFdKey);
                        if ($res) {
                            $userSocketInfo = $redis->hgetall($redisUserFdKey);
                            $client = new swoole_client(SWOOLE_SOCK_TCP);
                            $client->set([
                                'open_eof_split' => true,
                                'package_eof' => "\r\n",
                            ]);
                            $client->connect($userSocketInfo['ip'], 9044, 0.5);
                            $sendData['type'] = 'endorder';
                            $sendData['data'] = [
                                'user_id' => $order['user_id'],
                                'order_id' => $order['order_id'],
                                'pay_sum' => $order['pay_sum']
                            ];
                            $sendPackage = (string)json_encode($sendData) . "\r\n";
                            $res = $client->send($sendPackage);
                            $client->close();
                        }
                    }
                } catch (\Exception $e) {
                    \DB::rollBack();
                    continue;
                }
                \DB::commit();
            }
        }
    }
}
