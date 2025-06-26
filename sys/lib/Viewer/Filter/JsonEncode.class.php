<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_JsonEncode {


    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (!is_callable($value)) throw new Exception('(Filter_Json_encode):Value for filtering must be callable.');

        $compiler->raw('json_encode(');
        $value($compiler);
        $compiler->raw(', JSON_FORCE_OBJECT)');
    }
    
    
    public function __toString()
    {
        $out = '[filter]:json_encode' . "\n";
        return $out;
    }
}