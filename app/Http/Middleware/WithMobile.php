<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class WithMobile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!($request->has('mobile'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`mobile`参数';
            return response()->json($response);
        } elseif(!mobile_validator($request->input('mobile'))){
            $response['code'] = '1000';
            $response['msg'] = '手机号码格式错误';
            return response()->json($response);
        }

        return $next($request);
    }
}
