<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{
    /**
     * 检查验证码
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithMobile;
     * @middleware \App\Http\Middleware\CheckVerify;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function checkVerify()
    {
        return response()->json(array('code' => '0'));
    }


    /**
     * 投诉导师接口
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithData;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function complaint(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        try {
            Complaint::addOne($request->only('user_id','data'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }

        return response()->json($response);
    }

    /**
     * 反馈建议接口
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithData;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function feedback(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        try {
            Feedback::addOne($request->only('user_id','data'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }
        return response()->json($response);
    }

    /**
     * 举报接口
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithData;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function report(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        try {
            Feedback::report($request->only('user_id','data'));
        } catch (QueryException $queryException) {
            $response['code'] = '5002';
            $response['msg'] = '无法响应请求，服务端异常';
        }
        return response()->json($response);
    }

    /**
     * Forbidden Warning
     * @author AdamTyn
     * @return \Illuminate\Http\Response;
     */
    public function forbidden()
    {
        return response()->json([
            'code'=>'4003',
            'msg' =>'forbidden'
        ]);
    }

    /**
     * 获取app版本
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithData;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function getVersion(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        if (!$request->input('system')) {
            $response['code'] = '5002';
            $response['msg'] = '请求出错，缺少`system`参数';
            return response()->json($response);
        }
        $system = $request->input('system');
        $systemArray = ['ios', 'android'];
        if(!in_array($system, $systemArray)){
            $response['code'] = '5002';
            $response['msg'] = '请求出错，`system`参数错误';
            return response()->json($response);
        }

        $data = (object)null;
        $version = DB::table('app_version')->where('system', $system)->where('status', 1)->orderBy('id', 'DESC')->first();
        if($version){
            $data = $version;
        }
        $response['data'] = $data;
        return response()->json($response);
    }

    /**
     * 获取apple
     * @author AdamTyn
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithData;
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function getAppleProduct(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        
        $goods = DB::table('apple_virtual_goods')->where('status', 1)->orderBy('id', 'ASC')->get();
        $response['data'] = $goods;
        return response()->json($response);
    }
}