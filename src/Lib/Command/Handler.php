<?php
namespace Lib\Command;

class Handler
{

    public function exec()
    {
        $args = $GLOBALS['argv'];
        $job_path = rtrim($args[1], ".php");

        $arr = explode("/", $job_path);
        $arr = array_filter($arr);
        $clazz = APP_NAME . "\\Console\\" . implode("\\", $arr);
        $controller = new $clazz();

        unset($args[0], $args[1]);
        call_user_func(array($controller, "setArgs"), array_values($args));
        
        call_user_func(array($controller, "run"));
    }

}