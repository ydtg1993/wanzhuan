<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AccompanyOrder\CreateAccompanyOrder;
use App\Http\Controllers\AppointmentOrder\CreateAppointmentOrder;
use App\Http\Controllers\PersonalOrder\CreatePersonalOrder;
use App\Http\Traits\OrderStatus;
use App\Http\Traits\RangeSelect;
use App\Http\Traits\ServiceCharge;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\GameServer;
use App\Models\GameStatus;
use App\Models\ManCharge;
use App\Models\Master;
use App\Models\MasterGameOrder;
use App\Models\MasterRange;
use App\Models\NormalOrder;
use App\Models\User;
use App\Models\UserTransaction;
use App\Models\Wallet;
use App\Models\WomanCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class OrderController extends Controller
{
    use OrderStatus;
    use RangeSelect;
    use ServiceCharge;
    protected $redis;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 订单列表
     *
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithType;
     * @middleware \App\Http\Middleware\WithPage;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function showOrder(Request $request)
    {
        $data = NormalOrder::getOrder($request->only('user_id', 'paginate'));
        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkGoingOrder(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $user_id = $request->input('user_id');
        $order_info_as_user = GameStatus::getGoingOrder($user_id, false);
        $order_info_as_master = GameStatus::getGoingOrder($user_id);

        if($order_info_as_user && $order_info_as_master){
            if($order_info_as_user->created_at > $order_info_as_master->created_at){
                return self::$RESPONSE_CODE->Code(0, [
                    'master_user_id' => $order_info_as_user->master_user_id,
                    'order_id' => $order_info_as_user->order_id,
                    'user_status' => $order_info_as_user->user_status,
                    'is_master' => false,
                    'exists' => true
                ]);
            }
            return self::$RESPONSE_CODE->Code(0, [
                'master_user_id' => $order_info_as_master->master_user_id,
                'order_id' => $order_info_as_master->order_id,
                'user_status' => $order_info_as_master->user_status,
                'is_master' => true,
                'exists' => true
            ]);

        }elseif ($order_info_as_user && !$order_info_as_master){
            return self::$RESPONSE_CODE->Code(0, [
                'master_user_id' => $order_info_as_user->master_user_id,
                'order_id' => $order_info_as_user->order_id,
                'user_status' => $order_info_as_user->user_status,
                'is_master' => false,
                'exists' => true
            ]);

        }elseif ($order_info_as_master && !$order_info_as_user){
            return self::$RESPONSE_CODE->Code(0, [
                'master_user_id' => $order_info_as_master->master_user_id,
                'order_id' => $order_info_as_master->order_id,
                'user_status' => $order_info_as_master->user_status,
                'is_master' => true,
                'exists' => true
            ]);

        }else{
            return self::$RESPONSE_CODE->Code(0, [
                'master_user_id' => '',
                'order_id' => '',
                'user_status' => 0,
                'is_master' => false,
                'exists' => false
            ]);
        }
    }

    /**
     * 订单列表
     *
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithType;
     * @middleware \App\Http\Middleware\WithPage;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function yuewanOrder(Request $request)
    {
        $data = NormalOrder::getYuewanOrder($request->only('user_id', 'paginate'));
        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * 订单详情
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithType;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function orderInfo(Request $request)
    {
        $data = NormalOrder::getOrderInfo($request->only('user_id', 'order_id'));
        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * 生成陪玩订单
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function createAccompanyOrder(Request $request)
    {
        if (!$request->has('user_id')) {
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

        $requestData = $request->input('data');
        $user_id = (int)$request->input('user_id');

        if ($requestData['game_num'] < 1 || $requestData['game_num'] > 3) {
            return self::$RESPONSE_CODE->setMsg('请求出错，`game_num`参数不在允许的范围')->Code(4000);
        }

        if (false == isset($requestData['level_id'])) {
            $requestData['level_id'] = 0;
        }

        try {
            $data = (new CreateAccompanyOrder())->index($user_id, $requestData);
            return self::$RESPONSE_CODE->Code(0, $data);
        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->Code(5002);
        }
    }

    /**
     * 订单评分
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithOrderID;
     * @middleware \App\Http\Middleware\WithData;
     * @middleware \App\Http\Middleware\WithType;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\SetException;
     */
    public function comment(Request $request)
    {
        try {
            if (!$request->input('user_id')) {
                return self::$RESPONSE_CODE->setMsg('请求出错，缺少`user_id`参数或参数为空')->Code(4000);
            }
            if (!$request->input('data.order_id')) {
                return self::$RESPONSE_CODE->setMsg('请求出错，缺少`order_id`参数或参数为空')->Code(4000);
            }
            if (!$request->input('data.content')) {
                return self::$RESPONSE_CODE->setMsg('请求出错，缺少`content`参数或参数为空')->Code(4000);
            }
            NormalOrder::setComment($request->input('user_id'), $request->input('data'));

        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0);
    }

    /**
     * 获取导师订单评论列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMasterCommentList(Request $request)
    {
        try {
            if (!$request->has('master_user_id')) {
                return self::$RESPONSE_CODE->Code(4000);
            }
            $master_user_id = $request->get('master_user_id');
            $data = NormalOrder::findCommentList($master_user_id);
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * 专属单页
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function personalOrder(Request $request)
    {
        try {
            $master_info = (new Master())->getMasterRange($request->get('master_user_id'));
            if ($master_info == null) {
                return self::$RESPONSE_CODE->setMsg('不存在的导师')->code(5002);
            }
            $gender = $master_info['sex'];

            $CHARGE = null;//付费模型
            if ($gender == self::$FA_MALE) {
                $CHARGE = new WomanCharge();
                $game_type = 2;
            } else {
                $game_type = 1;
                $CHARGE = new ManCharge();
            }
            self::$MASTER_GENDER = $gender;
            $list = Game::getAllList($game_type);

            self::refactorList($list);
            //价格查询
            $game_ids = array_column($master_info['range'], 'game_id');
            $charge_list = $CHARGE->getAllByGameIds($game_ids);

            $master_range = self::rangeContentToArray($master_info['range']);
            $master_auth = array_column($master_info['skill'], 'game_id');

            self::$RANGE_SELECT_FILTER = true;
            self::$RANGE_SELECT_CHARGE = true;
            self::$MASTER_CHARGE_LIST = $charge_list;
            self::selectGameRange($list, $master_range, $master_auth);

            if (empty($list)) {
                $list = (object)[];
            }
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->code(5002);
        }

        return self::$RESPONSE_CODE->code(0, $list);
    }

    public function orderServicePrice(Request $request)
    {
        if ($request->has('master_user_id')) {
            $data = $this->PersonalOrderServicePrice($request->input());
        } else {
            $data = $this->NormalOrderServicePrice($request->input());
        }

        return $data;
    }

    /**
     * @param $request_data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function NormalOrderServicePrice($request_data)
    {
        if (!isset($request_data['game_id'])) {
            return self::$RESPONSE_CODE->code(4000);
        }
        if (!isset($request_data['game_num'])) {
            return self::$RESPONSE_CODE->code(4000);
        }
        if (!isset($request_data['level_type'])) {
            return self::$RESPONSE_CODE->code(4000);
        }
        if (!isset($request_data['gender'])) {
            return self::$RESPONSE_CODE->code(4000);
        }
        (new LogController())->addLog($request_data);
        $gender = $request_data['gender'];
        $game_id = $request_data['game_id'];
        $game_num = (int)$request_data['game_num'];
        $level_id = isset($request_data['level_id']) ? $request_data['level_id'] : 0;
        if (!$level_id) {
            $level_info = GameLevel::getFirstLevel($game_id);
            $level_id = $level_info->id;
        }

        try {
            $CHARGE = null;//付费模型
            if ($gender == 2) {//女
                $CHARGE = new WomanCharge();
                self::$MASTER_GENDER = self::$FA_MALE;
            } else {
                $CHARGE = new ManCharge();
            }

            $data = ['price' => 0];
            $charge_info = $CHARGE->getCharge($game_id, $level_id);
            if ($charge_info) {
                $charge_info = $charge_info->toArray();
                $charge_data = self::selectSinglePrice($charge_info, $request_data['level_type']);
                $price = ServiceCharge::calculate($charge_data['price'] * $game_num);
                $data['price'] = $price;
            }

        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->code(5002);
        }
        return self::$RESPONSE_CODE->code(0, $data);
    }

    /**
     * @param $request_data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function PersonalOrderServicePrice($request_data)
    {
        if (!isset($request_data['master_user_id'])) {
            return self::$RESPONSE_CODE->code(4000);
        }
        if (!isset($request_data['game_id'])) {
            return self::$RESPONSE_CODE->code(4000);
        }
        if (!isset($request_data['server_id'])) {
            return self::$RESPONSE_CODE->code(4000);
        }
        if (!isset($request_data['game_num'])) {
            return self::$RESPONSE_CODE->code(4000);
        }
        $master_id = $request_data['master_user_id'];
        $game_id = $request_data['game_id'];
        $game_num = (int)$request_data['game_num'];
        $level_id = isset($request_data['level_id']) ? $request_data['level_id'] : 0;
        if (!$level_id) {
            $level_info = GameLevel::getFirstLevel($game_id);
            $level_id = $level_info->id;
        }

        $master_info = (new Master())->getMasterGameRange($master_id, $game_id);
        if ($master_info == null) {
            return self::$RESPONSE_CODE->setMsg('不存在的导师')->code(5002);
        }

        $range = current($master_info['range']);
        $master_level = isset($range['master_level']) ? $range['master_level'] : 1;
        try {
            $CHARGE = null;//付费模型
            $gender = $master_info['sex'];
            if ($gender == self::$FA_MALE) {
                $CHARGE = new WomanCharge();
                self::$MASTER_GENDER = self::$FA_MALE;
            } else {
                $CHARGE = new ManCharge();
            }

            $data = ['price' => 0];
            $charge_info = $CHARGE->getCharge($game_id, $level_id);
            if ($charge_info) {
                $charge_info = $charge_info->toArray();
                $charge_data = self::selectSinglePrice($charge_info, $master_level);
                $price = ServiceCharge::calculate($charge_data['price'] * $game_num);
                $data['price'] = $price;
            }

        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->code(5002);
        }
        return self::$RESPONSE_CODE->code(0, $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrderByUser(Request $request)
    {
        try {
            $user_id = $request->input('user_id');
            $order_id = $request->input('order_id');

            $order = NormalOrder::getOrderById($order_id);
            if ($order->user_id != $user_id) {
                return self::$RESPONSE_CODE->setMsg('user_id参数错误')->code(4000);
            }

            $gameStatusInfo = GameStatus::getOrderInfo($order_id, $order->master_user_id);
            if (null != $gameStatusInfo && $gameStatusInfo->game_status == 1) {
                //游戏已开始
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1005));
            }

            
            if ($order->status >= 2) {
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1005));
            }
            
            $now = time();
            DB::beginTransaction();
            NormalOrder::updateOrder($order_id, ['status' => 3, 'pay_status' => 1, 'back_sum' => $order->pay_sum, 'back_type' => 1, 'back_status' => 1]);
            GameStatus::updateInfo($order_id, ['order_status' => 2]);
            UserTransaction::add([
                'user_id' => $order->user_id,
                'order_id' => $order->order_id,
                'money' => $order->pay_sum,
                'title' => '订单退款',
                'desc' => '订单退款',
                'type' => 2,
                'status' => 1,
                'created_at' => $now,
            ]);
            Wallet::addCash($order->user_id, $order->pay_sum);
            DB::table('master_gameorder_list')
                ->where('order_id', $order_id)
                ->update(['order_status' => 2]);
            //导师变为接单中
            Master::updateInfo(['user_id' => $order->master_user_id, 'status' => 2]);
        } catch (\Exception $e) {
            DB::rollBack();
            return self::$RESPONSE_CODE->code(5000,(object)[]);
        }
        DB::commit();
        return self::$RESPONSE_CODE->code(0,(object)[]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrderByMaster(Request $request)
    {
        try {
            $user_id = $request->input('user_id');
            $order_id = $request->input('order_id');

            $order = NormalOrder::getOrderById($order_id);
            if ($order->master_user_id != $user_id) {
                return self::$RESPONSE_CODE->setMsg('user_id参数错误')->code(4000);
            }

            $gameStatusInfo = GameStatus::getOrderInfo($order_id, $order->master_user_id);
            if (null != $gameStatusInfo && $gameStatusInfo->game_status == 1) {
                //游戏已开始
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1005));
            }

            
            if ($order->status >= 2) {
                return self::$RESPONSE_CODE->code(0, self::orderStatusCode(1005));
            }
            

            DB::beginTransaction();
            NormalOrder::updateOrder($order_id, ['status' => 3]);
            GameStatus::updateInfo($order_id, ['order_status' => 2]);
            UserTransaction::add([
                'user_id' => $order->user_id,
                'order_id' => $order->order_id,
                'money' => $order->pay_sum,
                'title' => '订单退款',
                'desc' => '订单退款',
                'type' => 2,
                'status' => 1,
                'created_at' => time(),
            ]);
            Wallet::addCash($order->user_id, $order->pay_sum);
            DB::table('master_gameorder_list')
                ->where('order_id', $order_id)
                ->update(['order_status' => 2]);
            //导师变为接单中
            Master::updateInfo(['user_id' => $user_id, 'status' => 2]);
        } catch (\Exception $e) {
            DB::rollBack();
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->code(5000,(object)[]);
        }
        DB::commit();
        return self::$RESPONSE_CODE->code(0,(object)[]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryOrderStatus(Request $request)
    {
        try {
            $order_id = $request->input('order_id');

            $order = NormalOrder::getOrderById($order_id);
            if (!$order) {
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(2001));
            }

            // 1:支付成功 2:已完成 3:已取消 4退款中
            $response = self::orderStatusCode(2000);
            switch ($order->status) {
                case 1:
                    $response = self::orderStatusCode(2000);
                    break;
                case 2:
                    $response = self::orderStatusCode(2003);
                    break;
                case 3:
                    $response = self::orderStatusCode(2002);
                    break;
                case 4:
                    $response = self::orderStatusCode(2004);
                    break;
                case 5:
                    $response = self::orderStatusCode(2005);
                    break;
            }
        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0, $response);
    }
}