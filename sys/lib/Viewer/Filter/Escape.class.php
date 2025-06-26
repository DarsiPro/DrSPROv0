<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Escape {


    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (!is_callable($value)) throw new Exception('(Filter_Escape):Value for filtering must be callable.');

        $compiler->raw('htmlspecialchars(');
        $value($compiler);
        $compiler->raw(')');
    }
    
    
    public function __toString()
    {
        $out = '[filter]:escape' . "\n";
        return $out;
    }
}