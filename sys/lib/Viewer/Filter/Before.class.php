<?php
/*
@Subpackege    Before filter
@Site:         https://darsi.pro/
@Package       DarsiPro
*/

class Viewer_Filter_Before {

    private $params = array();

    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (empty($this->params[0])) throw new Exception('First parameter is not exists in "Before" filter.');
        if (!is_callable($value)) throw new Exception('(Filter_Before):Value for filtering must be callable.');
        
        $compiler->raw('call_user_func(function($arr, $value) {array_unshift($arr,$value);return $arr;},');
        $value($compiler);
        $compiler->raw(',');
        $this->params[0]->compile($compiler);
        $compiler->raw(')');
    }
    

    public function addParam($param)
    {
        $this->params[] = $param;
    }


    public function __toString()
    {
        $out = '[filter]:before' . "\n";
        $out .= '[params]:' . implode("<br>\n", $this->params) . "\n";
        return $out;
    }
}