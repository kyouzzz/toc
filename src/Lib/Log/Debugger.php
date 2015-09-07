<?php
namespace Lib\Log;

use Lib\Util\Config;

class Debugger
{

    public static $instance;

    private $open_debug = false;

    private $content = null;

    private $start_time = 0;

    private function __construct()
    {
        if (isset($_GET['debug']) && $_GET['debug'] && Config::get("debug")) {
            $this->open_debug = true;
        }
        $this->start_time = microtime(1);
        $this->content = [];
    }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new Debugger();
        }
        return self::$instance;
    }

    public function traceBegin($key, $msg = '')
    {
        if (!$this->open_debug) {
            return false;
        }
        $begin_time = microtime(1);
        $this->content[$key] = [
            "message" => "$msg",
            "start_time" => $begin_time,
        ];
    }

    public function traceEnd($key, $msg = '')
    {
        if (!$this->open_debug) {
            return false;
        }
        $end_time = microtime(1);
        $trace_content = $this->content[$key];
        $this->content[$key] = [
            "message" => $trace_content['message'] . $msg,
            "exec_time" => $end_time - $trace_content['start_time'],
            "total_time" => $end_time - $this->start_time,
        ];
    }
    /**
     * 过滤没有结束的debug信息
     * @return [type] [description]
     */
    public function getContent()
    {
        foreach ($this->content as $key => $value) {
            if (!isset($value['exec_time'])) {
                unset($this->content[$key]);
            }
        }
        return $this->content;
    }
    /**
     * 在页面上输出时间
     * @return [type] [description]
     */
    public function getHtmlContent()
    {
        $content = $this->getContent();
        if (empty($content)) {
            return "";
        }
        $html = "<table border=1 >";
        foreach ($content as $key => $value) {
            $td = "<td>&nbsp;" . $key . "</td>";
            $td .= "<td>&nbsp;" . number_format(round($value['total_time'], 6), 6) . "</td>";
            $td .= "<td>&nbsp;" . number_format(round($value['exec_time'], 6), 6) . "</td>";
            $td .= "<td>&nbsp;" . $value['message'] . "</td>";
            $tr = "<tr>{$td}</tr>";
            $html .= $tr;
        }
        $html .= "</table>";
        return $html;
    }

    public function output()
    {
        echo $this->getHtmlContent();
    }
}