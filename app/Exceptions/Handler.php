<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        // 如果config配置debug为true ==>debug模式的话让laravel自行处理
//        if(config('app.debug')){
//            return parent::render($request, $e);
//        }

        //return parent::render($request, $e);
        return $this->handle($request,$e);
    }

    public function handle($request,Exception $e)
    {
        if(stripos($_SERVER['HTTP_USER_AGENT'],"android")!=false||stripos($_SERVER['HTTP_USER_AGENT'],"ios")!=false||stripos($_SERVER['HTTP_USER_AGENT'],"wp")!=false){
            $response['code'] = strval($e->getCode());
            $response['msg'] = $e->getMessage();

            if ($e instanceof ValidationException) {
                $response['code'] = 4000;
                $response['msg'] = $e->validator->errors()->first();
            }
            return response()->json($response);
        }else{
            return parent::render($request, $e);
        }

//        if($e instanceof AuthException)
//        {
//            $response['code'] = $e->getCode();
//            $response['msg'] = $e->getMessage();
//            return response()->json($response);
//        }
//        return parent::render($request, $e);
    }
}
