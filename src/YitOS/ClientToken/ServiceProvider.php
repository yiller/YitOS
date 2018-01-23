<?php

namespace YitOS\ClientToken;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * 设备令牌服务
 * @version 2.0.0
 * @package YitOS\ClientToken
 * @see \Illuminate\Support\ServiceProvider
 * @author yiller <yiller@live.com>
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * 注册client单件对象
     * @access public
     * @return void
     */
    public function register()
    {
        $this->app->singleton('client', function($app) {

        });
    }

    /**
     * 公开client提供调用
     * @access public
     * @return array
     */
    public function provides() {
        return ['client'];
    }
}
