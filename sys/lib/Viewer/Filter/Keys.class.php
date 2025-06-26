<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Keys {


    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (!is_callable($value)) throw new Exception('(Filter_Keys):Value for filtering must be callable.');

        $compiler->raw('array_keys(');
        $value($compiler);
        $compiler->raw(')');
    }
    
    
    public function __toString()
    {
        $out = '[filter]:keys' . "\n";
        return $out;
    }
}