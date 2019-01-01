<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 上午 11:13
 */

namespace App\Http\Controllers\Bask;

use App\Http\Controllers\Controller;
use App\Http\InterFaces\PaymentInterface;
use App\Http\Traits\ServiceCharge;
use App\Models\Game;
use App\Models\GameStatus;
use App\Models\Sociaty;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * 打赏
 * Class Create
 * @package App\Http\Controllers\CreateOrder
 */
class BaskController extends Controller
{
    use ServiceCharge;

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
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if(!$request->has('user_id')){
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`user_id`参数或参数为空')->Code(4000);
        }
        if(!$request->has('bask_user_id')){
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`bask_user_id`参数或参数为空')->Code(4000);
        }
        if(!$request->has('source_page')){
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`source_page`参数或参数为空')->Code(4000);
        }
        if(!$request->has('pay_type')){
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`pay_type`参数或参数为空')->Code(4000);
        }
        if(!$request->has('pay_sum')){
            return self::$RESPONSE_CODE->setMsg('请求出错，缺少`pay_sum`参数或参数为空')->Code(4000);
        }

        try {
            \DB::beginTransaction();

            $this->setData($request->input());
            $this->getPaymentObj();

            $this->PAYMENT->ahead($this->order_data);
            $data = $this->PAYMENT->pay();
            $this->PAYMENT->behind();
        } catch (\Exception $e) {
            \DB::rollBack();
            return self::$RESPONSE_CODE->Code(5002,$e->getMessage());
        }

        \DB::commit();
        return self::$RESPONSE_CODE->Code(0,$data);
    }

    /**
     * @throws \Exception
     */
    protected function getPaymentObj()
    {
        if ($this->order_data['pay_type'] == self::WALLET_BUY) {
            $this->PAYMENT = new WalletBask();
            return;
        }

        switch ($this->order_data['pay_type']) {
            case self::ALI_BUY:
                $this->PAYMENT = new AliPayBask();
                break;

            case self::WECHAT_BUY:
                $this->PAYMENT = new WechatBask();
                break;

            default:
                throw new \Exception('pay_type参数错误');
                break;
        }
    }

    /**
     * @param $requestData
     * @throws \App\Exceptions\UpdateException
     */
    protected function setData($requestData)
    {
        $user_id = (int)$requestData['user_id'];
        $bask_user_id = (int)$requestData['bask_user_id'];
        $pay_sum = max(0, $requestData['pay_sum']);
        $user_info = User::getBasic($bask_user_id);
        $sociaty_info = Sociaty::getInfoWhere(['id' => $user_info->sociaty_id]);
        $proportions = $sociaty_info->proportions;

        $this->order_data = [
            'order_id' => self::createOrderId(),
            'user_id' => $user_id,
            'bask_user_id' => $bask_user_id,
            'game_id' => isset($requestData['game_id']) ? (int)$requestData['game_id'] : 0,
            'server_id' => isset($requestData['server_id']) ? (int)$requestData['server_id'] : 0,
            'level_id' => isset($requestData['level_id']) ? (int)$requestData['level_id'] : 0,
            'source_page' => isset($requestData['source_page']) ? (int)$requestData['source_page'] : 0,
            'source_order' => isset($requestData['source_order']) ? $requestData['source_order'] : '',
            'pay_type' => $requestData['pay_type'],
            'sociaty_id' => (int)$user_info->sociaty_id,
            'pay_sum' => $pay_sum,
            'proportions' => $proportions,
            'status' => 0,
            'content'=>isset($requestData['content']) ? $requestData['content'] : ''
        ];
    }

    private static function createOrderId()
    {
        $order_id_main = 'D' . date('YmdHis');//打赏id标识
        $random = rand(10000000, 99999999);
        $id = (100 - $random % 100) % 100;
        $order_id = $order_id_main . str_pad($id, 6, '0', STR_PAD_LEFT);
        return $order_id;
    }
}