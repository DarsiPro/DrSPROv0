<?php



class Viewer_Node_Const extends Viewer_Node_Expresion
{

    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }




    public function compile(Viewer_CompileParser $compiler)
    {
        if (is_array($this->filters) && count($this->filters)) {
            $this->parseFilters($compiler);
        } else {
            $compiler->repr($this->value);
        }
    }




    public function __toString()
    {
        $out = "\n";
        $out .= '[value]:' . $this->value . "\n";
        return $out;
    }
}