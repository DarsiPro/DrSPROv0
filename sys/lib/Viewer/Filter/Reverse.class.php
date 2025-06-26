<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Reverse {


    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (!is_callable($value)) throw new Exception('(Filter_Reverse):Value for filtering must be callable.');

        $compiler->raw('array_reverse(');
        $value($compiler);
        $compiler->raw(')');
    }
    
    
    public function __toString()
    {
        $out = '[filter]:reverse' . "\n";
        return $out;
    }
}