<?php
namespace Lib\Database;

class DataObject implements \ArrayAccess
{
    public function set($key, $value)
    {
        $this->{$key} = $value;
    }
    public function get($key)
    {
        return $this->{$key};
    }

    public function offsetSet($key, $value)
    {
        if(array_key_exists($key,get_object_vars($this))) {
            $this->{$key} = $value;
        }
    }
    public function offsetGet($key)
    {
        if(array_key_exists($key,get_object_vars($this))) {
            return $this->{$key};
        }
    }
    /**
     * Defined by ArrayAccess interface
     * Unset a value by it's key e.g. unset($A['title']);
     * @param mixed key (string or integer)
     * @return void
    */
    public function offsetUnset($key)
    {
        if (array_key_exists($key,get_object_vars($this))) {
            unset($this->{$key});
        }
    }
    /**
     * Defined by ArrayAccess interface
     * Check value exists, given it's key e.g. isset($A['title'])
     * @param mixed key (string or integer)
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset,get_object_vars($this));
    }

}