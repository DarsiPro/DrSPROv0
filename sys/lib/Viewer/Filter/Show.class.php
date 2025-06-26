<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Show {


    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (!is_callable($value)) throw new Exception('(Filter_Show):Value for filtering must be callable.');
        $compiler->raw("'<pre>' . print_r(");
        $value($compiler);
        $compiler->raw(", true) . '<pre>'");
    }

    
    public function __toString()
    {
        $out = '[filter]:show' . "\n";
        return $out;
    }
}