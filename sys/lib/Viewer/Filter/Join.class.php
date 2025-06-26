<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Join {

    private $params = array();

    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (!is_callable($value)) throw new Exception('(Filter_Join):Value for filtering must be callable.');


        $compiler->raw('implode(');
        if (isset($this->params[0])) {
            $this->params[0]->compile($compiler);
        } else {
            $compiler->raw('""');
        }
        $compiler->raw(', ');
        $value($compiler);
        $compiler->raw(')');
    }
    

    public function addParam($param)
    {
        $this->params[] = $param;
    }


    public function __toString()
    {
        $out = '[filter]:join' . "\n";
        $out .= '[params]:' . implode("<br>\n", $this->params) . "\n";
        return $out;
    }
}