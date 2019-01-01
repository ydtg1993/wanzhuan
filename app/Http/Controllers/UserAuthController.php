<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthException;
use App\Models\Authorize;
use App\Models\Identity;
use App\Models\Master;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Mockery\Exception;

class UserAuthController extends Controller
{
    /**
     * 实名认证
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithData;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\AuthException;
     */
    public function identity(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        try {
            Identity::canIdentity($request->only('user_id', 'data'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }

    /**
     * 查看实名认证状态
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\AuthException;
     */
    public function checkIdentity(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        try {
            $response['data'] = Identity::CheckIdentity($request->only('user_id'));// 人工审核
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }

    /**
     * 导师游戏认证
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithData;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\AuthException;
     */
    public function _authorize(Request $request)
    {
        try {
            Authorize::canAuth($request->only('user_id', 'data'));// 人工审核
        } catch (AuthException $queryException) {
            return self::$RESPONSE_CODE->setMsg($queryException->getMessage())->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0);
    }

    /**
     * 导师游戏认证
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithData;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\AuthException;
     */
    public function cancelAuthorize(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        if (!$request->has('user_id') || !$request->input('user_id')) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`user_id`参数或参数为空';
            return response()->json($response);
        }
        try {
            Authorize::cancelAuthorize($request->input('user_id'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }

    /**
     * 查看导师游戏认证状态
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\AuthException;
     */
    public function checkAuth(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        try {
            $response['data'] = Authorize::CheckAuth($request->only('user_id', 'game_id'));// 人工审核
        }  catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }

    /**
     * 查看导师游戏认证状态
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\AuthException;
     */
    public function userAuthInfo(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        try {
            $result = Authorize::UserAuthInfo($request->only('user_id'));// 人工审核
            if(!$result){
                $response['data'] = (object)null;
            } else {
                $response['data'] = $result;
            }
        }  catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }

    /**
     * 查看导师游戏认证状态
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\AuthException;
     */
    public function authProgressInfo(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        try {
            $result = Authorize::authProgressInfo($request->only('user_id'));// 人工审核
            if(!$result){
                $response['data'] = (object)null;
            } else {
                $response['data'] = $result;
            }
        }  catch (QueryException $queryException) {
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
    public function directIdentity(Request $request)
    {
        if(!$request->has('user_id')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        $user_id = $request->input('user_id');
        try {
            \DB::beginTransaction();
            $user = User::getBasic($user_id);
            $authorizesInfo = Authorize::getInfoWhere([
                'user_id' => $user_id,
                'status' => 2
            ]);

            $skillData = [
                'master_user_id' => $authorizesInfo->user_id,
                'game_id' => $authorizesInfo->game_id,
                'game_name' => $authorizesInfo->game_name,
                'server_id' => $authorizesInfo->server_id,
                'game_server' => $authorizesInfo->game_server,
                'level_id' => $authorizesInfo->level_id,
                'game_level' => $authorizesInfo->game_level,
                'unit' => '',
                'created_at' => TIME,
                'status' => 1,
                'price' => 0
            ];

            $level_type = 1;
            if($user->sexy == '男'){
                $priceInfo = \DB::table('game_man_charge')->where('game_id', $authorizesInfo->game_id)->where('level_id', $authorizesInfo->level_id)->first();
                if($priceInfo){
                    $skillData['unit'] = $priceInfo->unit;
                    if($user->user_level == 1){
                        $level_type = 1;
                        $skillData['price'] = $priceInfo->normal_price;
                    }
                    if($user->user_level == 2){
                        $level_type = 2;
                        $skillData['price'] = $priceInfo->better_price;
                    }
                    if($user->user_level == 3){
                        $level_type = 3;
                        $skillData['price'] = $priceInfo->super_price;
                    }
                }
            }
            if($user->sexy == '女'){
                $priceInfo = \DB::table('game_woman_charge')->where('game_id', $authorizesInfo->game_id)->first();
                if($priceInfo){
                    $level_type = 1;
                    $skillData['unit'] = $priceInfo->unit;
                    $skillData['price'] = $priceInfo->normal_price;
                }
            }

            \DB::table('skills')->insert($skillData);

            Authorize::upInfoWhere(['status' => 4],
                ['user_id' => $user_id, 'status' => 2]);

            User::upInfoWhere(['isMaster' => 1], ['id' => $user_id]);

            $master = Master::masterInfo($user_id);
            if (!$master) {
                Master::addMaster([
                    'user_id' => $user_id,
                    'sex' => $user->sexy,
                    'order_count' => 0,
                    'arg_score' => 0,
                    'level_type' => $level_type,
                    'status' => 1
                ]);
            }
        }catch (Exception $e){
            \DB::rollBack();
            return self::$RESPONSE_CODE->Code(5002);
        }

        \DB::commit();
        return self::$RESPONSE_CODE->Code(0);
    }


}