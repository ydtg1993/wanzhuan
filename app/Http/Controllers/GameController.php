<?php

namespace App\Http\Controllers;

use App\Http\Traits\OrderStatus;
use App\Libraries\helper\Helper;
use App\Models\Game;
use App\Models\GameStatus;
use App\Models\GrabbedMaster;
use App\Models\Master;
use App\Models\MasterGameOrder;
use App\Models\MasterRange;
use App\Models\Ticket;
use App\Models\PlayOrder;
use App\Models\NormalOrder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Yansongda\Pay\Pay;

require_once __DIR__ . '/../../Libraries/cmq-sdk/cmq_api.php';


class GameController extends Controller
{
    use OrderStatus;
    protected $redis;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取游戏信息
     * @author yaooo
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function gameList(Request $request)
    {
        if (!$request->has('type') || !$request->input('type')) {
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`type`参数或参数为空')->Code(4000);
        }

        try {
            $type = (int)$request->input(['type']);
            $data = Game::getAll($type);
        } catch (ModelNotFoundException $modelNotFoundException) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * 获取暴鸡游戏段位付费信息
     * @author yaooo
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function getManGameLevelInfo(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        if (!$request->has('game_id') || !$request->input('game_id')) {
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`game_id`参数或参数为空')->Code(4000);
        }
        if (!$request->has('level_id') || !$request->input('level_id')) {
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`level_id`参数或参数为空')->Code(4000);
        }
        if (!$request->has('server_id') || !$request->input('server_id')) {
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`server_id`参数或参数为空')->Code(4000);
        }
        try {
            $game_id = (int)$request->input(['game_id']);
            $level_id = (int)$request->input(['level_id']);
            $server_id = (int)$request->input(['server_id']);
            $user_id = (int)$request->input(['user_id']);
            $data = DB::table('game_man_charge')->where('game_id', $game_id)->where('level_id', $level_id)->first();

            $response['data'] = json_decode(json_encode($data), true);
            $user_id = $request->input('user_id');
            $ticketList = DB::table('tickets')
                ->where('user_id', $user_id)
                ->where('play_type', 1)
                ->where('status', 1)
                ->select('id', 'type', 'limits' ,'rule')->get();
            $ticketData = [];
            foreach ($ticketList as $key => $val) {
                $tempGame = explode(",", $val->limits);
                if(in_array($game_id,$tempGame)){
                    $ticketData[] = $val;
                }
            }
            $response['data']['ticket_data'] = $ticketData;
        } catch (ModelNotFoundException $modelNotFoundException) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return response()->json($response);

    }

    /**
     * 获取暴娘游戏段位付费信息
     * @author yaooo
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function getWomanGameLevelInfo(Request $request)
    {
        if (!$request->has('game_id') || !$request->input('game_id')) {
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`game_id`参数或参数为空')->Code(4000);
        }
        if (!$request->has('server_id') || !$request->input('server_id')) {
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`server_id`参数或参数为空')->Code(4000);
        }
        try {
            $game_id = (int)$request->input(['game_id']);
            $server_id = (int)$request->input(['server_id']);
            $data = DB::table('game_woman_charge')->where('game_id', $game_id)->first();
        } catch (ModelNotFoundException $modelNotFoundException) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0, $data);
    }

    public function recreateGameOrder(Request $request)
    {

    }

    public function userGameReady(Request $request)
    {
        try {
            $statusInfo = DB::table('game_status')->where('order_id', $request->input('order_id'))->first();
            if ($statusInfo->order_status != 1) {
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1002));
            }

            DB::table('game_status')
                ->where('order_id', $request->input('order_id'))
                ->update(['user_status' => 1]);

        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0);
    }

    public function masterGameReady(Request $request)
    {
        try {
            $statusInfo = DB::table('game_status')->where('order_id', $request->input('order_id'))->first();
            if ($statusInfo->order_status != 1) {
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1002));
            }

            $order = DB::table('normal_orders')
                ->where('order_id', $request->input('order_id'))->first();

            DB::table('game_status')
                ->where('order_id', $request->input('order_id'))
                ->update(['master_status' => 1, 'game_status' => 1]);

            $masterInfo = DB::table('users')->where('id', $order->master_user_id)->first();
            $userInfo = DB::table('users')->where('id', $order->user_id)->first();

            $options['client_id'] = config('easemob.client_id');
            $options['client_secret'] = config('easemob.client_secret');
            $options['org_name'] = config('easemob.org_name');
            $options['app_name'] = config('easemob.app_name');
            $easemob = new \Easemob($options);
            $target_type = 'users';
            $target = array($userInfo->hx_id);
            $nickname = str_replace("|","",$masterInfo->nickname);
            // 订单消息
            $from = 'order';
            $content = '导师昵称：' . $nickname . '|游戏消息：' . $order->game_name . '-' . $order->server_name . '-' . $order->game_num . $order->unit;
            $content .= '|开始时间：' . date('Y-m-d H:i:s');
            $ext['title'] = '订单开始';
            $ext['type'] = '1';
            $ext['orderInfo'] = '{"order_id":"' . $order->order_id . '","user_id":"' . $order->user_id . '","master_user_id":"' . $order->master_user_id . '"}';
            $ext['redirectInfo'] = '';
            $ext['nickname'] = '订单消息';
            $ext['avatar'] = 'http://image.wanzhuanhuyu.cn/game-icon/order.png';
            $easemob->sendText($from, $target_type, $target, $content, $ext);
        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0);
    }

    /**
     * 查询订单游戏状态
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryGameStatus(Request $request)
    {
        try {
            if (!$request->has('order_id') || !$request->has('user_id')) {
                return self::$RESPONSE_CODE->Code(4000);
            }

            $data = GameStatus::getOrderInfo($request->input('order_id'), $request->input('user_id'));
            if (null == $data) {
                return self::$RESPONSE_CODE->Code(5004);
            }
            $data = $data->toArray();
            $master = User::getBasic($data['master_user_id']);
            $user = User::getBasic($data['user_id']);
            $data['user_hx_id'] = $user->hx_id;
            $order_info = [
                'level_type' => '',
                'game_name' => '',
                'server_name' => '',
                'level_name' => '',
                'unit' => '',
                'game_num' => '',
                'unit_price' => '',
                'master_user_id' => '',
                'master_hx_id' => '',
                'user_id' => '',
                'avatar' => '',
                'nickname' => '',
                'sexy'=>'',
                'master_sexy'=>'',
            ];

            if($master != null){
                $order_info['master_user_id'] = $master->id;
                $order_info['master_hx_id'] = $master->hx_id;
                $order_info['master_sexy'] = $master->sexy;
                $order_info['avatar'] = $user->avatar;
                $order_info['nickname'] = $user->nickname;
                $order_info['sexy'] = $user->sexy;
                $order_info['user_id'] = $user->id;
                $order_info['user_hx_id'] = $user->hx_id;
            }
            if (!empty($data['order_info'])) {
                $game_order_info = json_decode($data['order_info'], true);
                $order_info = array_merge($order_info,$game_order_info);
            }

            $data['order_info'] = $order_info;

        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0, $data);
    }

    public function endGame(Request $request)
    {
        try {
            DB::table('masters')
                ->where('user_id', (int)$request->input('user_id'))
                ->update(['status' => 2]);
            $game = DB::table('game_status')->where('order_id', $request->input('order_id'))->first();
            if($game){
                DB::table('game_status')
                    ->where('order_id', $game->order_id)
                    ->update(['game_status' => 2,'order_status' => 3]);
            }
            $gameResult = $request->input('data');
            $order = DB::table('normal_orders')
                ->where('order_id', $game->order_id)->first();
            $play_count = 0;
           
            foreach ($gameResult as $key => $item) {
                if ($item == 1) {
                    $play_count++;
                }
            }
            $play_count = min($play_count, $order->game_num);
            DB::table('normal_orders')
                ->where('order_id', $game->order_id)
                ->update(['game_result' => json_encode($gameResult), 'status' => 2, 'game_status' => 1, 'order_status' => 2, 'real_game_num' => $play_count,'updated_at'=>time()]);
            $income = 0;
            $income = $play_count * $order->unit_price;
            $data = [
                'sum' => $income
            ];
            $now = time();
            $refund_sum = 0;
            //计算退款金额
            if($play_count != $order->game_num ){
                if($order->pay_sum > 0){
                    if($play_count * $order->unit_price > $order->ticket_sum){
                        $refund_sum = $order->pay_sum -( $play_count * $order->unit_price - $order->ticket_sum + $order->service_price);
                    }else{
                        $refund_sum = $order->pay_sum;
                    }
                    $userTransactionSql = "insert into user_transaction(user_id,order_id,money,`title`,`desc`,`type`,status,created_at)values ({$order->user_id},'{$order->order_id}',{$refund_sum},'订单退款','订单退款',2,1,{$now})";
                    DB::insert($userTransactionSql);
                    $updateWalletSql = "UPDATE wallets SET cash = cash + {$refund_sum} WHERE user_id = {$order->user_id}";
                    DB::update($updateWalletSql);

                    $new_pay_sum = $order->pay_sum - $refund_sum;
                    DB::table('normal_orders')
                        ->where('order_id', $request->input('order_id'))
                        ->update(['back_sum' => $refund_sum, 'pay_sum' => $new_pay_sum, 'back_type' => 1, 'back_status' => 1,'updated_at'=>time()]);
                } 
            }
            if($income > 0){
                $updateMasterWalletSql = "UPDATE wallets SET cash = cash + {$income} WHERE user_id = {$order->master_user_id}";
                DB::update($updateMasterWalletSql);

                $userMasterTransactionSql = "insert into user_transaction(user_id,order_id,money,`title`,`desc`,`type`,status,created_at)values ({$order->master_user_id},'{$order->order_id}',{$income},'订单收入','订单收入',6,1,{$now})";
                DB::insert($userMasterTransactionSql);
            }
            $game_order_count = $game_hour_count = 0;
            //
            $order_count = DB::table('normal_orders')->where('master_user_id', $request->input('user_id'))->where('status', 2)->where('game_status', 1)->count();
            DB::table('masters')
                ->where('user_id', (int)$request->input('user_id'))
                ->update(['order_count' => $order_count]);
            //
            $game_id = $order->game_id;
            $game_order_count = DB::table('normal_orders')->where('master_user_id', $request->input('user_id'))->where('game_id', $game_id)->where('status', 2)->where('game_status', 1)->count();
            $game_hour_count = DB::table('normal_orders')->where('master_user_id', $request->input('user_id'))->where('game_id', $game_id)->where('status', 2)->where('game_status', 1)->where('unit', '小时')->sum('game_num');
            $real_game_count = DB::table('normal_orders')->where('master_user_id', $request->input('user_id'))->where('game_id', $game_id)->where('status', 2)->where('game_status', 1)->where('unit', '局')->sum('real_game_num');

            DB::table('skills')
                ->where('master_user_id', (int)$request->input('user_id'))
                ->where('game_id', $game_id)
                ->update(['now_count' => $game_order_count,'accumulation' => $game_hour_count,'game_count' => $real_game_count]);

            DB::table('master_game_range')
                ->where('master_id', (int)$request->input('user_id'))
                ->where('game_id', $game_id)
                ->update(['order_count' => $game_order_count,'service_time' => $game_hour_count]);

            $masterInfo = DB::table('users')->where('id', (int)$request->input('user_id'))->first();
            $userInfo = DB::table('users')->where('id', $order->user_id)->first();

            $options['client_id'] = config('easemob.client_id');
            $options['client_secret'] = config('easemob.client_secret');
            $options['org_name'] = config('easemob.org_name');
            $options['app_name'] = config('easemob.app_name');
            $easemob = new \Easemob($options);
            $target_type = 'users';
            $target = array($userInfo->hx_id);
            $nickname = str_replace("|","",$masterInfo->nickname);
            // 订单消息
            $from = 'order';
            $content = '导师昵称：' . $nickname . '|游戏消息：' . $order->game_name . '-' . $order->server_name . '-' . $order->game_num . $order->unit;
            $content .= '|结束时间：' . date('Y-m-d H:i:s'); 
            $ext['title'] = '订单结束';
            $ext['type'] = '1';
            $ext['orderInfo'] = '{"order_id":"' . $order->order_id . '","user_id":"' . $order->user_id . '","master_user_id":"' . $order->master_user_id . '"}';
            $ext['redirectInfo'] = '';
            $ext['nickname'] = '订单消息';
            $ext['avatar'] = 'http://image.wanzhuanhuyu.cn/game-icon/order.png';
            $easemob->sendText($from, $target_type, $target, $content, $ext);
        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryGameList(Request $request)
    {
        try {
            if (!$request->has('user_id')) {
                return self::$RESPONSE_CODE->Code(4000);
            }
            $user_id = $request->input('user_id');
            $page = $request->input('page');

            if($page <= 1){
                $data = self::queryGameOneList($user_id);
                if(null == $data){
                    return self::$RESPONSE_CODE->Code(0, []);
                }
                self::filterList($data);
                return self::$RESPONSE_CODE->Code(0, $data);
            }

            $data = MasterGameOrder::getOrderPageList($user_id,$page);
            if(null == $data){
                return self::$RESPONSE_CODE->Code(0, []);
            }
            $data = $data->toArray();
            self::filterList($data);
        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->Code(5002, $e->getMessage());
        }
        return self::$RESPONSE_CODE->Code(0, $data);
    }

    private function queryGameOneList($user_id)
    {
        $list = MasterGameOrder::getOrderPageList($user_id,1);
        $personal_list = MasterGameOrder::getPersonalOrderList($user_id);

        if(null != $personal_list){
            $personal_list = $personal_list->toArray();
            foreach ($personal_list as $k=>$l){
                $order_id = $l['order_id'];
                $order_info = NormalOrder::getOrderById($order_id);
                if(null == $order_info){
                    unset($personal_list[$k]);
                }
            }
        }

        if(null != $list){
            $list = $list->toArray();
            foreach ($list as $k=>$l){
                $order_info = (array)json_decode($l['order_info']);
                $game_id = (int)$order_info['game_id'];
                $master_range = MasterRange::getRange($user_id,$game_id);
                $master_level = $master_range->master_level;
                if($master_level < $order_info['level_type']){
                    unset($list[$k]);
                }//TODO
            }
        }

        $data = array_merge((array)$personal_list,(array)$list);
        return $data;
    }

    private static function filterList(&$data)
    {
        foreach ($data as &$item) {
            $item['order_info'] = json_decode($item['order_info'], true);
        }
    }
}