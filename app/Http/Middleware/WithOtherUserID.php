<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class WithOtherUserID
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
        if (!($request->has('other_user_id'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`other_user_id`参数';
            return response()->json($response);
        }elseif ($request->input('user_id') == $request->input('other_user_id')) {
            $response['code'] = '2000';
            $response['msg'] = '不可作用于当前用户自身';
            return response()->json($response);
        }elseif(DB::table('users')->where('id',$request->input('other_user_id'))->doesntExist()){
            $response['code'] = '1001';
            $response['msg'] = '请求出错，`other_user_id`无效或错误';
            return response()->json($response);
        }

        return $next($request);
    }
}
