<?php

namespace Service\Notice;

use Illuminate\Support\ServiceProvider;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/18
 * Time: 18:43
 */
class NoticeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('notice', function ($app) {
            return new XingeAppServer($app['config']['xinge']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'notice',
        ];
    }
}