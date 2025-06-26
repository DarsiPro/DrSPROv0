<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Split {

    private $params = array();

    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (empty($this->params[0])) throw new Exception('First parameter is not exists in "Split" filter.');
        if (!is_callable($value)) throw new Exception('(Filter_Split):Value for filtering must be callable.');


        $compiler->raw('explode(');
        $this->params[0]->compile($compiler);
        $compiler->raw(', ');
        $value($compiler);
        if (isset($this->params[1])) {
            $compiler->raw(', ');
            $this->params[1]->compile($compiler);
        }
        $compiler->raw(')');
    }
    

    public function addParam($param)
    {
        $this->params[] = $param;
    }


    public function __toString()
    {
        $out = '[filter]:split' . "\n";
        $out .= '[params]:' . implode("<br>\n", $this->params) . "\n";
        return $out;
    }
}