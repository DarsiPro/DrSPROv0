<?php



class Viewer_Parser_Include
{
    public $parser;



    public function __construct($parser)
    {
        $this->parser = $parser;
    }


    public function parse($token)
    {
        $this->parser->getStream()->next();
        $expr = $this->parser->getExpression()->parsePrimaryExpression(); // парсинг выражения


        return new Viewer_Node_Include($expr, $this->parser->getStack());
    }
}