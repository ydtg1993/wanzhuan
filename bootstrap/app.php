<?php

require_once __DIR__ . '/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__ . '/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__ . '/../')
);

$app->withFacades();
$app->withEloquent();
$app->configure('cloud');
$app->configure('code');
$app->configure('pay');
$app->configure('master');
$app->configure('easemob');
$app->configure('xinge');
$app->configure('redis');
$app->configure('redis_key');
$app->configure('applet');
$app->configure('cos');
$app->configure('swoole');

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    App\Http\Middleware\GlobalMiddleware::class
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'userId' => App\Http\Middleware\WithUserID::class,
    'mobile' => App\Http\Middleware\WithMobile::class,
    'verify' => App\Http\Middleware\CheckVerify::class,
    'cashVerify' => App\Http\Middleware\CheckCashVerify::class,
    'paginate' => App\Http\Middleware\WithPage::class,
    'data' => App\Http\Middleware\WithData::class,
    'otherId' => App\Http\Middleware\WithOtherUserID::class,
    'type' => App\Http\Middleware\WithType::class
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(\Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider::class);
$app->register(Yansongda\LaravelPay\PayServiceProvider::class);
$app->register(Service\Notice\NoticeServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => '',
], function ($router) {
    require __DIR__ . '/../routes/route.php';
});

return $app;
