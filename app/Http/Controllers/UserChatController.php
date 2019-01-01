<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Xghx;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class UserChatController extends Controller
{
    /**
     * 绑定环信信鸽（停用）
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithData;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function bindXgHx(Request $request)
    {
        $response = array('code' => '0');

        try {
            Xghx::bindXgHx($request->input('data'), $request->input('user_id'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }

    /**
     * 查看环信信鸽
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\GetException;
     */
    public function getXgHx(Request $request)
    {
        $response = array('code' => '0');

        try {
            $temp = Xghx::getXgHx($request->only('user_id'));

            if ($temp) {
                $response['data'] = $temp;
            } else {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
            $temp=null;
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }

    /**
     * 聊天对象的信息
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithOtherUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function chatInfo(Request $request)
    {
        $response = array('code' => '0');

        try {
            $response['data'] = User::chatInfo($request->input('user_id_1'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }
}