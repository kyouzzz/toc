<?php
namespace Lib\Http;

/**
* 拦截器基类
*/
abstract class Interceptor
{

    protected $params = null;
    
    abstract function before();
    // abstract function after();

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function getParams()
    {
        return $this->params;
    }

}

