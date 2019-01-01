<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 上午 11:13
 */

namespace App\Http\Controllers\AppointmentOrder;

use App\Http\Controllers\Controller;
use App\Http\InterFaces\PaymentInterface;
use App\Models\Game;
use App\Models\Ticket;

/**
 * 创建约玩订单
 * Class Create
 * @package App\Http\Controllers\CreateOrder
 */
class CreateAppointmentOrder extends Controller
{
    const ALI_BUY = 1;//阿里购买
    const WECHAT_BUY = 2;//微信购买
    const WALLET_BUY = 3;//钱包购买

    /**
     * 支付模型
     * @var PaymentInterface
     */
    protected $PAYMENT = null;

    protected $order_data = [];

    /**
     * @param $user_id
     * @param $requestData
     * @return mixed
     * @throws \Exception
     */
    public function index($user_id, $requestData)
    {
        try {
            \DB::beginTransaction();
            $game_id = $requestData['game_id'];
            $game_info = Game::getGameInfo($game_id);
            if($game_info->game_type != 3){
                throw new \Exception('此游戏不是约玩类');
            }

            $ticket = false;
            $calculate_data = self::calculate($user_id, $requestData, $ticket);
            $money_sum = max(0, $calculate_data['money_sum']);
            $pay_sum = max(0, $calculate_data['pay_sum']);
            $unit_price = max(0, $calculate_data['unit_price']);

            if($ticket == false){
                //未使用抵扣券
                $requestData['ticket_id'] = 0;
            }
            $this->setData($requestData, $user_id, $unit_price, $pay_sum, $money_sum);
            $this->getPaymentObj();

            $this->PAYMENT->ahead($this->order_data);
            $data = $this->PAYMENT->pay();
            $this->PAYMENT->behind();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw new \Exception($e->getMessage());
        }

        \DB::commit();
        return $data;
    }

    /**
     * @throws \Exception
     */
    protected function getPaymentObj()
    {
        if ($this->order_data['pay_sum'] == 0 || $this->order_data['pay_type'] == self::WALLET_BUY) {
            $this->PAYMENT = new WalletAppointmentOrder();
            return;
        }

        switch ($this->order_data['pay_type']) {
            case self::ALI_BUY:
                $this->PAYMENT = new AliPayAppointmentOrder();
                break;

            case self::WECHAT_BUY:
                $this->PAYMENT = new WechatPayAppointmentOrder();
                break;

            default:
                throw new \Exception('pay_type参数错误');
                break;
        }
    }

    /**
     * @param $user_id
     * @param $requestData
     * @param bool $ticket
     * @return array
     * @throws \Exception
     */
    protected static function calculate($user_id, $requestData, &$ticket = false)
    {
        $unit_price = Game::getPlayGameMoney($requestData['sexy']);
        $money_sum = $unit_price;
        $pay_sum = $money_sum;

        $data = ['unit_price'=>$unit_price,'pay_sum' => $pay_sum, 'money_sum' => $money_sum];

        //抵扣券
        if ($requestData['ticket_id'] < 0) {
            return $data;
        }

        $ticket_info = Ticket::getUnusedTicketById($user_id, $requestData['ticket_id']);
        if (empty($ticket_info)) {
            return $data;
        }

        //0满减 1免单
        if ($ticket_info->type == 1) {
            $data['pay_sum'] = 0;
            $ticket = true;
            return $data;
        } else {
            $rule = explode('@', $ticket->rule);
            $data['pay_sum'] = $pay_sum - $rule[1];
            $ticket = true;
            return $data;
        }
    }

    /**
     * @param $requestData
     * @param $user_id
     * @param $unit_price
     * @param $pay_sum
     * @param $money
     */
    protected function setData($requestData, $user_id, $unit_price, $pay_sum, $money)
    {
        $ticket_sum = $money - $pay_sum;
        $this->order_data = [
            'order_id' => self::createOrderId(),
            'search_sexy' => (int)$requestData['sexy'],
            'user_id' => (int)$user_id,
            'game_id' => (int)$requestData['game_id'],
            'server_id' => (int)$requestData['server_id'],
            'ticket_id' => isset($requestData['ticket_id'])?(int)$requestData['ticket_id']:0,
            'game_name' => isset($requestData['game_name'])?$requestData['game_name']:'',
            'server_name' => isset($requestData['server_name'])?$requestData['server_name']:'',
            'unit_price' => (int)$unit_price,
            'status' => 0,
            'pay_type' => (int)$requestData['pay_type'],
            'pay_sum' => $pay_sum,
            'money_sum' => $money,
            'ticket_sum' => $ticket_sum,
            'created_at' => $_SERVER['REQUEST_TIME'],
        ];
    }

    private static function createOrderId()
    {
        $order_id_main = 'P' . date('YmdHis');
        $random = rand(10000000, 99999999);
        $id = (100 - $random % 100) % 100;
        $order_id = $order_id_main . str_pad($id, 6, '0', STR_PAD_LEFT);
        return $order_id;
    }
}