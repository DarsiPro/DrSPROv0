<?php
/**
* @project    DarsiPro CMS
* @package    Filters
* @url        https://darsi.pro
*/


class Viewer_Filter_Format {

    private $params = array();

    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (empty($this->params[0])) throw new Exception('First parameter is not exists in "Format" filter.');
        if (!is_callable($value)) throw new Exception('(Filter_Format):Value for filtering must be callable.');


        $compiler->raw('sprintf(');
        $value($compiler);
        $compiler->raw(', ');
        foreach ($this->params as $k => $param) {
            $param->compile($compiler);
            if (($k + 1) < count($this->params)) $compiler->raw(', ');
        }
        $compiler->raw(')');
    }
    

    public function addParam($param)
    {
        $this->params[] = $param;
    }


    public function __toString()
    {
        $out = '[filter]:format' . "\n";
        $out .= '[params]:' . implode("<br>\n", $this->params) . "\n";
        return $out;
    }
}