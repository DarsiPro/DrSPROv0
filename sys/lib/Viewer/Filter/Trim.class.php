<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Trim {

    private $params = array();


    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (!is_callable($value)) throw new Exception('(Filter_Trim):Value for filtering must be callable.');

        $compiler->raw('trim(');
        $value($compiler);
        if (isset($this->params[0])) {
            $compiler->raw(', ');
            $this->params[0]->compile($compiler);
        }
        $compiler->raw(')');
    }


    public function addParam($param)
    {
        $this->params[] = $param;
    }

    
    public function __toString()
    {
        $out = '[filter]:trim' . "\n";
        $out .= '[params]:' . implode("<br>\n", $this->params) . "\n";
        return $out;
    }
}