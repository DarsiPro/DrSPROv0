<?php



class Viewer_Node_For
{
    protected $expr;
    protected $body;



    public function __construct($expr, $body)
    {
        $this->body = $body;
        $this->expr = $expr;
    }




    public function compile(Viewer_CompileParser $compiler)
    {
        $compiler->write('// For block node')
            ->raw("\n")
            ->write('foreach (');


        $compiler->subcompile($this->expr)
            ->raw(") {\n")
            ->indent();


        foreach ($this->body as $key => $val) {
            $val->compile($compiler);
        }


        $compiler->outdent()->write("}\n");
    }




    public function __toString()
    {
        $out = '[expr]:' . $this->expr . "\n";
        $out .= '[body]:' . implode("<br>\n", $this->body) . "\n";
        return $out;
    }
}