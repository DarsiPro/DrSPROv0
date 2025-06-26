<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Default {

    private $params = array();

    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (empty($this->params[0])) throw new Exception('First parameter is not exists in "Default" filter.');
        if (!is_callable($value)) throw new Exception('(Filter_Default):Value for filtering must be callable.');


        $compiler->raw('(');
        $value($compiler);
        $compiler->raw(') ? ');
        $value($compiler);
        $compiler->raw(' : ');
        $this->params[0]->compile($compiler);
    }
    

    public function addParam($param)
    {
        $this->params[] = $param;
    }


    public function __toString()
    {
        $out = '[filter]:default' . "\n";
        $out .= '[params]:' . implode("<br>\n", $this->params) . "\n";
        return $out;
    }
}