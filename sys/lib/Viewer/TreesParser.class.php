<?php

class Viewer_TreesParser
{

    protected $stream;
    protected $expressionParser;
    protected $tokenParsers;
    protected $nodesTree;
    protected $env;
    protected $currentValue;
    public $stack = array();




    public function __construct()
    {
        $this->expressionParser = new Viewer_ExpresionParser($this);
        $this->tokenParsers = array(
            'if' => 'Viewer_Parser_If',
            'for' => 'Viewer_Parser_For',
            'include' => 'Viewer_Parser_Include',
            'set' => 'Viewer_Parser_Set',
        );
    }


    public function parse(Viewer_TokenStream $stream, $test = null)
    {
        $this->stream = $stream;
        $rv = array();


        while (!$this->stream->isEOF()) {
            switch ($this->getCurrentToken()->getType()) {
                case Viewer_Token::TEXT_TYPE:
                    $token = $this->stream->next();
                    $this->setCurrentValue($token->getValue());
                    $rv[] = $this->setNode(new Viewer_Node_Text($token->getValue()));
                    break;

                case Viewer_Token::VAR_START_TYPE:
                    $token = $this->stream->next();
                    $expr = $this->expressionParser->parseExpression();
                    $this->stream->expect(Viewer_Token::VAR_END_TYPE);
                    $rv[] = $expr;
                    break;

                case Viewer_Token::URL_START_TYPE:
                    $this->stream->next();
                    $token = $this->stream->getCurrent();
                    $expr = $this->setNode(new Viewer_Node_Url($token->getValue()));
                    $this->stream->next();
                    //pr($node); die();
                    $this->stream->expect(Viewer_Token::URL_END_TYPE);
                    $rv[] = $expr;
                    break;
                case Viewer_Token::COMMENT_START_TYPE:
                    $this->stream->next();
                    $token = $this->stream->getCurrent();
                    $expr = $this->setNode(new Viewer_Node_Comment($token->getValue()));
                    $this->stream->next();
                    //pr($node); die();
                    $this->stream->expect(Viewer_Token::COMMENT_END_TYPE);
                    $rv[] = $expr;
                    break;
                case Viewer_Token::BLOCK_START_TYPE:
                    $this->stream->next();
                    $token = $this->getCurrentToken();


                    if (null !== $test && call_user_func($test, $token)) {
                        return $rv;
                    }
                    $subparser = $this->getTokenParser($token->getValue());
                    //if (!$subparser) break;


                    //pr($this->getCurrentToken());
                    $node = $subparser->parse($token);
                    if (null !== $node) {
                        $rv[] = $node;
                    }
                    break;

                default:
                    $token = $this->stream->next();
                    // TODO
            }
        }

        $this->nodesTree = $rv;
        return new Viewer_NodeTree($rv);
    }



    public function getStack()
    {
        return $this->stack;
    }



    public function setStack($key)
    {
        $this->stack[] = $key;
    }



    public function cleanStack()
    {
        $this->stack = array();
    }



    private function getTokenParser($value)
    {
        if (!array_key_exists($value, $this->tokenParsers)) {
            throw new Exception("'$value' parser not found.");
            return '';
        }

        return new $this->tokenParsers[$value]($this);
    }



    public function getExpression()
    {
        return $this->expressionParser;
    }



    public function getStream()
    {
        return $this->stream;
    }




    public function getCurrentToken()
    {
        return $this->stream->getCurrent();
    }





    public function getEnv()
    {
        return $this->env;
    }




    public function setEnv($env)
    {
        $this->env = $env;
    }



    public function setCurrentValue($value)
    {
        $this->currentValue = $value;
    }



    public function getCurrentValue()
    {
        return $this->currentValue;
    }



    public function setPrint($node)
    {
        return new Viewer_Node_Print($node);
    }




    public function setNode($node, $inFunc = false)
    {
        if ($node instanceof Viewer_Node_Comment
            || $node instanceof Viewer_Node_Comment
        ) {
            return $node;
        }


        switch ($this->getEnv()) {
            case 'set_left':
                break;
            case 'if':
            case 'for_definition':
            case 'set_right':
                if ($node instanceof Viewer_Node_Var) $node->setDef(true);
                //$node = new Viewer_Node($node);
                break;

            default:
                if ($node instanceof Viewer_Node_Var) $node->setDef(true);
                if (!$inFunc) $node = $this->setPrint($node);
                //$node = new Viewer_Node($node);
                break;
        }

        return $node;
    }

}