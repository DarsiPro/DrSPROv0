<?php



class Register implements ArrayAccess, Iterator, Countable
{

    private static $instance = NULL;


    public static function getInstance()
    {
        if (self::$instance == NULL) {
             self::$instance = new self();
        }
        return self::$instance;
    }


    function set($Name,$Value)
    {
        if (empty($Name))
        {
            $this->storage[] = $Value;
        }
        else
        {
            $this->storage[$Name] = $Value;
        }
    }


    function get($Name)
    {
        if (isset($this->storage[$Name]))
        {
            return $this->storage[$Name];
        }
        return NULL;
    }


    /** Get,returned and saving class */
    static function getClass($name) {
        $Register = self::getInstance();
        if (!isset($Register[$name]))
            $Register[$name] = new $name;
        return $Register[$name];
    }


    /**
     * @param  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->storage);
    }


    /**
     * @param  $key
     * @param  $value
     * @return bool
     */
    public function offsetSet($key, $value)
    {
        $this->storage[$key] = $value;
        return true;
    }

    /**
     * @param  $key
     * @return array
     */
    public function offsetGet($key)
    {
        return (!empty($this->storage[$key])) ? $this->storage[$key] : false;
    }

    /**
     * @param  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->storage[$key]);
    }



    /*
     * Next Iterator interface
     */



    /**
     * @return array
     */
    public function current()
    {
        return $this->storage[$this->key];
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->key++;
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->key = 0;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return array_key_exists($this->key, $this->storage);
    }



    /*
     * For Countable Interface
     */



    /**
     * @return int
     */
    public function count()
    {
        return count($this->storage);
    }
}