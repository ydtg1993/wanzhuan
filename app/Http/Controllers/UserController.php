<?php

namespace App\Http\Controllers;

use App\Libraries\helper\Helper;
use App\Libraries\LBS\Services\LBSServer;
use App\Models\AppointmentOrderModel;
use App\Models\Checkin;
use App\Models\User;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class UserController extends Controller
{
    /**
     * 基本信息接口
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\UpdateException
     */
    public function showBasic(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        try {
            $response['data'] = User::getBasic($request->input('user_id'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }
        return response()->json($response);
    }

    /**
     * 用户信息接口
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\UpdateException
     */
    public function profile(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        if (!$request->has('user_id')) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`user_id`参数';
            return response()->json($response);
        }

        if (!$request->has('other_user_id')) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`other_user_id`参数';
            return response()->json($response);
        }

        try {
            $response['data'] = User::getProfile($request->input('user_id'), $request->input('other_user_id'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }
        return response()->json($response);
    }

    /**
     * 每日签到
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithUserID;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     * @throws \App\Exceptions\UpdateException;
     */
    public function checkIn(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        try {
            $response['data'] = Checkin::newCheck($request->input('user_id'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }
        return response()->json($response);
    }

    /**
     * 上传文件
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function postFile(Request $request)
    {
        if (!$request->has('file_paths')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('kind')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        try {

            Resource::newFile($request->input('file_paths'), $request->input('user_id'), $request->input('kind'));
        } catch (\Exception $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }
        return self::$RESPONSE_CODE->Code(0);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFile(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('kind')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $data = Resource::getAllWhere([
            ['user_id', '=', $request->input('user_id')],
            ['kind', '=', $request->input('kind')],
            ['status', '=', 0],
            ['danger_class', '<', 2]
        ], 'created_at', 'DESC');

        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delFile(Request $request)
    {
        if (!$request->has('file_paths')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $paths = $request->input('file_paths');
        try {
            foreach ($paths as $path) {
                $result = Resource::upInfoWhere(['status' => 1], ['path' => $path, 'user_id' => $request->input('user_id')]);
                if ($result) {
                    continue;
                }
                $result = Resource::upInfoWhere(['status' => 1], ['ori_path' => $path, 'user_id' => $request->input('user_id')]);
                if ($result) {
                    continue;
                }

                return self::$RESPONSE_CODE->Code(5005);
            }
        } catch (\Exception $e) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0);
    }

    /**
     * 导师标准技能
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithOtherUserID;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function normalSkill(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        if (!$request->has('master_user_id')) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`master_user_id`参数';
        } else {
            try {
                $response['data'] = User::getSkill($request->input('master_user_id'));
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }

        return response()->json($response);
    }

    /**
     * 用户提现
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function takeCash(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        try {
            $user = DB::table('wallets')->where('user_id', $request->input('user_id'))->first();
            if (!$user) {
                $response['code'] = '4000';
                $response['msg'] = '请求出错，`user_id`参数错误';
                return response()->json($response);
            }
            $money = $request->input('data.money');
            if ($money > $user->cash) {
                $response['code'] = '4000';
                $response['msg'] = '余额不足';
                return response()->json($response);
            }
            User::takeCash($request->only('user_id', 'data'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\UpdateException
     */
    public function easemob(Request $request)
    {
        if (!$request->has('hx_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('other_hx_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $hx_id = $request->input('hx_id');
        $other_hx_id = $request->input('other_hx_id');

        try {
            $user = DB::table('users')->where('hx_id', $hx_id)->select('id')->first();

            $other_user = DB::table('users')->where('hx_id', $other_hx_id)->select('id')->first();
            $appoint_order = AppointmentOrderModel::getInfoWhere(['user_id' => $user->id, 'accept_user_id' => $other_user->id, 'order_status' => 1, 'game_status' => 1]);
            $data = User::getHxProfile($other_user->id);

            $data['shut_game'] = 0;
            $data['dialog'] = 0;
            $data['appointment_order_id'] = '';
            if ($appoint_order) {
                $data['shut_game'] = 1;
                if ($appoint_order['pay_sum'] == 0) {
                    $data['shut_game'] = 0;
                }
                $data['dialog'] = 1;
                $data['appointment_order_id'] = $appoint_order['order_id'];
            }
        }catch (\Exception $e){
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * 根据环信id获取信息
     * @author AdamTyn
     * @param array
     * @return mixed
     */
    public function avatar($hx_id)
    {
        $user = DB::table('users')->where('hx_id', $hx_id)->select('id', 'avatar')->first();
        $avatar = 'http://image.wanzhuanhuyu.cn/437E2D4F-A386-4B2F-82C4-4A302FC19B97.JPG';
        if ($user) {
            $avatar = $user->avatar;
        }
        echo file_get_contents($avatar);
        exit;
    }
}