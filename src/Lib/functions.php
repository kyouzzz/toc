<?php

// 页面布局用到的方法
if (!function_exists('layout')){
    function layout($layout = '', $params = [])
    {
        $page = Lib\Http\Page::getInstance();
        $page->layout($layout, $params);
    }
}
if (!function_exists('block')){
    function block($name)
    {
        $page = Lib\Http\Page::getInstance();
        $page->block($name);
    }
}
if (!function_exists('block_start')){
    function block_start($name)
    {
        $page = Lib\Http\Page::getInstance();
        $page->blockStart($name);
    }
}
if (!function_exists('block_end')){
    function block_end($name)
    {
        $page = Lib\Http\Page::getInstance();
        $page->blockEnd($name);
    }
}
// 加载js css 方法
if (!function_exists('js')){
    function js($name)
    {
        $page = Lib\Http\Page::getInstance();
        $page->js($name);
    }
}
if (!function_exists('css')){
    function css($name)
    {
        $page = Lib\Http\Page::getInstance();
        $page->css($name);
    }
}
if (!function_exists('render')){
    function render($name, $params = [])
    {
        $page = Lib\Http\Page::getInstance();
        $page->render($name, $params);
    }
}
