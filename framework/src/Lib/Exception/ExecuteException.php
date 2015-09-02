<?php
namespace Lib\Exception;

class ExecuteException extends \Exception
{

    CONST CONTROLLER_NOT_EXIST = 4001;
    CONST ACTION_NOT_EXIST = 4002;
    CONST CONTROLLER_DEFINE_ERROR = 4003;
    CONST APP_DEFINE_ERROR = 4004;
    CONST CONFIG_DEFINE_ERROR = 4005;
    CONST INTERCEPTOR_BLOCK_ERROR = 4006;

    public function __construct($code = 0, $extend = '') {
        parent::__construct($this->getErrorMessage($code) . "\t$extend", $code);
    }

    public function __toString() {
        return __CLASS__ . ":[{$this->code}]:" . $this->message . '\n';
    }

    public function getErrorMessage($code)
    {
        $mapping = [
            self::CONTROLLER_NOT_EXIST => "controller 不存在",
            self::ACTION_NOT_EXIST => "action 不存在",
            self::CONTROLLER_DEFINE_ERROR => "controller 定义错误",
            self::APP_DEFINE_ERROR => "APP_NAME 未定义",
            self::CONFIG_DEFINE_ERROR => "CONFIG_PATH 未定义",
            self::INTERCEPTOR_BLOCK_ERROR => "拦截器阻止",
        ];
        return $mapping[$code];
    }


}