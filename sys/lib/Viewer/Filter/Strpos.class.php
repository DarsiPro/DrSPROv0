<?php
/*
@Subpackege    Strpos filter
@Site:         https://darsi.pro/
@Package       DarsiPro
*/

class Viewer_Filter_Strpos {

    private $params = array();

    public function compile($value, Viewer_CompileParser $compiler)
    {
        if (empty($this->params[0])) throw new Exception('First parameter is not exists in "Strpos" filter.');
        if (!is_callable($value)) throw new Exception('(Filter_Strpos):Value for filtering must be callable.');
        
        $compiler->raw('mb_strpos(');
        $value($compiler);
        $compiler->raw(',');
        $this->params[0]->compile($compiler);
        if (isset($this->params[1])) {
            $compiler->raw(',');
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
        $out = '[filter]:strpos' . "\n";
        $out .= '[params]:' . implode("<br>\n", $this->params) . "\n";
        return $out;
    }
}