<?php
namespace Lib\Util;

class Config
{

    // TODO 相对路径
    const SYS_CONF_PATH = "vendor/21cake/framework/config/";

    public static function get($key, $file = "common")
    {
        $config_paths = json_decode(CONFIG_PATH);
        // 添加系统默认配置到最前面
        array_unshift($config_paths, ROOT_PATH . self::SYS_CONF_PATH);
        // 所以,后面的 config 会覆盖前面的
        foreach ($config_paths as $value) {
            $file_name = $file . ".php";
            $config_file = $value . $file_name;
            if (file_exists($config_file)) {
                include($config_file);
            }
            $app_config_path = $value . "app-" . strtolower(APP_NAME). "/";
            $app_config_file = $app_config_path . $file_name;
            if (file_exists($app_config_file)) {
                include($app_config_file);
            }
        }

        if (isset($config[$key])) {
            return $config[$key];
        } else {
            // TODO LOG WARN
            // 未找到配置文件 记录警告信息
            return '';
        }
        
    }

}
