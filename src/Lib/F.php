<?php
namespace Lib;

use Lib\Http\Request;
use Lib\Http\Response;
use Lib\Http\Router;
use Lib\Command\Handler as CommandHandler;
use Lib\Util\Config;
use Lib\Log\Debugger;
use Lib\Exception\ExecuteException as ExecError;

class F
{

    private static $instance = null;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new F();
        }
        return self::$instance;
    }
    /**
     * 执行请求
     * @return [type] [description]
     */
    public function run()
    {
        if (!defined("APP_NAME")) {
            $this->interrupt(ExecError::APP_DEFINE_ERROR);
        }
        if (!defined("CONFIG_PATH")) {
            $this->interrupt(ExecError::CONFIG_DEFINE_ERROR);
        }
        // job 运行
        if ($this->isCli()) {
            $this->runCommand();
        } else {
            $this->runHttpRequest();
        }
    }
    /**
     * 命令行运行
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function runCommand()
    {
        $handler = new CommandHandler();
        $handler->exec();
    }
    /**
     * web 请求
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function runHttpRequest()
    {
        $this->request = new Request();
        $this->router = new Router();
        // 获取 url
        $base_uri = $this->getBaseUri();
        // 获取路由 controller 和 action
        $route_mapping = $this->router->getRouteMapping($base_uri);
        if (empty($route_mapping)) {
            Response::redirect("/error.html", 302);
        }
        $params = $this->request->getParameters();
        // url 正则匹配数组
        $matches = $this->router->getMatches();
        // 执行拦截器操作
        $this->execInterceptors($route_mapping["controller"],
            $route_mapping["action"], $params, $matches);
        // 执行 controller 操作
        $this->execAction($route_mapping["controller"],
            $route_mapping["action"], $params, $matches);
    }
    /**
     * 依次调用拦截器的方法
     * @param  [type] $controller_name [description]
     * @param  [type] $action_name     [description]
     * @param  [type] $params         [description]
     * @param  [type] $matches        [description]
     * @return [type]                 [description]
     */
    private function execInterceptors($controller_name, $action_name, $params, $matches)
    {
        $used_interceptors = $this->getActionInterceptors(
            $controller_name, $action_name);
        // 遍历执行拦截器
        foreach ($used_interceptors as $interceptor_name) {
            $class_name = $interceptor_name . "Interceptor";
            $clazz = APP_NAME . "\\Interceptor\\" . $class_name;
            $interceptor = new $clazz();
            $trace_key = $class_name;
            // 记录拦截器运行时间
            Debugger::instance()->traceBegin($trace_key);
            call_user_func(array($interceptor, "setParams"), $params);
            $result = call_user_func(array($interceptor, "before"), $matches);
            Debugger::instance()->traceEnd($trace_key);
            if ($result == false) {
                // 拦截器运行失败统一出口
                $this->interrupt(ExecError::INTERCEPTOR_BLOCK_ERROR, $clazz);
            }
        }
    }
    /**
     * 获取所有用到的拦截器
     * @param  [type] $controller_name [description]
     * @param  [type] $action_name     [description]
     * @return [type]                  [description]
     */
    private function getActionInterceptors($controller_name, $action_name)
    {
        $list_all = Config::get("interceptor", "interceptor");
        // 合并拦截器
        if (isset($list_all["global"])) {
            $used_list = $list_all["global"];
        } else {
            $used_list = array();
        }
        if (isset($list_all["$controller_name"])) {
            $used_list = array_merge($used_list, $list_all["$controller_name"]);
        }
        $action_key = "$controller_name@$action_name";
        if (isset($list_all[$action_key])) {
            $used_list = array_merge($used_list, $list_all[$action_key]);
        }
        // 过滤空元素
        $used_list = array_filter($used_list);
        // 获取所有要执行的拦截器
        $formated_list = array();
        foreach ($used_list as $key => $value) {
            // 去除排除掉的过滤器
            if (strpos($value, "!") !== 0 && !in_array($value, $formated_list)) {
                $formated_list[] = $value;
                continue;
            }
            $ignored  = substr($value, 1);
            if (in_array($ignored, $formated_list)) {
                $ignored_key = array_search($ignored, $formated_list);
                unset($formated_list[$ignored_key]);
            }
        }
        return array_values($formated_list);
    }
    /**
     * 调用 controller 的 action 传递参数和匹配
     * @param  [type] $controller_name [description]
     * @param  [type] $action_name     [description]
     * @param  [type] $params         [description]
     * @param  [type] $matches        [description]
     * @return [type]                 [description]
     */
    private function execAction($controller_name, $action_name, $params, $matches)
    {
        $clazz = APP_NAME . "\\Controller\\" . $controller_name . "Controller";
        $action = $action_name . "Action";
        if (!class_exists($clazz)) {
            throw new ExecError(ExecError::CONTROLLER_NOT_EXIST, $clazz);
        }
        $controller = new $clazz();
        if (!method_exists($controller, $action)) {
            throw new ExecError(ExecError::ACTION_NOT_EXIST, $action);
        }
        if (!is_subclass_of($controller, "Lib\\Http\\Controller")) {
            throw new ExecError(ExecError::CONTROLLER_DEFINE_ERROR, $clazz);
        }
        // 记录 action 运行时间
        $trace_key = "{$controller_name}Controller@{$action}";
        Debugger::instance()->traceBegin($trace_key);

        call_user_func(array($controller, "setParams"), $params);
        call_user_func(array($controller, "setRequest"), $this->request);
        call_user_func(array($controller, $action), $matches);

        Debugger::instance()->traceEnd($trace_key);
        Debugger::instance()->output();
    }
    /**
     * 获取url
     * @return [type] [description]
     */
    private function getBaseUri()
    {
        $base_uri = '/';
        $uri = $this->request->getUri();
        $pos = strpos($uri, '?');
        if ($pos) {
            $base_uri = substr($uri, 0, $pos);
        } else {
            $base_uri = $uri;
        }
        return $base_uri;
    }
    /**
     * 判断是否是命令行运行
     * @return boolean [description]
     */
    private function isCli()
    {
        return php_sapi_name() == "cli";
    }
    /**
     * 异常终止
     * @param  [type] $code [description]
     * @return [type]       [description]
     */
    private function interrupt($code, $extend = "")
    {
        throw new ExecError($code, $extend);
    }

}

