<?php



class Viewer_Parser_Set
{
    public $parser;



    public function __construct($parser)
    {
        $this->parser = $parser;
    }


    public function parse($token)
    {
        $this->parser->getStream()->next();

        $this->parser->setEnv('set_left');
        $left = $this->parser->getExpression()->parsePrimaryExpression();


        if (!$left instanceof Viewer_Node_Var)
            throw new Exception("Attempt to change a constant var (".$left.")");


        $this->parser->getStream()->expect(Viewer_Token::OPERATOR_TYPE);

        $this->parser->setEnv('set_right');
        $right = $this->parser->getExpression()->parseExpression();

        $this->parser->setEnv(false);
        $this->parser->setStack($left->getValue());

        return new Viewer_Node_Set($left, $right);
    }
}