<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class UserUpdateController extends Controller
{
    /**
     * 更新手机号
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithMobile;
     * @middleware \App\Http\Middleware\CheckVerify;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     * @throws \App\Exceptions\UpdateException;
     */
    public function setMobile(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        try {
           User::setMobile($request->only('user_id', 'mobile'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }

    /**
     * 更新昵称
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithUserID;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\UpdateException;
     */
    public function setNickname(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        if (!$request->has('nickname')) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`nickname`参数';
        } else {
            try {
                User::setNickname($request->only('user_id', 'nickname'));
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }

        return response()->json($response);
    }

    /**
     * 更新基本信息
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithData;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function setBasic(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        try {
            User::setBasic($request->only('user_id', 'data'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }
}