<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Replace {

    private $params = array();

    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (empty($this->params[0])) throw new Exception('First parameter is not exists in "Replace" filter.');
        if (!is_callable($value)) throw new Exception('(Filter_Replace):Value for filtering must be callable.');

        $compiler->raw('str_replace(');
        // search
        $this->params[0]->compile($compiler);
        $compiler->raw(', ');
        // replace
        $this->params[1]->compile($compiler);
        $compiler->raw(', ');
        // subject
        $value($compiler);
        // count
        if (isset($this->params[2])) {
            $compiler->raw(', ');
            $this->params[2]->compile($compiler);
        }
        $compiler->raw(')');
    }
    

    public function addParam($param)
    {
        $this->params[] = $param;
    }


    public function __toString()
    {
        $out = '[filter]:replace' . "\n";
        $out .= '[params]:' . implode("<br>\n", $this->params) . "\n";
        return $out;
    }
}