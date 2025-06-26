<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Length {


    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (!is_callable($value)) throw new Exception('(Filter_Length):Value for filtering must be callable.');

        $compiler->raw('mb_strlen(');
        $value($compiler);
        $compiler->raw(')');
    }
    
    
    public function __toString()
    {
        $out = '[filter]:length' . "\n";
        return $out;
    }
}