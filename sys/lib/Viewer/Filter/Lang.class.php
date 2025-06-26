<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Lang {


    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (!is_callable($value)) throw new Exception('(Filter_Lang):Value for filtering must be callable.');

        $compiler->raw('__(');
        $value($compiler);
        $compiler->raw(', true)');
    }


    public function __toString()
    {
        $out = '[filter]:lang' . "\n";
        return $out;
    }
}