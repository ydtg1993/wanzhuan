<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PersonalOrder\CreatePersonalOrder;
use App\Http\Traits\OrderStatus;
use App\Http\Traits\RangeSelect;
use App\Models\GameStatus;
use App\Models\Master;
use App\Models\MasterGameOrder;
use App\Models\MasterRange;
use App\Models\NormalOrder;
use App\Models\User;
use Illuminate\Http\Request;

class PersonalOrderController extends Controller
{
    use OrderStatus;
    use RangeSelect;

    /**
     * 专属导师下单
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPersonalOrder(Request $request)
    {
        if(!$request->has('user_id')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('data.game_id') || !$request->input('data.game_id')) {
            return self::$RESPONSE_CODE->setMsg('缺少`game_id`参数或参数为空')->Code(4000);
        }
        if (!$request->has('data.server_id') || !$request->input('data.server_id')) {
            return self::$RESPONSE_CODE->setMsg('缺少`server_id`参数或参数为空')->Code(4000);
        }
        if (!$request->has('data.order_type') || !$request->input('data.order_type')) {
            return self::$RESPONSE_CODE->setMsg('缺少`order_type`参数或参数为空')->Code(4000);
        }
        if (!$request->has('data.ticket_id')) {
            return self::$RESPONSE_CODE->setMsg('缺少`ticket_id`参数或参数为空')->Code(4000);
        }
        if (!$request->has('data.unit') || !$request->input('data.unit')) {
            return self::$RESPONSE_CODE->setMsg('缺少`unit`参数或参数为空')->Code(4000);
        }
        if (!$request->has('data.game_num') || !$request->input('data.game_num')) {
            return self::$RESPONSE_CODE->setMsg('缺少`game_num`参数或参数为空')->Code(4000);
        }
        if (!$request->has('data.pay_type')) {
            return self::$RESPONSE_CODE->setMsg('缺少`pay_type`参数')->Code(4000);
        }
        if (!$request->has('data.master_id')) {
            return self::$RESPONSE_CODE->setMsg('缺少`master_id`参数')->Code(4000);
        }

        $requestData = $request->input('data');
        $master_id = intval($requestData['master_id']);
        $user_id = (int)$request->input('user_id');

        if ($requestData['game_num'] < 1 || $requestData['game_num'] > 3) {
            return self::$RESPONSE_CODE->setMsg('请求出错，`game_num`参数不在允许的范围')->Code(4000);
        }

        $master_info = Master::masterInfo($master_id);
        if ($master_info->status != 2) {
            return self::$RESPONSE_CODE->setMsg('导师当前无法接单')->Code(4000);
        }

        $master_range = MasterRange::getRange($master_id,$requestData['game_id']);
        $requestData['level_type'] = $master_range->master_level;
        if($master_info->sex == RangeSelect::$FA_MALE){
            $requestData['master_type'] = 2;
        }else{
            $requestData['master_type'] = 1;
        }

        if (false == isset($requestData['level_id'])) {
            $requestData['level_id'] = 0;
        }

        try {
            $data = (new CreatePersonalOrder())->index($user_id, $requestData);
            return self::$RESPONSE_CODE->Code(0,$data);
        }catch (\Exception $e){
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->Code(5002);
        }
    }

    /**
     * 导师接单
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptPersonalOrder(Request $request)
    {
        try {
            if(!$request->has('user_id') || !$request->has('order_id')){
                return self::$RESPONSE_CODE->code(4000);
            }
            $order_id = $request->input('order_id');

            $master = Master::masterInfo($request->input('user_id'));
            if ($master->status != 2) {
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1001));
            }

            $normal_order_info = NormalOrder::getOrderById($order_id);
            if(null == $normal_order_info){
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(2001));
            }

            if($normal_order_info['order_status'] == 2){//订单结束
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1004));
            }
            if($normal_order_info['game_status'] == 1){//订单结束
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1004));
            }
            if($normal_order_info['status'] >= 2){//订单结束
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1002));
            }

            $normal_order_info = $normal_order_info->toArray();

            \DB::beginTransaction();

            $result = MasterGameOrder::updataOrder($order_id,1);
            if(!$result){
                \DB::rollBack();
                return self::$RESPONSE_CODE->setMsg('master_gameorder_list没有修改')->Code(5005);
            }

            $result = GameStatus::add([
                'user_id' => $normal_order_info['user_id'],
                'master_user_id' => $normal_order_info['master_user_id'],
                'order_id' => $order_id,
                'created_at' => $_SERVER['REQUEST_TIME'],
                'order_info' => json_encode([
                    'level_type'=>isset($normal_order_info['level_type']) ? $normal_order_info['level_type'] : '',
                    'game_name'=>isset($normal_order_info['game_name']) ? $normal_order_info['game_name'] : '',
                    'server_name'=>isset($normal_order_info['server_name']) ? $normal_order_info['server_name'] : '',
                    'level_name'=>isset($normal_order_info['level_name']) ? $normal_order_info['level_name'] : '',
                    'unit'=>isset($normal_order_info['unit']) ? $normal_order_info['unit'] : '',
                    'game_num'=>isset($normal_order_info['game_num']) ? $normal_order_info['game_num'] : '',
                    'unit_price'=>isset($normal_order_info['unit_price']) ? $normal_order_info['unit_price'] : '',
                    'hx_id' => isset($normal_order_info['hx_id']) ? $normal_order_info['hx_id'] : '',
                ]),
            ]);
            if(!$result){
                \DB::rollBack();
                return self::$RESPONSE_CODE->setMsg('game_status没有修改')->Code(5005);
            }

            //导师游戏中
            Master::updateInfo([
                'user_id' => $normal_order_info['master_user_id'],
                'status' => 3,
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->code(4000);
        }

        \DB::commit();
        return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1000));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryPersonalOrder(Request $request)
    {
        try {
            if(!$request->has('order_id')){
                return self::$RESPONSE_CODE->code(4000);
            }
            $data = MasterGameOrder::getInfoById($request->input('order_id'));
            if($data == null){
                return self::$RESPONSE_CODE->code(0, [
                    'result' => 0
                ]);
            }

            if ($data->order_status == 0) {
                $data = [
                    'result' => 0
                ];
            } else if ($data->order_status == 2) {
                $data = [
                    'result' => 2
                ];
            } else {
                $user_info = User::getBasic($data->master_id);
                $data = [
                    'result' => 1,
                    'master_info' => [
                        'master_id'=>$data->master_id,
                        'hx_id'=>$user_info->hx_id,
                        'sexy'=>$user_info->sexy,
                    ]
                ];
            }

        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->code(5002);
        }

        return self::$RESPONSE_CODE->code(0, $data);
    }

}