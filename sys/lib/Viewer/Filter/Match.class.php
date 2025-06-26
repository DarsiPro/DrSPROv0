<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Match {

    private $params = array();

    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (empty($this->params[0])) throw new Exception('Regexp string is not exists in "Match" filter.');
        if (!is_callable($value)) throw new Exception('(Filter_Match):Value for filtering must be callable.');

        $compiler->raw('preg_match(');
        $this->params[0]->compile($compiler);
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
        $out = '[filter]:match' . "\n";
        $out .= '[params]:' . implode("<br>\n", $this->params) . "\n";
        return $out;
    }
}