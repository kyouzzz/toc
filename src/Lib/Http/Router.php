<?php
namespace Lib\Http;

use Lib\Util\Config;

class Router
{
    const ROUTE_FILE = "route";
    const ROUTE_CONF = "mapping";

    public $matches = array();

    /**
     * 根据 uri 获取 controller && action
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function getRouteMapping($base_uri='')
    {
        $routes = Config::get(self::ROUTE_CONF, self::ROUTE_FILE);
        // TODO Add Route Cache
        foreach ($routes as $controller_name => $action_mappings) {
            foreach ($action_mappings as $action_name => $uri_patterns) {
                foreach ( (array) $uri_patterns as $uri_pattern) {
                    $pattern = '/^' . str_replace('/', '\/', $uri_pattern) . '/';
                    preg_match($pattern, $base_uri, $matches);
                    if (!$matches) {
                        continue ;
                    }
                    $this->setMatches($matches);
                    return [
                        "controller" => $controller_name,
                        "action" => $action_name
                    ];
                }
            }
        }
        return [];
    }
    /**
     * 获取 uri maches
     * @return [type] [description]
     */
    public function getMatches()
    {
        return $this->matches;
    }
    /**
     * 设置 uri maches
     * @return [type] [description]
     */
    public function setMatches($matches)
    {
        $this->matches = $matches;
    }

}