<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class WithUserID
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
      // if (!($request->has('user_id'))) {
      //    $response['code'] = '4000';
      //    $response['msg'] = '请求出错，缺少`user_id`参数';
      //    return response()->json($response);
      // } elseif (($request->user()->id) != ($request->input('user_id'))) {
      //    $response['code'] = '4005';
      //    $response['msg'] = '请求出错，`user_id`与令牌不匹配';
      //    return response()->json($response);
      // } elseif (DB::table('users')->where('id', $request->input('user_id'))->doesntExist()) {
      //    $response['code'] = '1001';
      //    $response['msg'] = '请求出错，`user_id`无效或错误';
      //    return response()->json($response);
      // }
      return $next($request);
    }
}
