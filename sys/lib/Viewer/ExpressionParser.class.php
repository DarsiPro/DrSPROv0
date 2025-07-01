<?php

/**
 * Класс для парсинга выражений в шаблонизаторе DarsiPro CMS
 * 
 * @project    DarsiPro CMS
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.0
 * @php        5.6+
 */
class Viewer_ExpressionParser
{
    /**
     * @var Viewer_TreesParser Экземпляр парсера деревьев
     */
    private $parser;
    
    /**
     * @var array Список бинарных операторов и соответствующих им классов
     */
    private $binaryOperators;
    
    /**
     * @var int Счетчик вложенности функций
     */
    private $inFunc = 0;
    
    /**
     * @var int Счетчик вложенности определений if
     */
    private $inIfDefinition = 0;

    /**
     * Конструктор класса
     * 
     * @param Viewer_TreesParser $parser Экземпляр парсера деревьев
     */
    public function __construct(Viewer_TreesParser $parser)
    {
        $this->parser = $parser;
        
        // Инициализация списка бинарных операторов
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

    /**
     * Парсинг выражения с учетом приоритета операторов
     * 
     * @param int $precedence Приоритет оператора (по умолчанию 0)
     * @return mixed Результат парсинга выражения
     * @throws Exception Если встречен неизвестный тип токена
     */
    public function parseExpression($precedence = 0)
    {
        // Парсим первичное выражение
        $node = $this->parsePrimaryExpression();
        $currToken = $this->parser->getStream()->getCurrent();

        // Обрабатываем операторы или конец блока
        switch ($currToken->getType()) {
            case Viewer_Token::OPERATOR_TYPE:
                $node = $this->parseOperatorExpression($node, $currToken->getValue());
                break;
            case Viewer_Token::BLOCK_END_TYPE:
                $node = $this->parseOperatorExpression($node, null);
                break;
        }
        
        return $node;
    }

    /**
     * Парсинг выражения с оператором
     * 
     * @param mixed $left Левый операнд
     * @param string|null $type Тип оператора
     * @return mixed Результат парсинга операторного выражения
     * @throws Exception Если оператор не существует
     */
    public function parseOperatorExpression($left, $type)
    {
        $this->inFunc++;
        
        // Проверка существования оператора
        if (!empty($type) && !array_key_exists($type, $this->binaryOperators)) {
            throw new Exception("Оператор '$type' не существует.");
        }

        $stream = $this->parser->getStream();

        // Обработка случая, когда в IF только один параметр (if($var))
        if ($stream->getCurrent()->getType() == Viewer_Token::BLOCK_END_TYPE) {
            $this->inFunc--;
            return new $this->binaryOperators['==']($left, null, true);
        }

        $stream->next();
        $token = $stream->getCurrent();

        // Установка временной переменной при определении цикла foreach
        if ('for_definition' === $this->parser->getEnv()) {
            $this->parser->setStack($left->getValue());
        }
        
        $right = $this->parsePrimaryExpression();
        $this->inFunc--;
        
        // Специальная обработка оператора 'in' в контексте цикла foreach
        if ('for_definition' === $this->parser->getEnv() && $type === 'in') {
            return new $this->binaryOperators[$type]($left, $right, $this->parser->getEnv());
        }
        
        return new $this->binaryOperators[$type]($left, $right);
    }

    /**
     * Парсинг первичного выражения
     * 
     * @return mixed Результат парсинга первичного выражения
     * @throws Exception Если встречен неизвестный тип токена
     */
    public function parsePrimaryExpression()
    {
        $token = $this->parser->getCurrentToken();
        $node = null;

        switch ($token->getType()) {
            case Viewer_Token::NAME_TYPE:
                $this->parser->getStream()->next();
                $node = $this->handleNameTypeToken($token);
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
                $node = $this->handlePunctuationTypeToken($token);
        }

        // Обработка постфиксных выражений
        $node = $this->postfixExpression($node);

        // Обработка случаев с более чем 2 параметрами в блоке IF
        if ($this->parser->getCurrentToken()->test(Viewer_Token::OPERATOR_TYPE, array_keys($this->binaryOperators))) {
            $node = $this->parseOperatorExpression(
                $this->parser->setNode($node, $this->inFunc + 1),
                $this->parser->getCurrentToken()->getValue()
            );
        }

        return $this->parser->setNode($node, $this->inFunc);
    }

    /**
     * Обработка токена типа NAME
     * 
     * @param Viewer_Token $token Токен
     * @return mixed Результат обработки
     */
    private function handleNameTypeToken(Viewer_Token $token)
    {
        switch ($token->getValue()) {
            case 'true':
            case 'TRUE':
                return new Viewer_Node_Const(true);

            case 'false':
            case 'FALSE':
                return new Viewer_Node_Const(false);

            case 'none':
            case 'NONE':
            case 'null':
            case 'NULL':
                return new Viewer_Node_Const(null);

            default:
                // Обработка констант
                if ($token->getValue() === strtoupper($token->getValue()) && defined($token->getValue())) {
                    return new Viewer_Node_Const($token->getValue());
                // Обработка функций
                } elseif ('(' === $this->parser->getCurrentToken()->getValue()) {
                    return $this->getFunctionNode($token->getValue());
                // Обработка переменных
                } else {
                    $node = new Viewer_Node_Var($token->getValue());

                    if (in_array($token->getValue(), $this->parser->getStack())) {
                        $node->setTmpContext($token->getValue());
                    }
                    
                    return $node;
                }
        }
    }

    /**
     * Обработка токена типа PUNCTUATION
     * 
     * @param Viewer_Token $token Токен
     * @return mixed Результат обработки
     * @throws Exception Если встречен неизвестный тип токена
     */
    private function handlePunctuationTypeToken(Viewer_Token $token)
    {
        if ($token->test(Viewer_Token::PUNCTUATION_TYPE, '[')) {
            return $this->parseArrayExpression();
        } elseif ($token->test(Viewer_Token::PUNCTUATION_TYPE, '{')) {
            return $this->parseJsonExpression();
        } elseif ($token->test(Viewer_Token::PUNCTUATION_TYPE, '(')) {
            $this->inFunc++;
            $this->parser->getStream()->next();
            $expr = $this->parseExpression();
            $node = new Viewer_Node_Group($expr);
            $this->parser->getStream()->next();
            $this->inFunc--;
            return $node;
        } else {
            throw new Exception("Неожиданный тип токена.", $token->getLine());
        }
    }

    /**
     * Обработка постфиксных выражений
     * 
     * @param mixed $node Узел для обработки
     * @return mixed Результат обработки
     */
    public function postfixExpression($node)
    {
        while (true) {
            $token = $this->parser->getCurrentToken();
            
            // Проверяем только токены типа PUNCTUATION
            if ($token->getType() != Viewer_Token::PUNCTUATION_TYPE) {
                break;
            }

            switch ($token->getValue()) {
                case '.':
                case '[':
                    $node = $this->parseSubscriptExpression($node);
                    break;
                case '|':
                    $node = $this->parseFilterExpression($node);
                    break;
                case '~':
                    $this->parser->getStream()->next();
                    $this->inFunc++;
                    $expr = $this->parsePrimaryExpression();
                    $node = new Viewer_Node_Concat($this->parser->setNode($node, $this->inFunc));
                    $node->addElement($expr);
                    $this->inFunc--;
                    break;
                default:
                    break 2; // Выход из цикла и switch
            }
        }

        return $node;
    }

    /**
     * Парсинг выражения с фильтром
     * 
     * @param mixed $node Узел для применения фильтра
     * @return mixed Результат с примененными фильтрами
     * @throws Exception Если фильтр не существует
     */
    public function parseFilterExpression($node)
    {
        while ($this->parser->getStream()->getCurrent()->test(Viewer_Token::PUNCTUATION_TYPE, array('|'))) {
            $this->parser->getStream()->next();
            
            // Получаем имя фильтра и преобразуем его в имя класса
            $filterName = $this->parser->getStream()->getCurrent()->getValue();
            $filterClassName = 'Viewer_Filter_' . ucfirst(preg_replace_callback(
                '/_([a-z])/', 
                function($c) { return strtoupper($c[1]); }, 
                $filterName
            ));
            
            // Проверка существования класса фильтра
            if (!class_exists($filterClassName)) {
                throw new Exception("Фильтр '{$filterName}' не существует");
            }
            
            $filter = new $filterClassName;
            $this->parser->getStream()->next();
            
            // Обработка параметров фильтра, если они есть
            if ($this->parser->getStream()->getCurrent()->test(Viewer_Token::PUNCTUATION_TYPE, array('('))) {
                $this->inFunc++;
                $this->parser->getStream()->next();
                $filter->addParam($this->parsePrimaryExpression());
                
                // Обработка нескольких параметров
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

    /**
     * Парсинг выражения с подписями (доступ к свойствам/элементам)
     * 
     * @param mixed $node Узел для обработки
     * @return mixed Результат с обработанными подписями
     */
    public function parseSubscriptExpression($node)
    {
        $stream = $this->parser->getStream();
        $stream->getCurrent()->test(Viewer_Token::PUNCTUATION_TYPE, array('[', '.'));
        $punctuationValue = $stream->getCurrent()->getValue();

        switch ($punctuationValue) {
            case '[':
                $stream->next();
                $token = $stream->getCurrent();
                
                // Обработка числового или строкового индекса
                if ($token->test(Viewer_Token::NUMBER_TYPE) || $token->test(Viewer_Token::STRING_TYPE)) {
                    $node->addAttr($token->getValue());
                } elseif ($token->test(Viewer_Token::NAME_TYPE)) {
                    $node->addAttr(new Viewer_Node_Var($token->getValue()));
                }
                
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

    /**
     * Создание узла функции
     * 
     * @param string $func Имя функции
     * @return Viewer_Node_Function Узел функции
     */
    public function getFunctionNode($func)
    {
        $this->parser->getStream()->next();
        $node = $this->parser->getStream()->getCurrent();

        $this->inFunc++;

        // Обработка функции без параметров
        if (')' === $node->getValue()) {
            $this->parser->getStream()->next();
            $this->inFunc--;
            return new Viewer_Node_Function($func);
        }

        $expr = new Viewer_Node_Function($func);
        $expr->addParam($this->parsePrimaryExpression());

        // Обработка нескольких параметров функции
        while ($this->parser->getStream()->getCurrent()->test(Viewer_Token::PUNCTUATION_TYPE, array(','))) {
            $this->parser->getStream()->next();
            $param = $this->parsePrimaryExpression();
            $expr->addParam($param);
        }

        $this->inFunc--;
        $this->parser->getStream()->next();
        
        return $expr;
    }

    /**
     * Парсинг массива
     * 
     * @return Viewer_Node_Array Узел массива
     * @throws Exception Если массив неправильно закрыт
     */
    public function parseArrayExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Viewer_Token::PUNCTUATION_TYPE, '[', 'Ожидается элемент массива');

        $this->inFunc++;
        $node = new Viewer_Node_Array(array(), $stream->getCurrent()->getLine());
        $first = true;
        
        while (!$stream->test(Viewer_Token::PUNCTUATION_TYPE, ']')) {
            if (!$first) {
                $stream->expect(
                    Viewer_Token::PUNCTUATION_TYPE, 
                    ',', 
                    'Элемент массива должен разделяться запятой'
                );

                // Проверка на лишнюю запятую в конце
                if ($stream->test(Viewer_Token::PUNCTUATION_TYPE, ']')) {
                    break;
                }
            }
            $first = false;

            $node->addElement($this->parseExpression());
        }
        
        $this->inFunc--;
        $stream->expect(Viewer_Token::PUNCTUATION_TYPE, ']', 'Массив не закрыт');
        
        return $node;
    }

    /**
     * Парсинг JSON-подобного объекта
     * 
     * @return Viewer_Node_Array Узел массива с ключами
     * @throws Exception Если объект неправильно закрыт
     */
    public function parseJsonExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Viewer_Token::PUNCTUATION_TYPE, '{', 'Ожидается элемент объекта');

        $this->inFunc++;
        $node = new Viewer_Node_Array(array(), $stream->getCurrent()->getLine());
        $first = true;
        
        while (!$stream->test(Viewer_Token::PUNCTUATION_TYPE, '}')) {
            if (!$first) {
                $stream->expect(
                    Viewer_Token::PUNCTUATION_TYPE, 
                    ',', 
                    'Элемент объекта должен разделяться запятой'
                );

                // Проверка на лишнюю запятую в конце
                if ($stream->test(Viewer_Token::PUNCTUATION_TYPE, '}')) {
                    break;
                }
            }
            $first = false;

            $key = $stream->getCurrent()->getValue();
            $stream->next();
            $stream->expect(
                Viewer_Token::PUNCTUATION_TYPE, 
                ':', 
                'Ключ и значение объекта должны разделяться двоеточием'
            );
            
            $value = $this->parseExpression();
            $node->addElement($value, $key);
        }
        
        $this->inFunc--;
        $stream->expect(Viewer_Token::PUNCTUATION_TYPE, '}', 'Объект не закрыт');
        
        return $node;
    }

    /**
     * Парсинг строкового выражения
     * 
     * @return Viewer_Node_Text Узел текста
     */
    public function parseStringExpression()
    {
        $param = $this->parser->getStream()->getCurrent();
        $this->parser->getStream()->next();

        return new Viewer_Node_Text($param->getValue());
    }
}