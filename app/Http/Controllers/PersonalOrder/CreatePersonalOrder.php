<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 上午 11:13
 */

namespace App\Http\Controllers\PersonalOrder;

use App\Http\Controllers\Controller;
use App\Http\InterFaces\PaymentInterface;
use App\Http\Traits\RangeSelect;
use App\Http\Traits\ServiceCharge;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\ManCharge;
use App\Models\MasterRange;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WomanCharge;

/**
 * 创建订单
 * Class Create
 * @package App\Http\Controllers\CreateOrder
 */
class CreatePersonalOrder extends Controller
{
    use ServiceCharge;
    use RangeSelect;
    const ALI_BUY = 1;//阿里购买
    const WECHAT_BUY = 2;//微信购买
    const WALLET_BUY = 3;//钱包购买

    /**
     * 支付模型
     * @var PaymentInterface
     */
    protected $PAYMENT = null;

    public $order_data;

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

            $ticket = false;
            $calculate_data = self::calculate($user_id, $requestData, $ticket);

            if ($ticket == false) {
                //未使用抵扣券
                $requestData['ticket_id'] = 0;
            }
            $this->setData($requestData, $user_id, $calculate_data);
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
            $this->PAYMENT = new WalletPersonalOrder();
            return;
        }

        switch ($this->order_data['pay_type']) {
            case self::ALI_BUY:
                $this->PAYMENT = new AliPayPersonalOrder();
                break;

            case self::WECHAT_BUY:
                $this->PAYMENT = new WechatPayPersonalOrder();
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
        $unit_price = 0;
        $CHARGE = null;//付费模型
        if ($requestData['master_type'] == 2) {//女
            $CHARGE = new WomanCharge();
            self::$MASTER_GENDER = self::$FA_MALE;
        } else {
            $CHARGE = new ManCharge();
        }
        $charge_info = $CHARGE->getCharge($requestData['game_id'], $requestData['level_id']);
        if ($charge_info) {
            $charge_info = $charge_info->toArray();
            $charge_data = self::selectSinglePrice($charge_info, $requestData['level_type']);
            $unit_price = $charge_data['price'];
        }

        $money_sum = $unit_price * $requestData['game_num'];
        $service_price = ServiceCharge::calculate($money_sum);
        $pay_sum = $money_sum + $service_price;

        $data = [
            'unit_price' => $unit_price,
            'pay_sum' => $pay_sum,
            'money_sum' => $money_sum,
            'service_price' => $service_price,
            'ticket_sum' => 0
        ];

        //抵扣券
        if ($requestData['ticket_id'] > 0) {
            $ticket_info = Ticket::getUnusedTicketById($user_id, $requestData['ticket_id']);
            if (empty($ticket_info)) {
                return $data;
            }

            $allow_game = explode(',', $ticket_info->limits);
            //不在免单游戏范围
            if (!empty($allow_game) && false == in_array($requestData['game_id'], $allow_game)) {
                return $data;
            }

            //0满减 1免单
            if ($ticket_info->type == 1) {
                $ticket = true;
                $data['ticket_sum'] = $data['pay_sum'];
                $data['pay_sum'] = 0;
                return $data;
            } else {
                $rule = explode('@', $ticket_info->rule);
                if ($pay_sum > $rule[0]) {
                    $ticket = true;
                    $data['ticket_sum'] = $rule[1];
                    $data['pay_sum'] = $pay_sum - $rule[1];
                    return $data;
                }
            }
        }

        return [
            'unit_price' => max(0, $data['unit_price']),
            'pay_sum' => max(0, $data['pay_sum']),
            'money_sum' => max(0, $data['money_sum']),
            'service_price' => max(0, $data['service_price']),
            'ticket_sum' => max(0, $data['ticket_sum'])
        ];
    }

    /**
     * @param $requestData
     * @param $user_id
     * @param $calculate_data
     */
    protected function setData($requestData, $user_id, $calculate_data)
    {
        $money_sum = $calculate_data['money_sum'];
        $pay_sum = $calculate_data['pay_sum'];
        $unit_price = $calculate_data['unit_price'];
        $service_price = $calculate_data['service_price'];
        $ticket_sum = $calculate_data['ticket_sum'];
        $level_id = isset($requestData['level_id']) ? (int)$requestData['level_id'] : 0;
        $level_name = isset($requestData['level_name']) ? $requestData['level_name'] : '';
        if($level_id){
            $level_info = GameLevel::getInfoWhere(['id'=>$level_id]);
            $level_name = $level_info->level_name;
        }

        $this->order_data = [
            'order_id' => self::createOrderId(),
            'order_type' => (int)$requestData['order_type'],
            'master_type' => (int)$requestData['master_type'],
            'user_id' => (int)$user_id,
            'master_user_id' => $requestData['master_id'],
            'game_id' => (int)$requestData['game_id'],
            'level_type' => (int)$requestData['level_type'],
            'server_id' => (int)$requestData['server_id'],
            'level_id' => $level_id,
            'ticket_id' => (int)$requestData['ticket_id'],
            'game_name' => isset($requestData['game_name']) ? $requestData['game_name'] : '',
            'server_name' => isset($requestData['server_name']) ? $requestData['server_name'] : '',
            'level_name' => $level_name,
            'unit' => $requestData['unit'],
            'unit_price' => (int)$unit_price,
            'game_num' => (int)$requestData['game_num'],
            'room_id' => isset($requestData['room_id']) ? (int)$requestData['room_id'] : 0,
            'status' => 0,
            'pay_type' => (int)$requestData['pay_type'],
            'pay_sum' => $pay_sum,
            'money_sum' => $money_sum,
            'service_price' => $service_price,
            'ticket_sum' => $ticket_sum,
            'is_exclusive' => 1,
            'created_at' => $_SERVER['REQUEST_TIME'],
        ];
    }

    private static function createOrderId()
    {
        $order_id_main = 'W' . date('YmdHis');
        $random = rand(10000000, 99999999);
        $id = (100 - $random % 100) % 100;
        $order_id = $order_id_main . str_pad($id, 6, '0', STR_PAD_LEFT);
        return $order_id;
    }
}