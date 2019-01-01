<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class WithType
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
        if (!($request->has('type'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`type`参数';
            return response()->json($response);
        } elseif (intval($request->input('type')) < 0) {
            $response['code'] = '4002';
            $response['msg'] = '请求出错，`type`参数无效或错误';
            return response()->json($response);
        }

        return $next($request);
    }
}
