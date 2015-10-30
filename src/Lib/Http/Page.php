<?php
namespace Lib\Http;

use Lib\Exception\ExecuteException as ExecError;

class Page
{
    // 单例
    private static $instance;
    // 加载的模板
    private $layouts = [];
    // 模板内容
    private $block_buffer = [];
    // 
    private $resource_version = "";

    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new Page();
        }
        return static::$instance;
    }

    private function __construct()
    {
    }
    /**
     * 渲染 html 页面
     * @param  [type] $html_file [html 文件]
     * @param  string $params    [渲染数据]
     * @return [type]            [description]
     */
    public function parse($html_file, $params = '')
    {
        // 获取页面路径
        $file_path = $this->getPagePath($html_file);
        if (!empty($params) && is_array($params)) {
            foreach ($params as $key => $value) {
                // 只在第一维简单过滤html注入, 遍历数组过滤太耗性能 
                if (gettype($value) == "string") {
                    $value = htmlspecialchars($value);
                }
                $$key = $value;
            }
        }
        // 文件不存在抛出异常
        if (!file_exists($file_path)) {
            throw new ExecError(ExecError::FILE_MISS_ERROR, $file_path);
        }
        include($file_path);
    }
    /**
     * 记录页面使用的模板
     * @param  [type] $layout_html [布局文件]
     * @param  array  $params      [传递参数]
     * @return [type]              [description]
     */
    public function layout($layout_html, $params = [])
    {
        // 模板相对路径做 key, 模板参数为 value.
        $this->layouts[$layout_html] = $params;
    }
    /**
     * 输出模板中内容
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    public function block($name)
    {
        if (isset($this->block_buffer[$name])) {
            echo($this->block_buffer[$name]);
        }
    }
    /**
     * 将模板内容记录到缓冲区
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    public function blockStart($name)
    {
        ob_start();
    }
    public function blockEnd($name)
    {
        $this->block_buffer[$name] = ob_get_contents();
        ob_clean();
    }
    /**
     * 正式输出模板
     * @return [type] [description]
     */
    public function outPut()
    {
        foreach ($this->layouts as  $layout => $data) {
            $this->parse($layout, $data);
        }
        return ;
    }
    /**
     * 获取页面根目录
     * @return [type] [description]
     */
    private function getBasePagePath()
    {
        $base_path = ROOT_PATH . "page/app-" . strtolower(APP_NAME) . "/html/";
        return $base_path;
    }
    /**
     * 获取文件据对路径
     * @param  string $page_file [description]
     * @return [type]            [description]
     */
    public function getPagePath($page_file = '')
    {
        $page_path = $this->getBasePagePath() . ltrim($page_file, "/");
        return $page_path;
    }
    /**
     * 加载公共模块的方法
     * @param  [type] $html_file [description]
     * @param  array  $data      [description]
     * @return [type]            [description]
     */
    public function render($html_file, $data = [])
    {
        $this->parse($html_file, $data);
    }

    public function js($js_file)
    {
        $js_src = $this->getResourceSrc($js_file);
        echo "<script src=\"$js_src\"></script>" . PHP_EOL;
    }
    public function css($css_file)
    {
        $css_src = $this->getResourceSrc($css_file);
        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$css_src\">" . PHP_EOL;
    }
    /**
     * 获取 js css 路径
     * @param  string $resource_file [description]
     * @return [type]                [description]
     */
    private function getResourceSrc($resource_file = '')
    {
        $resource_path =  "/resource/";
        $version = $this->getResourceVersion();
        $src = $resource_path . ltrim($resource_file, "/") . "?v=$version";
        return $src;
    }
    /**
     * 根据文件名生成版本
     * @return [type] [description]
     */
    private function getResourceVersion()
    {
        if (empty($this->resource_version)) {
            // 获取文件夹名
            $path_arr = explode("/", ROOT_PATH);
            $source = end($path_arr);
            // 方法名做混淆码
            $crypt_str = md5($source . __METHOD__);
            $this->resource_version = substr($crypt_str, 0, 5);
        }
        return $this->resource_version;
    }

}
