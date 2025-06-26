<?php



class Viewer_Node_Print
{

    private $value;



    public function __construct($value)
    {
        $this->value = $value;
    }



    public function compile(Viewer_CompileParser $compiler)
    {
        $compiler->write('echo ')
            ->raw($this->value->compile($compiler))
            ->raw(";\n");
    }




    public function __toString()
    {
        return (string)$this->value;
    }
}