<?php

namespace YitOS\ClientToken;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * 客户端令牌服务
 * @version 2.0.0
 * @package YitOS\ClientToken
 * @see \Illuminate\Support\ServiceProvider
 * @author yiller <yiller@live.com>
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * 启动时加载配置文件
     * @access public
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/config/client_token.php', 'client_token');
    }

    /**
     * 注册client单件对象
     * @access public
     * @return void
     */
    public function register()
    {
        $this->app->singleton('client', function ($app) {
            $validator = $app['config']['client_token.validator'];
            $bundles = $app['config']['client_token.bundles'];
            return new Client($app, $validator, $bundles);
        });
    }

    /**
     * 公开client提供调用
     * @access public
     * @return array
     */
    public function provides()
    {
        return ['client'];
    }
}
