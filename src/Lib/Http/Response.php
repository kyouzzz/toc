<?php
namespace Lib\Http;

class Response
{

    /**
     * 页面展示
     * @param  [type]  $html_file [description]
     * @param  array   $data      [description]
     * @param  integer $http_code [description]
     * @return [type]             [description]
     */
    public static function view($html_file = "", $data = [], $http_code = 200)
    {
        http_response_code($http_code);
        if (empty($html_file)) {
            return true;
        }
        $page = Page::getInstance();
        $page->parseToBuffer($html_file, $data);
        return $page->outPut();
    }
    /**
     * 页面跳转
     * @param  [type]  $url       [description]
     * @param  integer $http_code [description]
     * @return [type]             [description]
     */
    public static function redirect($url, $http_code = 302)
    {
        header("Location: $url", true, $http_code);
        exit();
    }
    /**
     * 输出 json 字符串
     * @param  [type] $data [description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public static function json($data, $http_code = 200)
    {
        header("Content-Type:application/json");
        http_response_code($http_code);
        return print_r(json_encode($data));
    }
    /**
     * 输出异常
     * @param  [type]  $exception [description]
     * @param  integer $http_code [description]
     * @return [type]             [description]
     */
    public static function error($exception, $http_code = 500)
    {
        $result = [
            "message" => $exception->getMessage(),
            "error_code" => $exception->getCode(),
        ];
        return self::json($result, $http_code);
    }

}