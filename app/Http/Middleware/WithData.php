<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class WithData
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
        if (!($request->has('data'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`data`参数';
            return response()->json($response);
        } elseif (count($request->input('data')) < 1) {
            $response['code'] = '4002';
            $response['msg'] = '请求出错，`data`无效或错误';
            return response()->json($response);
        }

        return $next($request);
    }
}
