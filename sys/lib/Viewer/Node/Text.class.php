<?php



class Viewer_Node_Text extends Viewer_Node_Expresion
{

    protected $data;


    public function __construct($data)
    {
        $this->data = $data;
    }



    public function compile(Viewer_CompileParser $compiler)
    {
        if (is_array($this->filters) && count($this->filters)) {
            $this->parseFilters($compiler);
        } else {
            $compiler->string($this->data);
        }
    }



    public function __toString()
    {
        $out = "\n";
        $out .= (string)$this->data;
        return $out;
    }
}