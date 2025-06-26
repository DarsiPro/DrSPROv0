<?php



class Viewer_ExpresionParser
{

    private $parser;
    private $binaryOperators;
    private $inFunc = 0;
    private $inIfDefinition = 0;



    public function __construct(Viewer_TreesParser $parser)
    {
        $this->parser = $parser;
        $this->binaryOperators = array(
            '==' => 'Viewer_Operator_BinaryEqual',
            '===' => 'Viewer_Operator_BinaryStrictEqual',
            '!=' => 'Viewer_Operator_BinaryNotEqual',
            '!==' => 'Viewer_Operator_BinaryStrictNotEqual',
            '>=' => 'Viewer_Operator_BinaryMoreEq',
            '>' => 'Viewer_Operator_BinaryMore',
            '<=' => 'Viewer_Operator_BinaryLessEq',
            '<' => 'Viewer_Operator_BinaryLess',
            '+' => 'Viewer_Operator_BinarySumm',
            '-' => 'Viewer_Operator_BinarySubtrac',
            '*' => 'Viewer_Operator_BinaryMult',
            '**' => 'Viewer_Operator_BinaryPower',
            '/' => 'Viewer_Operator_BinaryDivis',
            '//' => 'Viewer_Operator_BinaryDivisFloor',
            '%' => 'Viewer_Operator_BinaryMod',
            'in' => 'Viewer_Operator_BinaryIn',
            'not in' => 'Viewer_Operator_BinaryNotIn',
            'and' => 'Viewer_Operator_BinaryAnd',
            'or' => 'Viewer_Operator_BinaryOr',
        );
    }


    public function parseExpression($precedence = 0)
    {
        $node = $this->parsePrimaryExpression();
        $currToken = $this->parser->getStream()->getCurrent();


        switch ($currToken->getType()) {
            case Viewer_Token::OPERATOR_TYPE:
                $node = $this->parseOperatorExpression($node, $currToken->getValue());

                break;
            case Viewer_Token::BLOCK_END_TYPE:
                $node = $this->parseOperatorExpression($node, NULL);
                break;
        }
        return $node;
    }


    public function parseOperatorExpression($left, $type)
    {
        $this->inFunc++;
        if (!empty($type) && !array_key_exists($type, $this->binaryOperators)) {
            throw new Exception("Operator type '$type' is not exists.");
        }

        $stream = $this->parser->getStream();

        // if use IF with only one parametr ( if($var) )
        if ($stream->getCurrent()->getType() == Viewer_Token::BLOCK_END_TYPE) {
            //$right = $this->parsePrimaryExpression();
            $this->inFunc--;
            return new $this->binaryOperators['==']($left, null, true);
        }

        $stream->next();
        $token = $stream->getCurrent();


        // This is tmp var seting when foreach array
        if ('for_definition' === $this->parser->getEnv()) {
            $this->parser->setStack($left->getValue());
        }
        $right = $this->parsePrimaryExpression();
        $this->inFunc--;
        if ('for_definition' === $this->parser->getEnv() && $type === 'in') {
            return new $this->binaryOperators[$type]($left, $right, $this->parser->getEnv());
        }
        return new $this->binaryOperators[$type]($left, $right);
    }



    public function parsePrimaryExpression()
    {
        $token = $this->parser->getCurrentToken();

        switch ($token->getType()) {
            case Viewer_Token::NAME_TYPE:
                $this->parser->getStream()->next();
                switch ($token->getValue()) {
                    case 'true':
                    case 'TRUE':
                        $node = new Viewer_Node_Const(true);
                        break;

                    case 'false':
                    case 'FALSE':
                        $node = new Viewer_Node_Const(false);
                        break;

                    case 'none':
                    case 'NONE':
                    case 'null':
                    case 'NULL':
                        $node = new Viewer_Node_Const(null);
                        break;

                    default:
                        // For constants
                        if ($token->getValue() === strtoupper($token->getValue()) && defined($token->getValue())) {
                            $node = new Viewer_Node_Const($token->getValue());
                        // For functions
                        } elseif ('(' === $this->parser->getCurrentToken()->getValue()) {
                            $node = $this->getFunctionNode($token->getValue());
                        // For vars
                        } else {
                            $node = new Viewer_Node_Var($token->getValue());

                            if (in_array($token->getValue(), $this->parser->getStack())) {
                                $node->setTmpContext($token->getValue());
                            }
                        }
                        break;
                }
                break;

            case Viewer_Token::NUMBER_TYPE:
                $this->parser->getStream()->next();
                $node = new Viewer_Node_Const($token->getValue());
                break;

            case Viewer_Token::BLOCK_END_TYPE:
                $node = new Viewer_Node_Const(true);
                break;

            case Viewer_Token::STRING_TYPE:
                $node = $this->parseStringExpression();
                break;

            default:
                if ($token->test(Viewer_Token::PUNCTUATION_TYPE, '[')) {
                    $node = $this->parseArrayExpression();
                } else if ($token->test(Viewer_Token::PUNCTUATION_TYPE, '{')) {
                    $node = $this->parseJsonExpression();
                // Groups
                } else if ($token->test(Viewer_Token::PUNCTUATION_TYPE, '(')) {
                    $this->inFunc++;
                    $this->parser->getStream()->next();
                    $expr = $this->parseExpression();
                    $node = new Viewer_Node_Group($expr);
                    $this->parser->getStream()->next();
                    $this->inFunc--;
                } else {
                    throw new Exception("Unexpected token type.", $token->getLine());
                }
        }

        $node = $this->postfixExpression($node);


        // >2 parameters in IF block
        if ($this->parser->getCurrentToken()->test(Viewer_Token::OPERATOR_TYPE, array_keys($this->binaryOperators))) {
            $node = $this->parseOperatorExpression(
                $this->parser->setNode($node, $this->inFunc + 1),
                $this->parser->getCurrentToken()->getValue()
            );
        }


        return $this->parser->setNode($node, $this->inFunc);
    }


    public function postfixExpression($node)
    {
        while (true) {
            $token = $this->parser->getCurrentToken();
            if ($token->getType() == Viewer_Token::PUNCTUATION_TYPE) {
                if ('.' == $token->getValue() || '[' == $token->getValue()) {
                    $node = $this->parseSubscriptExpression($node);
                } elseif ('|' == $token->getValue()) {
                    $node = $this->parseFilterExpression($node);
                 //concat
                } elseif ('~' == $token->getValue()) {
                    $this->parser->getStream()->next();
                    $this->inFunc++;
                    $expr = $this->parsePrimaryExpression();
                    $node = new Viewer_Node_Concat($this->parser->setNode($node, $this->inFunc));
                    $node->addElement($expr);
                    $this->inFunc--;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $node;
    }


    public function parseFilterExpression($node) {
        while($this->parser->getStream()->getCurrent()->test(Viewer_Token::PUNCTUATION_TYPE, array('|'))) {
            $this->parser->getStream()->next();
            $filterName = $this->parser->getStream()->getCurrent()->getValue();
            $filterClassName = 'Viewer_Filter_'
                . ucfirst(preg_replace_callback('/_([a-z])/', function($c){
                    return strtoupper($c[1]);
                }, $filterName));
            
            if (!class_exists($filterClassName)) {
                throw new Exception("Unexpected filter name ({$filterName})");
            }
            
            $filter = new $filterClassName;
            
            $this->parser->getStream()->next();
            // params
            if ($this->parser->getStream()->getCurrent()->test(Viewer_Token::PUNCTUATION_TYPE, array('('))) {
                $this->inFunc++;
                $this->parser->getStream()->next();
                $filter->addParam($this->parsePrimaryExpression());
                while ($this->parser->getStream()->getCurrent()->test(Viewer_Token::PUNCTUATION_TYPE, array(','))) {
                    $this->parser->getStream()->next();
                    $param = $this->parsePrimaryExpression();
                    $filter->addParam($param);
                }
                $this->inFunc--;
                $this->parser->getStream()->next();
            }

            $node->addFilter($filter);
        }
        return $node;
    }


    public function parseSubscriptExpression($node)
    {
        $stream = $this->parser->getStream();
        $stream->getCurrent()->test(Viewer_Token::PUNCTUATION_TYPE, array('[', '.'));
        $punctuation_value = $stream->getCurrent()->getValue();

        switch ($punctuation_value) {
            case '[':
                $stream->next();
                $token = $stream->getCurrent();
                if (
                    $token->test(Viewer_Token::NUMBER_TYPE) ||
                    $token->test(Viewer_Token::STRING_TYPE)
                ) {
                    $node->addAttr($token->getValue());
                } else if ($token->test(Viewer_Token::NAME_TYPE)) {
                    $node->addAttr(new Viewer_Node_Var($token->getValue()));
                }
                //$stream->expect(Viewer_Token::NUMBER_TYPE);
                $stream->next();
                $stream->next();
                break;
            case '.':
                $stream->next();
                $token = $stream->getCurrent();
                $stream->expect(Viewer_Token::NAME_TYPE);
                $node->addAttr($token->getValue());
                break;
        }

        return $this->postfixExpression($node);
    }


    public function getFunctionNode($func)
    {
        $this->parser->getStream()->next();
        $node = $this->parser->getStream()->getCurrent();

        $this->inFunc++;

        if (')' === $node->getValue()) {
            $this->parser->getStream()->next();
            $this->inFunc--;
            return new Viewer_Node_Function($func);
        }

        $expr = new Viewer_Node_Function($func); //$this->parsePrimaryExpression()
        $expr->addParam($this->parsePrimaryExpression());

        while ($this->parser->getStream()->getCurrent()->test(Viewer_Token::PUNCTUATION_TYPE, array(','))) {
            $this->parser->getStream()->next();
            $param = $this->parsePrimaryExpression();
            $expr->addParam($param);
        }

        $this->inFunc--;

        $this->parser->getStream()->next();
        return $expr;
    }


    public function parseArrayExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Viewer_Token::PUNCTUATION_TYPE, '[', 'An array element was expected');

        $this->inFunc++;
        $node = new Viewer_Node_Array(array(), $stream->getCurrent()->getLine());
        $first = true;
        while (!$stream->test(Viewer_Token::PUNCTUATION_TYPE, ']')) {
            if (!$first) {
                $stream->expect(Viewer_Token::PUNCTUATION_TYPE, ',', 'An array element must be followed by a comma');

                // trailing ,?
                if ($stream->test(Viewer_Token::PUNCTUATION_TYPE, ']')) {
                    break;
                }
            }
            $first = false;

            $node->addElement($this->parseExpression());
        }
        $this->inFunc--;
        $stream->expect(Viewer_Token::PUNCTUATION_TYPE, ']', 'An opened array is not properly closed');

        return $node;
    }

    public function parseJsonExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Viewer_Token::PUNCTUATION_TYPE, '{', 'An array element was expected');

        $this->inFunc++;
        $node = new Viewer_Node_Array(array(), $stream->getCurrent()->getLine());
        $first = true;
        while (!$stream->test(Viewer_Token::PUNCTUATION_TYPE, '}')) {
            if (!$first) {
                $stream->expect(Viewer_Token::PUNCTUATION_TYPE, ',', 'An array element must be followed by a comma');

                // trailing ,?
                if ($stream->test(Viewer_Token::PUNCTUATION_TYPE, '}')) {
                    break;
                }
            }
            $first = false;

            $key = $stream->getCurrent()->getValue();
            $stream->next();
            $stream->expect(Viewer_Token::PUNCTUATION_TYPE, ':', 'An array keys, elements must be separated by a ":"');
            $value = $this->parseExpression();
            $node->addElement($value, $key);
        }
        $this->inFunc--;
        $stream->expect(Viewer_Token::PUNCTUATION_TYPE, '}', 'An opened array is not properly closed');

        return $node;
    }

    public function parseStringExpression()
    {
        $param = $this->parser->getStream()->getCurrent();
        $this->parser->getStream()->next();

        $expr = new Viewer_Node_Text($param->getValue());

        return $expr;
    }
}