<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/24 0024
 * Time: 下午 2:39
 */

namespace App\Http\Controllers;

use App\Http\Traits\OrderStatus;
use App\Libraries\helper\Helper;
use App\Models\GameStatus;
use App\Models\GrabbedMaster;
use App\Models\Master;
use App\Models\MasterGameOrder;
use App\Models\NormalOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 抢单
 * Class GrabbedOrder
 * @package App\Http\Controllers
 */
class GrabbedOrder extends Controller
{
    use OrderStatus;
    protected $redis;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function grab(Request $request)
    {
        try {
            if(!$request->has('user_id') || !$request->has('order_id')){
                return self::$RESPONSE_CODE->code(4000);
            }
            $user_id = $request->input('user_id');
            $order_id = $request->input('order_id');
            $master = Master::masterInfo($user_id);

            $grabbedInfo = GrabbedMaster::getOrdersById($order_id);
            $count = GrabbedMaster::checkGrabbedTotal($order_id);
            $order = NormalOrder::getOrderById($order_id);

            if(empty($order)){
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(2010));
            }
            $order = $order->toArray();
            $grabbedInfo = $grabbedInfo->toArray();
            if($order['order_status'] == 2){//订单结束
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1004));
            }
            if($order['game_status'] == 1){//订单结束
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1004));
            }
            if($order['status'] >= 2){//订单结束
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1002));
            }

            if($order['master_user_id'] == $user_id){//已经抢到订单
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1000));
            }

            $checkGrabbed = Helper::multiQuery2Array($grabbedInfo,['user_id'=>$user_id]);
            if(count($checkGrabbed)){
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1006));
            }

            if ($master['status'] != 2) {
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1001));
            }

            if ($order['status'] != 1) {
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(2001));
            }

            //已经有人接单了
            if ($order['master_user_id'] != 0) {
                //更新该订单在导师列表的信息
                MasterGameOrder::updataOrder($order_id,1);
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1003));
            }

            if ($order['master_type'] == 2) {//小姐姐接单
                if ($count >= 3) {
                    return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1003));
                }
            } else {
                if ($count > 0) {
                    return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1003));
                }
            }

            $userInfo = User::getBasic($user_id);

            $data = [
                'user_id' => $order['user_id'],
                'order_id' => $order_id,
                'master_id' => $user_id,
                'master_info' => json_encode([
                    'nickname' => $userInfo->nickname,
                    'sexy' => $userInfo->sexy,
                    'avatar' => $userInfo->avatar,
                    'location' => $userInfo->location,
                    'hx_id' => $userInfo->hx_id,
                ])
            ];
            $res = DB::table('grabbed_master')->where('user_id', $order['user_id'])->where('master_id', $user_id)->where('order_id', $order_id)->first();
            if(!$res){
                DB::table('grabbed_master')->insert($data);
            }
        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->code(4000);
        }

        return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1000));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryGrabbedMaster(Request $request)
    {
        try {
            if(!$request->has('order_id') || !$request->has('user_id')){
                return self::$RESPONSE_CODE->Code(4000);
            }

            $data = GrabbedMaster::getOrder($request->input('order_id'),$request->input('user_id'));

            if (!empty($data)) {
                foreach ($data as &$item) {
                    $item->master_info = json_decode($item->master_info, true);
                }
            }

        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * 选择
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectGrabbedMaster(Request $request)
    {
        try {
            if(!$request->has('order_id') || !$request->has('master_id') || !$request->has('user_id')){
                return self::$RESPONSE_CODE->Code(4000);
            }
            $master_id = (int)$request->input('master_id');
            $order_id = $request->input('order_id');
            $order = NormalOrder::getOrderById($order_id);
            if ($order->status >= 2) {
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1005));
            }

            DB::beginTransaction();
            
            $normal_order_info = NormalOrder::getOrderById($order_id);
            if(null == $normal_order_info){
                return self::$RESPONSE_CODE->setMsg('normal_order为空')->Code(4000);
            }
            $normal_order_info = $normal_order_info->toArray();

            $order_info = [
                'level_type'=>isset($normal_order_info['level_type']) ? $normal_order_info['level_type'] : '',
                'game_name'=>isset($normal_order_info['game_name']) ? $normal_order_info['game_name'] : '',
                'server_name'=>isset($normal_order_info['server_name']) ? $normal_order_info['server_name'] : '',
                'level_name'=>isset($normal_order_info['level_name']) ? $normal_order_info['level_name'] : '',
                'unit'=>isset($normal_order_info['unit']) ? $normal_order_info['unit'] : '',
                'game_num'=>isset($normal_order_info['game_num']) ? $normal_order_info['game_num'] : '',
                'unit_price'=>isset($normal_order_info['unit_price']) ? $normal_order_info['unit_price'] : '',
                'hx_id' => isset($normal_order_info['hx_id']) ? $normal_order_info['hx_id'] : '',
            ];

            $masterInfo = Master::masterInfo($master_id);
            if($masterInfo->status != 2){
                return self::$RESPONSE_CODE->setMsg('导师不在接单中')->Code(4000);
            }

            if($normal_order_info['order_status'] != 0){
                return self::$RESPONSE_CODE->setMsg('订单不是准备中')->Code(4000);
            }

            $result = NormalOrder::updateOrder($request->input('order_id'),
                ['master_user_id' => $master_id,
                'order_status' => 1]);

            if(!$result){
                DB::rollBack();
                return self::$RESPONSE_CODE->setMsg('normal_order没有修改')->Code(5005);
            }
            //导师游戏中
            $result = Master::updateInfo([
                'user_id' => $master_id,
                'status' => 3,
            ]);

            if(!$result){
                DB::rollBack();
                return self::$RESPONSE_CODE->setMsg('master没有修改')->Code(5005);
            }

            $result = GameStatus::add([
                'user_id' => (int)$request->input('user_id'),
                'master_user_id' => $master_id,
                'order_id' => $order_id,
                'created_at' => $_SERVER['REQUEST_TIME'],
                'order_info' => json_encode($order_info),
            ]);

            if(!$result){
                DB::rollBack();
                return self::$RESPONSE_CODE->setMsg('game_status没有修改')->Code(5005);
            }
            //订单已经被抢
            $result = MasterGameOrder::updataOrder($order_id,1);

            if(!$result){
                DB::rollBack();
                return self::$RESPONSE_CODE->setMsg('master_gameorder_list没有修改')->Code(5005);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return self::$RESPONSE_CODE->Code(5002);
        }

        DB::commit();
        return self::$RESPONSE_CODE->Code(0);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryGrabbedMasterSelectResult(Request $request)
    {
        try {
			if(!$request->has('order_id')){
				return self::$RESPONSE_CODE->Code(4000);
			}

			$order = NormalOrder::getOrderById($request->input('order_id'));
			if ($order->master_user_id == 0) {
				$data = [
					'result' => 0
				];
			} else if ($order->master_user_id != $request->input('user_id')) {
				$data = [
					'result' => 2
				];
			} else {
				$data = [
					'result' => 1,
					'user_id' => $order->user_id
				];
			}
		} catch (\Exception $e) {
			return self::$RESPONSE_CODE->Code(5002);
		}

		return self::$RESPONSE_CODE->Code(0, $data);
    }
}