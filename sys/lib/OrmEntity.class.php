<?php
/**
* @project    DarsiPro CMS
* @package    OrmEntity class
* @url        https://darsi.pro
*/






class OrmEntity {

    public function __construct($params = array())
    {
        $this->set($params);
    }


    public function set($params = array())
    {
        if (!empty($params) && is_array($params)) {
            foreach ($params as $k => $value) {
                $funcName = 'set' . ucfirst($k);
                $this->$funcName($value);
            }
        }
    }


    public function __call($method, $params)
    {
        if (false !== strpos($method, 'set')) {
            $name = str_replace('set', '', $method);
            $name = strtolower($name);
            $this->$name = $params[0];

        } else if (false !== strpos($method, 'get')) {
            $name = str_replace('get', '', $method);
            $name = strtolower($name);
            return (isset($this->$name)) ? $this->$name : null;
        }
        return;
    }



    protected function checkProperty($var)
    {
        if (is_object($var)) return true;
        return (!isset($this->{$var})) ? false : true;
    }


    public function asArray()
    {   
        return get_object_vars($this);
    }
}
