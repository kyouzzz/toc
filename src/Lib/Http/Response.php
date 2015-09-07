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
    public static function view($html_file, $data = [], $http_code = 200)
    {
        if (empty($html_file)) {
            http_response_code($http_code);
            return ;
        }
        $page_path = ROOT_PATH . "page/app-" . strtolower(APP_NAME) . "/html/";
        $view_file = $page_path . ltrim($html_file, "/");
        foreach ($data as $key => $value) {
            // 只在第一维简单过滤html注入, 遍历数组过滤可能会影响性能 
            if (gettype($value) == "string") {
                $value = htmlspecialchars($value);
            }
            $$key = $value;
        }
        http_response_code($http_code);
        include($view_file);
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
        http_response_code($http_code);
        echo json_encode($data);
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
        self::json($result, $http_code);
    }

    public static function render($html_file, $data = [])
    {
        self::view($html_file, $data);
    }

    public static function js($js_file)
    {
        $resource_path =  "/resource/";
        $js_src = $resource_path . ltrim($js_file, "/");
        echo "\n\t<script src=\"" . $js_src . "\"></script>";
    }

    public static function css($css_file)
    {
        $resource_path =  "/resource/";
        $css_src = $resource_path . ltrim($css_file, "/");
        // 一堆换行仅仅为了输出好看
        echo "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $css_src . "\">";
    }

}