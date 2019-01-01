<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/6 0006
 * Time: 下午 2:59
 */

namespace App\Http\AppointmentControllers;


use App\Models\AppointmentOrderModel;
use App\Models\AppointmentStatusModel;
use App\Models\Geo;
use App\Models\UserTag;
use App\Libraries\helper\Helper;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\Request;

require_once __DIR__ . '/../../Libraries/cmq-sdk/cmq_api.php';

class UserController extends Controller
{
    public function getAppointmentStatus(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $appointment_status_info = AppointmentStatusModel::getInfoWhere([
            'user_id' => $request->input('user_id'),
            'status' =>1
        ]);
        if ($appointment_status_info) {
            //TODO
            //产生socket事件消息
            $type = 'appointmentStatus';
            $this->createOrderSocketEvent($request->input('user_id'), $appointment_status_info['order_id'], $type);

            return self::$RESPONSE_CODE->Code(0, [
                'order_id' => $appointment_status_info['order_id'],
                'promoter' => 1,
                'promoter_avatar'=>'',
                'promoter_gender'=>'',
                'other_user_id'=>0,
            ]);
        }

        $appointment_status_info = AppointmentStatusModel::getInfoWhere([
            'user_id' => $request->input('user_id'),
            'status' =>3
        ]);
        if ($appointment_status_info) {
            $order_info = AppointmentOrderModel::getInfoWhere(['order_id'=>$appointment_status_info['order_id']]);
            $master_user_info = User::getBasic($order_info['user_id']);
            return self::$RESPONSE_CODE->Code(0, [
                'order_id' => $appointment_status_info['order_id'],
                'promoter' => 0,
                'promoter_avatar'=>$master_user_info->avatar,
                'promoter_gender'=>$master_user_info->sexy,
                'other_user_id'=>$master_user_info->id,
            ]);
        }

        return self::$RESPONSE_CODE->Code(0,(object)[]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setMapPosition(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('longitude')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('latitude')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $flag = (boolean)$request->input('gps');

        try {
            if ($flag) {
                $result = Geo::set($request->input('user_id'), $request->input('longitude'), $request->input('latitude'));

                if (!$result) {
                    return self::$RESPONSE_CODE->Code(5005);
                }
                $data = ['longitude' => $request->input('longitude'), 'latitude' => $request->input('latitude')];
                return self::$RESPONSE_CODE->Code(0, $data);
            }

            $user = User::getBasic($request->input('user_id'));
            $location = $user->location;
            if ($location) {
                $data = Helper::curlRequest('http://api.map.baidu.com/geocoder',
                    ['address' => $location, 'output' => 'json', 'src' => 'webapp.baidu.openAPIdemo'], 'GET');
                $data = (array)json_decode($data, true);
                if ($data['status'] == 'OK') {
                    $longitude = $data['result']['location']['lng'];
                    $latitude = $data['result']['location']['lat'];

                    Helper::randomLbs($longitude, $latitude);
                    $result = Geo::set($request->input('user_id'), $longitude, $latitude);

                    if (!$result) {
                        return self::$RESPONSE_CODE->Code(5005);
                    }
                    $data = ['longitude' => $longitude, 'latitude' => $latitude];
                    return self::$RESPONSE_CODE->Code(0, $data);
                }
            }

            $data = Helper::curlRequest('http://api.map.baidu.com/location/ip',
                ['ip' => $request->getClientIp(), 'ak' => 'rdBSDGmzv3m5kvhFMmZrNg5LvTkVx62G'], 'GET');
            $data = (array)json_decode($data, true);
            if ($data['status'] != 0) {
                return self::$RESPONSE_CODE->Code(5005);
            }
            $longitude = round($data['content']['point']['x'] / 100000, 6);
            $latitude = round($data['content']['point']['y'] / 100000, 6);
            Helper::randomLbs($longitude, $latitude);

            $result = Geo::set($request->input('user_id'), $longitude, $latitude);

            if (!$result) {
                return self::$RESPONSE_CODE->Code(5005);
            }
            $data = ['longitude' => $longitude, 'latitude' => $latitude];
            return self::$RESPONSE_CODE->Code(0, $data);
        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->setMsg($e->getMessage())->Code(5002);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagList(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $game_list = (Game::getAllWhere(['game_type' => 3]))->toArray();

        $user_id = $request->input('user_id');
        $user_tags = UserTag::getAllWhere(['user_id' => $user_id]);
        $user_tag_ids = [];
        if ($user_tags) {
            $user_tag_ids = array_column($user_tags, 'game_id');
        }

        $data = [];
        foreach ($game_list as $game) {
            if ($game['id'] == 34) {
                //删除语音聊天
                continue;
            }
            $game['select'] = 0;
            if (in_array($game['id'], $user_tag_ids)) {
                $game['select'] = 1;
            }
            $data[] = $game;
        }

        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setTags(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('tags')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $game_ids = explode(',', $request->input('tags'));

        $data = [];
        foreach ($game_ids as $game_id) {
            $data[] = ['user_id' => (int)$request->input('user_id'), 'game_id' => (int)$game_id];
        }
        UserTag::delInfoWhere(['user_id' => $request->input('user_id')]);
        $result = UserTag::addAll($data);

        if (!$result) {
            return self::$RESPONSE_CODE->Code(5005);
        }
        return self::$RESPONSE_CODE->Code(0);
    }

    private function createOrderSocketEvent($user_id, $order_id, $type)
    {
        ///////////发送消息到队列///////////
        $secretId = config('cloud.cloud_secret_id');
        $secretKey = config('cloud.cloud_secret_key');
        $endPoint = config('cloud.cd_end_point');
        $queue_name = CMQ_PRENAME."socket-event";

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
}