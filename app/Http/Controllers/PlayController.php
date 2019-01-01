<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AppointmentOrder\CreateAppointmentOrder;
use App\Http\Controllers\entrust\ShareTaskController;
use App\Model\ShareStream;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Wallet;
use App\Models\YueMatch;
use App\Models\YueOrder;
use Illuminate\Http\Request;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Yansongda\Pay\Pay;

class PlayController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectGame()
    {
        $games = Game::getDateGameList();
        return self::$RESPONSE_CODE->Code(0,$games);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryPlayGameMoney(Request $request)
    {
        if(!$request->has('sexy')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        try {
            $data = [];
            $charge = \DB::table('game_yuewan_charge')->where('player_sex', $request->input('sexy'))->first();
            $data['price'] = $charge->price;
        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0,$data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryOrderInfo(Request $request)
    {
        if(!$request->has('order_id')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        try{
            $order_info = YueOrder::getOrderInfo($request->input('order_id'));
            if($order_info == null){
                return self::$RESPONSE_CODE->setMsg('订单回调未完成')->Code(5002);
            }
            $status = $order_info->status;
            if($order_info->status == 2){
                $status = 1;
            }
            $data = [
                'status'=>$status
            ];
        }catch (\Exception $e){
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0,$data);
    }

    /**
     * 约玩订单
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAppointmentOrder(Request $request)
    {
        if (!$request->has('data.sexy') || !$request->input('data.sexy')) {
            return self::$RESPONSE_CODE->setMsg('缺少`sexy`参数或参数为空')->Code(4000);
        }
        if (!$request->has('data.game_id') || !$request->input('data.game_id')) {
            return self::$RESPONSE_CODE->setMsg('缺少`game_id`参数或参数为空')->Code(4000);
        }
        if (!$request->has('data.server_id') || !$request->input('data.server_id')) {
            return self::$RESPONSE_CODE->setMsg('缺少`server_id`参数或参数为空')->Code(4000);
        }
        if (!$request->has('data.pay_type') || !$request->input('data.pay_type')) {
            return self::$RESPONSE_CODE->setMsg('缺少`pay_type`参数或参数为空')->Code(4000);
        }
        if (!$request->has('user_id') || !$request->input('user_id')) {
            return self::$RESPONSE_CODE->setMsg('缺少`user_id`参数或参数为空')->Code(4000);
        }

        $requestData = $request->input('data');
        $user_id = (int)$request->input('user_id');

        try{
            $data = (new CreateAppointmentOrder())->index($user_id,$requestData);
            return self::$RESPONSE_CODE->Code(0,$data);
        }catch (\Exception $e){
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->Code(5002);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelAppointmentOrder(Request $request)
    {
        if(!$request->has('order_id')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        $order = DB::table('yuewan_orders')->where('order_id', $request->input('order_id'))->first();
        if($order->order_status == 1){
            return self::$RESPONSE_CODE->setMsg('匹配成功无法取消')->Code(5105);
        }
        try{
            $result = YueOrder::updateOrder(['order_id'=>$request->input('order_id'),'status'=>3]);
            if(!$result){
                return self::$RESPONSE_CODE->setMsg('订单已取消')->Code(5005);
            }
            DB::table('yuewan_user_list')->where('user_id', $order->user_id)->delete();
            $matchResult = DB::table('yuewan_match_list')->where('order_id', $order->order_id)->first();
            if(!$matchResult){
                $refund_sum = $order->pay_sum;
                $now = time();
                if($refund_sum){
                    $userTransactionSql = "insert into user_transaction(user_id,order_id,money,`title`,`desc`,`type`,status,created_at)values ({$order->user_id},'{$order->order_id}',{$refund_sum},'订单退款','订单退款',2,1,{$now})";
                    DB::insert($userTransactionSql);
                    $updateWalletSql = "UPDATE wallets SET cash = cash + {$refund_sum} WHERE user_id = {$order->user_id}";
                    DB::update($updateWalletSql);
                }
            }
        }catch (\Exception $e){
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0,(object)[]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryMatchResult(Request $request)
    {
        if(!$request->has('order_id')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        try{
            $match_info = YueMatch::getMatching($request->input('order_id'));
            if(null != $match_info){
                $match_user_id = $match_info->match_user_id;
                $user = User::getBasic($match_user_id);
                $data = [
                    'nickname'=>$user->nickname,
                    'avatar'=>$user->avatar,
                    'hx_id'=>$user->hx_id,
                    'sexy'=>$user->sexy,
                    'location'=>$user->location,
                ];
            }else{
                $data = (object)[];
            }
        }catch (\Exception $e){
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0,$data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectMatchUser(Request $request)
    {
        if(!$request->has('order_id')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        try{
            DB::beginTransaction();
            $match_info = YueMatch::getMatching($request->input('order_id'));
            $result = YueMatch::updateOrder([
                'order_id'=>$request->input('order_id'),
                'status'=>1]);
            if(!$result){
                return self::$RESPONSE_CODE->setMsg('订单已修改')->Code(5005);
            }
            //任务流程
            (new ShareTaskController())->index($match_info->user_id);

        }catch (\Exception $e){
            DB::rollBack();
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->Code(5002);
        }
        DB::commit();
        return self::$RESPONSE_CODE->Code(0,['status'=>'ok']);
    }

}