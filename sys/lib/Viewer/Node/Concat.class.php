<?php
/**
* @project    DarsiPro CMS
* @package    Escape filter
* @url        https://darsi.pro
*/


class Viewer_Node_Concat
{
    private $keys = array();


    public function __construct($node = null)
    {
        if ($node !== null) $this->keys[] = $node;
    }



    public function compile(Viewer_CompileParser $compiler)
    {
        if (is_array($this->keys) && count($this->keys)) {
            $i = 1;
            foreach ($this->keys as $val) {
                $val->compile($compiler);
                if ($i < count($this->keys)) $compiler->raw(' . ');
                $i++;
            }
        }
    }



    public function addElement($element)
    {
        $this->keys[] = $element;
    }



    protected function _keysCallback($markers, $key)
    {
        return $key->compile($markers, true);
    }



    public function __toString()
    {
        $out = '[keys]:';
        if (!empty($this->keys)) {
            $out .= '(' . "\n";
            foreach ($this->keys as $key => $node) {
                $out .= get_class($node) . ':' . $node . "\n";
            }
            $out .= ')' . "\n";
        }
        return $out;
    }
}