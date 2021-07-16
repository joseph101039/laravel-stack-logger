<?php

namespace RDM\StackLogger;

use Illuminate\Support\ServiceProvider;

class StackLoggerServiceProvider extends ServiceProvider
{

    /**
     * config/logging.php 內設置的 channel 名稱
     */
    const CHANNEL = 'stack_logger' ;

    /**
     * 提示言延遲載入這個 service container, 也就是說不是每個 request 初始化時
     * 都會預載, 只有第一次呼叫到時才會解析並實例化
     * note: laravel 6 以上用 implement DeferrableProvider 來提示此功能
     *
     * 可以用
     * @var bool
     */
    protected $defer = true;

    /**
    * Register the custom logger for settle job
    * @see https://laravel.com/docs/8.x/providers
    *
    * @return void
    */
    public function register()
    {
        $this->app->singleton('stackLogger', function($app) {
            return new \RDM\StackLogger\Managers\GCPLogManager(config('logging.channels.' . self::CHANNEL));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }

    public function provides()
    {
        return ['stackLogger'];   // 或是 \RDM\StackLogger\Facades\GCPLog::class 都可以
    }


}
