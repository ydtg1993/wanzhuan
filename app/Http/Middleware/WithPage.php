<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class WithPage
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
        if (!($request->has('paginate'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`paginate`参数';
            return response()->json($response);
        } elseif (intval($request->input('paginate')) < 1) {
            $response['code'] = '4002';
            $response['msg'] = '请求出错，`paginate`参数无效或错误';
            return response()->json($response);
        }

        return $next($request);
    }
}
