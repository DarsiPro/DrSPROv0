<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Count {


    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (!is_callable($value)) throw new Exception('(Filter_Count):Value for filtering must be callable.');

        $compiler->raw('count(');
        $value($compiler);
        $compiler->raw(')');
    }
    
    
    public function __toString()
    {
        $out = '[filter]:count' . "\n";
        return $out;
    }
}