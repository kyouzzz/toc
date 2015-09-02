<?php
namespace Lib\Http;

class Controller
{

    private $params = null;

    private $request = null;

    public function setParams($params = null)
    {
        $this->params = $params;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }
    /**
     * 获取参数
     * @param  [type] $key     [参数名]
     * @param  [type] $default [没有的话默认值]
     * @return [type]          [description]
     */
    public function params($key, $default = null)
    {
        $params = $this->params;
        if(isset($params["$key"])) {
            return $params["$key"];
        }
        return $default;
    }

}