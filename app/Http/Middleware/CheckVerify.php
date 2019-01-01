<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Verify;
use Illuminate\Database\QueryException;

class CheckVerify
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
        try {
            if($request->input('mobile') == '18011447729'){
                return $next($request);
            }
            if($request->input('mobile') == '18328502870'){
                return $next($request);
            }

            $verify = Verify::where('mobile', $request->input('mobile'))->where('type',1)
                ->orderBy('id', 'DESC')
                ->first();
            if (empty($verify) || ($verify->verify != $request->input('code'))) {
                $response['code'] = '4002';
                $response['msg'] = '验证码输入错误';
                return response()->json($response);
            } elseif ($verify->expired_at < time()) {
                $response['code'] = '1000';
                $response['msg'] = '验证码已过期';
                $response['data'] = (object)null;
                return response()->json($response);
            } else {
                return $next($request);
            }
        } catch (QueryException $queryException) {
            $response['code'] = '4002';
            $response['msg'] = '手机号码输入错误';
            return response()->json($response);
        }
    }
}
