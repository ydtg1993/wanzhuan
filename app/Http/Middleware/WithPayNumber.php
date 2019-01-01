<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class WithPayNumber
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!($request->has('order_id'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`order_id`参数';
            return response()->json($response);
        }
        switch (intval($request->input('type'))) {
            case 0:
                if (DB::table('normal_orders')->where('id', 'order_id')->doesntExist()) {
                    $response['code'] = '4002';
                    $response['msg'] = '请求出错，`type`参数无效或错误';
                    return response()->json($response);
                }
                break;
            case 1:
                if (DB::table('team_orders')->where('id', 'order_id')->doesntExist()) {
                    $response['code'] = '4002';
                    $response['msg'] = '请求出错，`type`参数无效或错误';
                    return response()->json($response);
                }
                break;
            case 2:
                if (DB::table('skill_orders')->where('id', 'order_id')->doesntExist()) {
                    $response['code'] = '4002';
                    $response['msg'] = '请求出错，`type`参数无效或错误';
                    return response()->json($response);
                }
                break;
            default:
                $response['code'] = '4002';
                $response['msg'] = '请求出错，`type`参数无效或错误';
                return response()->json($response);
        }

        return $next($request);
    }
}