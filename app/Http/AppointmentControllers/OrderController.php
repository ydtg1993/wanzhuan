<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/6 0006
 * Time: 下午 2:58
 */

namespace App\Http\AppointmentControllers;

use App\Http\Common\Lock;
use App\Http\Common\LogController;
use App\Http\Common\RedisDriver;
use App\Http\Controllers\Controller;
use App\Http\Traits\OrderStatus;
use App\Models\AppointmentGrabOrderModel;
use App\Models\AppointmentOrderModel;
use App\Models\AppointmentStatusModel;
use App\Models\Game;
use App\Models\GameServer;
use App\Models\Sociaty;
use App\Models\User;
use App\Models\UserTransaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

require_once __DIR__ . '/../../Libraries/cmq-sdk/cmq_api.php';
require_once __DIR__ . '/../../../public/xinge-api-php/XingeApp.php';

class OrderController extends Controller
{
    use OrderStatus;

    public function __construct()
    {
        parent::__construct();
    }

    public function orderList(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $page = $request->has('page') ? $request->input('page') : 1;

        $list = AppointmentOrderModel::findOrderListWhere(
            [['user_id', '=', $request->input('user_id')], ['accept_user_id', '>', 0]]
            , $page, 10, 'created_at', 'DESC');
        foreach ($list as &$l) {
            if (!$l['accept_user_id']) {
                continue;
            }
            $l['user_info'] = User::getBasic($l['accept_user_id']);
            $l['game_name'] = (Game::getGameInfo($l['game_id']))->name;
            $l['server_name'] = GameServer::getInfoWhere(['id' => $l['server_id']])['server_name'];
        }

        return self::$RESPONSE_CODE->Code(0, $list);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('game_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('gender_limit')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('server_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('service_time')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('pay_sum')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        try {
            $user_id = $request->input('user_id');
            $pay_sum = abs((int)$request->input('pay_sum'));
            if ($pay_sum > 0) {
                $wallet = Wallet::getWallet($user_id);
                if ($wallet['cash'] < $pay_sum) {
                    return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1008));
                }
            }
            $order_id = self::createOrderId();

            \DB::beginTransaction();
            Wallet::addUserTransaction([
                'user_id' => $user_id,
                'order_id' => $order_id,
                'money' => (0 - $pay_sum),
                'title' => '消费',
                'desc' => '呼叫消费',
                'type' => 12,
                'status' => 1,
                'created_at' => TIME
            ]);

            if ($pay_sum < 100) {
                $max_accept_num = 1;
            } elseif ($pay_sum < 200) {
                $max_accept_num = 2;
            } else {
                $max_accept_num = 3;
            }

            $result = AppointmentOrderModel::add([
                'user_id' => $user_id,
                'order_id' => $order_id,
                'game_id' => (int)$request->input('game_id'),
                'game_name' => Game::getGameInfo($request->input('game_id'))->name,
                'gender_limit' => (int)$request->input('gender_limit'),
                'server_id' => (int)$request->input('server_id'),
                'service_time' => (int)$request->input('service_time'),
                'pay_sum' => $pay_sum,
                'max_accept_num' => $max_accept_num,
                'order_status' => 1
            ]);

            AppointmentStatusModel::addOrUp([
                'user_id' => $user_id,
                'order_id' => $order_id,
                'status' => 1
            ], ['user_id' => $user_id, 'order_id' => $order_id]);

            if (!$result) {
                \DB::rollBack();
                return self::$RESPONSE_CODE->Code(5005);
            }
            //产生socket事件消息
            $type = 'createorder';
            $this->createOrderSocketEvent($user_id, $order_id, $type);

        } catch (\Exception $e) {
            \DB::rollBack();
            return self::$RESPONSE_CODE->Code(5002);
        }

        \DB::commit();
        return self::$RESPONSE_CODE->Code(0, ['order_id' => $order_id]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function grabOrder(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('order_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        set_error_handler(function ($error_no, $error_message, $error_file, $error_line) {
            return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1007));
        }, E_ERROR);
        $LOCK = new Lock();
        try {
            while (true) {
                $flag = $LOCK->judge($request->input('order_id'));
                if ($flag === true) {
                    break;
                }
            }
            $user_id = $request->input('user_id');
            $order_id = $request->input('order_id');
            $order_info = AppointmentOrderModel::getInfoWhere(['order_id' => $order_id]);

            $appoint_status_info = AppointmentStatusModel::getInfoWhere(['user_id' => $user_id, 'order_id' => $order_id]);
            if ($appoint_status_info && $appoint_status_info['status'] == 3) {
                $LOCK->unlash($request->input('order_id'));
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1006));
            }

            $result = AppointmentGrabOrderModel::getInfoWhere(['order_id' => $order_id, 'accept_user_id' => $user_id]);
            if ($result) {
                $LOCK->unlash($request->input('order_id'));
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1000));
            }

            if ($order_info['game_status'] > 0) {
                $LOCK->unlash($request->input('order_id'));
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1003));
            }

            if ($order_info['order_status'] == 2) {
                $LOCK->unlash($request->input('order_id'));
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1002));
            }

            $grab_orders = AppointmentGrabOrderModel::getAllWhere(['order_id' => $order_id]);
            if (count($grab_orders) >= $order_info['max_accept_num']) {
                $LOCK->unlash($request->input('order_id'));
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1007));
            }

            $has_appointment_order = AppointmentOrderModel::getInfoWhere(['accept_user_id' => $user_id, 'game_status' => 1, 'order_status' => 1]);
            if ($has_appointment_order) {
                $start_time = $has_appointment_order['game_start_at'];
                if ($start_time) {
                    $LOCK->unlash($request->input('order_id'));
                    return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1009));
                }
            }

            $result = AppointmentGrabOrderModel::add([
                'order_id' => $request->input('order_id'),
                'user_id' => $order_info['user_id'],
                'accept_user_id' => $request->input('user_id')
            ]);
            if (!$result) {
                $LOCK->unlash($request->input('order_id'));
                return self::$RESPONSE_CODE->Code(5005);
            }
            //信鸽推送
            $andorid_access_id = 2100300435;
            $andorid_secret_key = '96774184c1d236fe0cfb37744b9c0515';

            $ios_access_id = 2200302271;
            $ios_secret_key = '82477f2c9b7956386583dc094ddef0a1';

            $user = User::find($order_info['user_id']);
            if ($user->system == 'andriod') {
                $res = \xinge\XingeApp::PushAccountAndroid($andorid_access_id, $andorid_secret_key, "有人接单啦", "附近的妹子/汉子接单啦 点击确认您的呼叫~", $user->xg_id);
            }
            if ($user->system == 'ios') {
                //$res = \xinge\XingeApp::PushAccountIos($ios_access_id, $ios_secret_key, "附近的妹子/汉子接单啦 点击确认您的呼叫~", $user->xg_id, \xinge\XingeApp::IOSENV_PROD);
                $push = new \xinge\XingeApp($ios_access_id, $ios_secret_key);
                $mess = new \xinge\MessageIOS();
                $mess->setExpireTime(86400);
                $mess->setAlert("附近的妹子/汉子接单啦 点击确认您的呼叫~");
                //$mess->setAlert(array('key1'=>'value1'));
                $mess->setBadge(1);

                $acceptTime1 = new \xinge\TimeInterval(0, 0, 23, 59);
                $mess->addAcceptTime($acceptTime1);
                $res = $push->PushAccountList(0, [$user->xg_id], $mess, \xinge\XingeApp::IOSENV_DEV);
            }

            AppointmentStatusModel::addOrUp([
                'user_id' => $user_id,
                'order_id' => $order_id,
                'status' => 3
            ], ['user_id' => $user_id, 'order_id' => $order_id]);
            //产生socket事件消息
            $type = 'graborder';
            $this->createOrderSocketEvent($user_id, $order_id, $type);
        } catch (\Exception $e) {
            $LOCK->unlash($request->input('order_id'));
            return self::$RESPONSE_CODE->Code(5002);
        }
        $LOCK->unlash($request->input('order_id'));
        return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1000));
    }

    /**
     * 选人
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptOrder(Request $request)
    {
        if (!$request->has('other_user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('order_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        $other_user_id = $request->input('other_user_id');

        set_error_handler(function ($error_no, $error_message, $error_file, $error_line) {
            return self::$RESPONSE_CODE->Code(0, 5002);
        }, E_ERROR);

        try {
            $LOCK = new Lock();
            while (true) {
                $flag = $LOCK->judge($request->input('order_id') . ':' . $other_user_id);
                if ($flag === true) {
                    break;
                }
            }

            $order_info = AppointmentOrderModel::getInfoWhere(['order_id' => $request->input('order_id')]);

            if ($order_info['user_id'] == $other_user_id) {
                throw new \Exception('选择用户不能是自己');
            }

            $flag = AppointmentGrabOrderModel::getInfoWhere(['order_id' => $request->input('order_id'), 'accept_user_id' => $other_user_id]);
            if (!$flag) {
                $LOCK->unlash($request->input('order_id') . ':' . $other_user_id);
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1002));
            }

            if ($order_info['order_status'] == 2) {
                $LOCK->unlash($request->input('order_id') . ':' . $other_user_id);
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1002));
            }

            if ($order_info['game_status'] > 0) {
                $LOCK->unlash($request->input('order_id') . ':' . $other_user_id);
                return self::$RESPONSE_CODE->Code(5002);
            }

            DB::beginTransaction();
            $result = AppointmentOrderModel::upInfoWhere([
                'game_status' => 1,
                'accept_user_id' => $other_user_id,
                'game_start_at' => TIME,
            ], ['order_id' => $request->input('order_id')]);
            if (!$result) {
                DB::rollBack();
                $LOCK->unlash($request->input('order_id') . ':' . $other_user_id);
                return self::$RESPONSE_CODE->Code(5005);
            }

            $accepts = DB::table('appointment_status as a')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->where([
                    ['a.order_id', '=', $request->input('order_id')],
                ])
                ->select('u.system', 'u.xg_id', 'u.id')
                ->get();

            //信鸽推送
            $andorid_access_id = 2100300435;
            $andorid_secret_key = '96774184c1d236fe0cfb37744b9c0515';

            $ios_access_id = 2200302271;
            $ios_secret_key = '82477f2c9b7956386583dc094ddef0a1';
            foreach ($accepts as $accept) {

                if ($accept->id == $request->input('other_user_id')) {
                    //发送推送消息

                    if ($accept->system == 'andriod') {
                        $res = \xinge\XingeApp::PushAccountAndroid($andorid_access_id, $andorid_secret_key, "接单成功", "接单成功 进行下一步沟通才能挣到赏金哦~", $accept->xg_id);
                    }
                    if ($accept->system == 'ios') {
                        $push = new \xinge\XingeApp($ios_access_id, $ios_secret_key);
                        $mess = new \xinge\MessageIOS();
                        $mess->setExpireTime(86400);
                        $mess->setAlert("接单成功 进行下一步沟通才能挣到赏金哦~");
                        //$mess->setAlert(array('key1'=>'value1'));
                        $mess->setBadge(1);

                        $acceptTime1 = new \xinge\TimeInterval(0, 0, 23, 59);
                        $mess->addAcceptTime($acceptTime1);
                        $res = $push->PushAccountList(0, [$accept->xg_id], $mess, \xinge\XingeApp::IOSENV_DEV);
                    }
                } elseif ($accept->id != $order_info['user_id']) {
                    //发送推送消息
                    if ($accept->system == 'andriod') {
                        $res = \xinge\XingeApp::PushAccountAndroid($andorid_access_id, $andorid_secret_key, "接单失败", "该呼叫已被人抢到，完善个人资料才能赚到赏金哦~", $accept->xg_id);
                    }
                    if ($accept->system == 'ios') {
                        $push = new \xinge\XingeApp($ios_access_id, $ios_secret_key);
                        $mess = new \xinge\MessageIOS();
                        $mess->setExpireTime(86400);
                        $mess->setAlert("该呼叫已被人抢到，完善个人资料才能赚到赏金哦~");
                        //$mess->setAlert(array('key1'=>'value1'));
                        $mess->setBadge(1);

                        $acceptTime1 = new \xinge\TimeInterval(0, 0, 23, 59);
                        $mess->addAcceptTime($acceptTime1);
                        $res = $push->PushAccountList(0, [$accept->xg_id], $mess, \xinge\XingeApp::IOSENV_DEV);
                    }
                }
            }

            if ($order_info['pay_sum'] == 0) {
                //0元直接清
                AppointmentStatusModel::delInfoWhere(['user_id' => $other_user_id, 'order_id' => $request->input('order_id')]);
                AppointmentOrderModel::upInfoWhere(['game_status' => 2],
                    ['accept_user_id' => $other_user_id, 'game_status' => 1, 'order_status' => 1]);
            } else {
                AppointmentStatusModel::addOrUp([
                    'user_id' => $other_user_id,
                    'order_id' => $request->input('order_id'),
                    'status' => 2
                ], ['user_id' => $other_user_id, 'order_id' => $request->input('order_id')]);
            }

            AppointmentStatusModel::delInfoWhere([
                ['user_id', '<>', $request->input('other_user_id')],
                ['order_id', '=', $request->input('order_id')],
            ]);

            //环信推送
            $options['client_id'] = config('easemob.client_id');
            $options['client_secret'] = config('easemob.client_secret');
            $options['org_name'] = config('easemob.org_name');
            $options['app_name'] = config('easemob.app_name');
            $easemob = new \Easemob($options);
            //会话消息
            $other_user_info = User::getBasic($request->input('other_user_id'));
            $user_info = User::getBasic($order_info['user_id']);
            $to = $other_user_info->hx_id;
            $from = $user_info->hx_id;
            $content = '订单开始';

            $ext = [];
            $ext['title'] = '订单开始';
            $ext['type'] = '4';
            $ext['orderInfo'] = json_encode($order_info);
            $ext['redirectInfo'] = 'bask';
            $ext['nickname'] = $user_info->nickname;
            $ext['avatar'] = $user_info->avatar;
            $ext['tip'] = 1;
            $easemob->sendText($from, 'users', [$to], $content, $ext);

            $ext['nickname'] = $other_user_info->nickname;
            $ext['avatar'] = $other_user_info->avatar;
            $easemob->sendText($to, 'users', [$from], $content, $ext);

            //产生socket事件消息
            $type = 'acceptorder';
            $order_id = $request->input('order_id');
            $this->createOrderSocketEvent($other_user_id, $order_id, $type);
            //service_time
            if ($order_info['pay_sum'] > 0) {
                //产生结束消息队列
                //$this->createEndOrderMessageQueue($other_user_id, $order_info, $type);
            }
        } catch (\Exception $e) {
            $LOCK->unlash($request->input('order_id') . ':' . $other_user_id);
            DB::rollBack();
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->Code(5002);
        }

        $LOCK->unlash($request->input('order_id') . ':' . $other_user_id);
        DB::commit();
        return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1000));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrder(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('order_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        try {
            $order_info = AppointmentOrderModel::getInfoWhere(['order_id' => $request->input('order_id')]);
            if ($order_info['game_status'] > 0) {
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1005));
            }

            DB::beginTransaction();
            $result = AppointmentOrderModel::upInfoWhere(['order_status' => 2], ['order_id' => $request->input('order_id')]);
            if (!$result) {
                DB::rollBack();
                return self::$RESPONSE_CODE->Code(5005);
            }
            //退款
            $pay_sum = $order_info['pay_sum'];
            Wallet::addUserTransaction([
                'user_id' => $request->input('user_id'),
                'order_id' => $request->input('order_id'),
                'money' => $pay_sum,
                'title' => '退款',
                'desc' => '呼叫退款',
                'type' => 2,
                'status' => 1,
                'created_at' => TIME
            ]);

            /*$cancelAccepts = DB::table('appointment_status as a')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->where([
                    ['a.order_id', '=', $request->input('order_id')],
                ])->select('u.system', 'u.xg_id')->get();

            //信鸽推送
            $andorid_access_id = 2100300435;
            $andorid_secret_key = '96774184c1d236fe0cfb37744b9c0515';

            $ios_access_id = 2200302271;
            $ios_secret_key = '82477f2c9b7956386583dc094ddef0a1';
            foreach ($cancelAccepts as $cancelAccept) {
                //发送推送消息
                if ($cancelAccept['system'] == 'andriod') {
                    $res = \xinge\XingeApp::PushAccountAndroid($andorid_access_id, $andorid_secret_key, "订单被抢了", "该呼叫已被人抢到，完善个人资料才能赚到赏金哦~", $cancelAccept['xg_id']);
                }
                if ($cancelAccept['system'] == 'ios') {
                    $res = \xinge\XingeApp::PushAccountIos($ios_access_id, $ios_secret_key, "该呼叫已被人抢到，完善个人资料才能赚到赏金哦~", $cancelAccept['xg_id'], \xinge\XingeApp::IOSENV_DEV);
                }
            }*/
            AppointmentStatusModel::delInfoWhere(['order_id' => $request->input('order_id')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return self::$RESPONSE_CODE->Code(5002);
        }
        DB::commit();
        //产生socket事件消息
        $type = 'cancelorder';
        $user_id = $request->input('user_id');
        $order_id = $request->input('order_id');
        $this->createOrderSocketEvent($user_id, $order_id, $type);
        return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1000));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelGrab(Request $request)
    {
        set_time_limit(2);
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('order_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        set_error_handler(function ($error_no, $error_message, $error_file, $error_line) {
            return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1005));
        }, E_ERROR);
        try {
            $LOCK = new Lock();
            while (true) {
                $flag = $LOCK->judge($request->input('order_id') . ':' . $request->input('user_id'));
                if ($flag === true) {
                    break;
                }
            }

            $order_info = AppointmentOrderModel::getInfoWhere(['order_id' => $request->input('order_id')]);
            if ($order_info['game_status'] > 0) {
                return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1005));
            }

            DB::beginTransaction();
            $res = AppointmentGrabOrderModel::delInfoWhere(['accept_user_id' => $request->input('user_id'), 'order_id' => $request->input('order_id')]);

            AppointmentStatusModel::delInfoWhere([
                'user_id' => $request->input('user_id'),
                'order_id' => $request->input('order_id')
            ]);
            if (!$res) {
                return self::$RESPONSE_CODE->Code(5002);
            }

            //产生socket事件消息
            $type = 'graborder';
            $user_id = $request->input('user_id');
            $order_id = $request->input('order_id');
            $this->createOrderSocketEvent($user_id, $order_id, $type);
        } catch (Exception $e) {
            $LOCK->unlash($request->input('order_id') . ':' . $request->input('user_id'));
            DB::rollBack();
            return self::$RESPONSE_CODE->Code(5002);
        }

        DB::commit();
        $LOCK->unlash($request->input('order_id') . ':' . $request->input('user_id'));
        return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1000));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function endGame(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('order_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $order_info = AppointmentOrderModel::getInfoWhere(['order_id' => $request->input('order_id')]);

        if ($order_info['game_status'] == 2) {
            return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1004));
        }

        try {
            DB::beginTransaction();
            $accept_user = User::getBasic($order_info['accept_user_id']);
            $sociaty_info = Sociaty::getInfoWhere(['id' => $accept_user->sociaty_id]);
            $proportions = $sociaty_info->appointment_proportions;
            $get_sum = $order_info['pay_sum'] * $proportions;

            $result = AppointmentOrderModel::upInfoWhere([
                'game_status' => 2,
                'user_id' => $request->input('user_id'),
                'accept_user_get_sum' => max($get_sum, 0),
                'proportions' => $proportions,
                'game_end_at' => TIME,
            ], ['order_id' => $request->input('order_id')]);
            if (!$result) {
                return self::$RESPONSE_CODE->Code(5005);
            }

            //接单人收钱
            Wallet::addUserTransaction([
                'user_id' => $order_info['accept_user_id'],
                'order_id' => $request->input('order_id'),
                'money' => $get_sum,
                'title' => '收入',
                'desc' => '呼叫收入',
                'type' => 10,
                'status' => 1,
                'created_at' => TIME
            ]);

            AppointmentGrabOrderModel::delInfoWhere(['order_id' => $request->input('order_id')]);
            AppointmentStatusModel::delInfoWhere(['order_id' => $request->input('order_id')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return self::$RESPONSE_CODE->Code(5005);
        }

        DB::commit();

        $type = 'endgame';
        $user_id = $request->input('user_id');
        $order_id = $request->input('order_id');
        //service_time
        //产生结束消息队列
        $this->createEndOrderMessageQueue($user_id, $order_info, $type);
        return self::$RESPONSE_CODE->Code(0, self::orderStatusCode(1000));
    }

    private static function createOrderId()
    {
        $order_id_main = 'P' . date('YmdHis');
        $random = rand(10000000, 99999999);
        $id = (100 - $random % 100) % 100;
        $order_id = $order_id_main . str_pad($id, 6, '0', STR_PAD_LEFT);
        return $order_id;
    }

    private function createOrderSocketEvent($user_id, $order_id, $type)
    {
        ///////////发送消息到队列///////////
        $secretId = config('cloud.cloud_secret_id');
        $secretKey = config('cloud.cloud_secret_key');
        $endPoint = config('cloud.cd_end_point');
        $queue_name = CMQ_PRENAME . "socket-event";

        $my_account = new \Qcloudcmq\Account($endPoint, $secretId, $secretKey);
        $my_queue = $my_account->get_queue($queue_name);
        try {
            $cmqData = [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'event_name' => $type
            ];
            $msg = new \Qcloudcmq\Message(json_encode($cmqData));
            $my_queue->send_message($msg);
        } catch (\Exception $e) {
            throw new \Exception('服务器消息推送错误，请稍后重试');
        }
    }

    private function createEndOrderMessageQueue($user_id, $order_info, $type)
    {
        ///////////发送消息到队列///////////
        $secretId = config('cloud.cloud_secret_id');
        $secretKey = config('cloud.cloud_secret_key');
        $endPoint = config('cloud.cd_end_point');
        $queue_name = CMQ_PRENAME . "end-order";

        $my_account = new \Qcloudcmq\Account($endPoint, $secretId, $secretKey);
        $my_queue = $my_account->get_queue($queue_name);
        $delaySeconds = 60 * $order_info['service_time'];
        try {
            $cmqData = [
                'user_id' => $user_id,
                'order_id' => $order_info['order_id'],
                'event_name' => $type,
                'service_time' => $order_info['service_time'],
                'time' => date('Y-m-d H:i:s')
            ];
            $msg = new \Qcloudcmq\Message(json_encode($cmqData));
            if ($type == 'endgame') {
                $my_queue->send_message($msg);
            } else {
                $my_queue->send_message($msg, $delaySeconds);
            }
        } catch (\Exception $e) {

            throw new \Exception('服务器消息推送错误，请稍后重试');
        }
    }

}