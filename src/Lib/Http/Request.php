<?php
namespace Lib\Http;

class Request
{

    public function getUri()
    {
        if (isset($_SERVER["REQUEST_URI"])) {
            return $_SERVER["REQUEST_URI"];
        }
        return "";
    }
    /**
     * 获取请求方法 get || post
     * @return [type] [description]
     */
    public function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    /**
     * 判断是否是 GET 请求
     * @return [type] [description]
     */
    public function isGet()
    {
        return strtoupper($this->getRequestMethod()) == "GET";
    }
    /**
     * 判断是否是 POST 请求
     * @return [type] [description]
     */
    public function isPost()
    {
        return strtoupper($this->getRequestMethod()) == "POST";
    }
    /**
     * 判断是否是 ajax 请求
     * @return [type] [description]
     */
    public function isAjax()
    {
        if (!isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
            return false;
        }
        $http_x_request = $_SERVER["HTTP_X_REQUESTED_WITH"];
        if ( $http_x_request && strtoupper($http_x_request) == "XMLHTTPREQUEST") {
            return true;
        }
        return false;
    }
    /**
     * 获取请求中的参数
     * @return [type] [description]
     */
    public function getParameters()
    {
        $result = array();
        if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            $raw_data = (array) json_decode($GLOBALS['HTTP_RAW_POST_DATA'], 1);
            $result = array_merge($result, $raw_data);
        }
        $result = array_merge($result, $_REQUEST);
        return $result;
    }
    /**
     * 获取请求参数
     * @param  [type] $key           [description]
     * @param  string $default_value [description]
     * @return [type]                [description]
     */
    public function getParameter($key, $default_value='')
    {
        $params = $this->getParameters();
        if (isset($params[$key])) {
            return $params[$key];
        }
        return $default_value;
    }


}
