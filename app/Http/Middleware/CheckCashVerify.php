<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Verify;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class CheckCashVerify
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
            if (!$request->has('user_id')) {
                $response['code'] = '4000';
                $response['msg'] = '请求出错，缺少`user_id`参数';
                return response()->json($response);
            }

            if (!$request->has('code')) {
                $response['code'] = '4000';
                $response['msg'] = '请求出错，缺少`code`参数';
                return response()->json($response);
            }

            if (!$request->has('data.type')) {
                $response['code'] = '4000';
                $response['msg'] = '请求出错，缺少`type`参数';
                return response()->json($response);
            }

            if (!$request->has('data.cash_account')) {
                $response['code'] = '4000';
                $response['msg'] = '请求出错，缺少`cash_account`参数';
                return response()->json($response);
            }

            if (!$request->has('data.money')) {
                $response['code'] = '4000';
                $response['msg'] = '请求出错，缺少`money`参数';
                return response()->json($response);
            }

            $user = DB::table('users')->where('id', $request->input('user_id'))->first();
            if(!$user){
                $response['code'] = '4000';
                $response['msg'] = '请求出错，`user_id`参数错误';
                return response()->json($response);
            }
            $mobile = $user->mobile;

            $verify = Verify::where('mobile', $mobile)->where('type',2)
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
