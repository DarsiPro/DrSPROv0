<?php



class Viewer_Node_Set
{

    private $left;
    private $right;


    public function __construct($left, $right)
    {
        $this->left = $left;
        $this->right = $right;
    }



    public function compile(Viewer_CompileParser $compiler)
    {
        $compiler->addIndent();
        $this->left->compile($compiler);
        $compiler->raw(" = ");
        $this->right->compile($compiler);
        $compiler->raw(";\n");
    }




    public function __toString()
    {
        $out = "\n";
        $out .= '[left]:' . $this->left . "\n";
        $out .= '[right]:' . $this->right . "\n";
        return $out;
    }
}