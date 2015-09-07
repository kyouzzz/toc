<?php
namespace Lib\Command;

abstract class Console
{

    private $args;
    private $options;

    abstract function run();

    public function setArgs($args='')
    {
        $this->args = $args;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function setOptions($options='')
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

}