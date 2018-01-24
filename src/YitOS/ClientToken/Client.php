<?php

namespace YitOS\ClientToken;

use Carbon\Carbon;
use YitOS\ClientToken\Contracts\Validator;

/**
 * 客户端令牌
 * @version 2.0.0
 * @package YitOS\ClientToken
 * @author yiller <yiller@live.com>
 */
class Client
{
    /**
     * 应用程序实例
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * 令牌验证器对象或类名
     * @var \YitOS\ClientToken\Contracts\Validator|string
     */
    protected $validator;

    /**
     * 设备编号
     * @var string
     */
    protected $device = '';

    /**
     * 商户名
     * @var string
     */
    protected $merchant = '';

    /**
     * 扩展的额外参数
     * @var array
     */
    protected $bundles = [];

    /**
     * 扩展的属性值
     * @var array
     */
    protected $attributes = [];

    /**
     * 实例化一个客户端对象
     * @access public
     * @param \Illuminate\Foundation\Application $app
     * @param string $validator
     * @param array $bundles
     * @return void
     */
    public function __construct($app, $validator, array $bundles)
    {
        $this->app = $app;
        $this->validator = $validator;
        $this->bundles = $bundles;
    }

    /**
     * 验证客户端有效性
     * @access public
     * @param string $merchant
     * @param string $device
     * @param boolean $initial
     * @return boolean
     */
    public function validate($merchant, $device, $initial = false)
    {
        $rec = $this->getRepoTable()->where('merchant', $merchant)->first();
        if (is_null($rec)) {
            return false;
        }
        $secretKey = ($rec->mode === 'dynamic' && !$initial) ? $this->getSecretKey($merchant, $device) : $rec->secret_key;
        $validator = $this->getValidator($merchant, $secretKey);
        return !is_null($validator) && $validator->isValid() && ($this->merchant = $merchant) && ($this->device = $device);
    }

    /**
     * 注册客户端并返回密钥字符串
     * @access public
     * @param array $bundles
     * @return string
     */
    public function registry($bundles)
    {
        $rec = $this->getRepoTable()->where('merchant', $this->merchant)->first();
        if (is_null($rec)) {
            return '';
        }
        $data = [];
        foreach ($bundles as $key => $value) {
            if (!in_array($key, $this->bundles)) {
                continue;
            }
            $data[$key] = $this->attributes[$key] = $value;
        }
        $data['token_merchant'] = $this->merchant;
        $data['token_secret_key'] = $rec->mode === 'dynamic' ? $this->generateSecretKey() : $rec->secret_key;
        $data['client_ip'] = $this->app['request']->ip();
        $data['renewal_time'] = Carbon::now();
        return $this->getClientTable()->updateOrInsert(['device' => $this->device], $data) ? $data['token_secret_key'] : '';
    }

    /**
     * 获得客户端仓库表查询对象
     * @access protected
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getClientTable()
    {
        return $this->app['db']->connection()->table('client_repositories');
    }

    /**
     * 获得令牌仓库表查询对象
     * @access protected
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getRepoTable()
    {
        return $this->app['db']->connection()->table('token_repositories')->where('status', 'normal');
    }

    /**
     * 获得验证器对象
     * @access protected
     * @param string $merchant
     * @param string $secretKey
     * @return \YitOS\ClientToken\Contracts\Validator
     */
    protected function getValidator($merchant = '', $secretKey = '')
    {
        if ($this->validator instanceof Validator) {
            return $this->validator;
        }
        if (empty($merchant) || empty($secretKey)) {
            return null;
        }
        $class = '\\'.ltrim($this->validator, '\\');
        $instance = new $class($merchant, $secretKey);
        if (!$instance instanceof Validator) {
            return null;
        }
        return $this->validator = $instance;
    }

    /**
     * 获得当前密钥
     * @access protected
     * @param string $merchant
     * @param string $device
     * @return string
     */
    protected function getSecretKey($merchant, $device)
    {
        $rec = $this->getClientTable()->where(['device' => $device, 'token_merchant' => $merchant])->first();
        return is_null($rec) ? '' : $rec->token_secret_key;
    }

    /**
     * 生成会话令牌字符串
     * @access protected
     * @return string
     */
    protected function generateSecretKey()
    {
        return substr(strtoupper(hash_hmac('sha256', str_random(8), 'YitOS_OpenSSL_DESCryptor')), 0, 16);
    }

    /**
     * 获得动态属性值
     * @access public
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (!in_array($name, $this->bundles)) {
            return null;
        }
        if (empty($this->attributes)) {
            $rec = $this->getClientTable()->where(['device' => $this->device, 'token_merchant' => $this->merchant])->first();
            if (is_null($rec)) {
                return null;
            }
            foreach ($this->bundles as $bundle) {
                $this->attributes[$bundle] = isset($rec->{$bundle}) ? $rec->{$bundle} : '';
            }
        }
        return $this->attributes[$name];
    }

    /**
     * 判断是否设置动态属性值
     * @access public
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        if (!in_array($name, $this->bundles)) {
            return false;
        }
        return !empty($this->{$name});
    }

    /**
     * 动态调用 Validator 的方法
     * @access public
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this->validator instanceof Validator) {
            return $this->validator->{$method}(...$parameters);
        }
        return null;
    }
}
