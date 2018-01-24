<?php

namespace YitOS\ClientToken\Contracts;

/**
 * 客户端令牌验证器接口
 * @version 2.0.0
 * @package YitOS\ClientToken\Contracts
 * @author yiller <yiller@live.com>
 */
interface Validator
{
    /**
     * 请求是否可验证
     * @access public
     * @return boolean
     */
    public function isValid();
}
