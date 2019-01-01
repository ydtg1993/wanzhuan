<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class GlobalMiddleware
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
       // if (!app()->environment('production')) {
       //     $response['code'] = '5003';
       //     $response['msg'] = 'API系统维护';
       //     return response()->json($response);
       // }

        return $next($request);
    }
}
