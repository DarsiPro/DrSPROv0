<?php

/**
 * Класс для парсинга дерева узлов шаблона в DarsiPro CMS
 *
 * @project    DarsiPro CMS
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.0
 * @php        5.6+
 */
class Viewer_TreesParser
{
    /**
     * @var Viewer_TokenStream Поток токенов для парсинга
     */
    protected $stream;
    
    /**
     * @var Viewer_ExpressionParser Парсер выражений
     */
    protected $expressionParser;
    
    /**
     * @var array Массив парсеров для различных типов токенов
     */
    protected $tokenParsers;
    
    /**
     * @var array Дерево узлов шаблона
     */
    protected $nodesTree;
    
    /**
     * @var string Текущее окружение парсера (if, for и т.д.)
     */
    protected $env;
    
    /**
     * @var mixed Текущее значение
     */
    protected $currentValue;
    
    /**
     * @var array Стек для отслеживания состояния парсера
     */
    public $stack = array();

    /**
     * Конструктор класса
     */
    public function __construct()
    {
        $this->expressionParser = new Viewer_ExpressionParser($this);
        
        // Инициализация парсеров для различных типов токенов
        $this->tokenParsers = array(
            'if' => 'Viewer_Parser_If',       // Парсер для условных блоков
            'for' => 'Viewer_Parser_For',     // Парсер для циклов
            'include' => 'Viewer_Parser_Include', // Парсер для включений
            'set' => 'Viewer_Parser_Set',     // Парсер для присваиваний
        );
    }

    /**
     * Основной метод парсинга потока токенов в дерево узлов
     *
     * @param Viewer_TokenStream $stream Поток токенов
     * @param callable|null $test Функция для проверки токенов (опционально)
     * @return Viewer_NodeTree Дерево узлов шаблона
     */
    public function parse(Viewer_TokenStream $stream, $test = null)
    {
        $this->stream = $stream;
        $rv = array(); // Результирующий массив узлов

        // Основной цикл парсинга
        while (!$this->stream->isEOF()) {
            switch ($this->getCurrentToken()->getType()) {
                // Обработка текстовых токенов
                case Viewer_Token::TEXT_TYPE:
                    $token = $this->stream->next();
                    $this->setCurrentValue($token->getValue());
                    $rv[] = $this->setNode(new Viewer_Node_Text($token->getValue()));
                    break;

                // Обработка переменных ({{ ... }})
                case Viewer_Token::VAR_START_TYPE:
                    $token = $this->stream->next();
                    $expr = $this->expressionParser->parseExpression();
                    $this->stream->expect(Viewer_Token::VAR_END_TYPE);
                    $rv[] = $expr;
                    break;

                // Обработка URL ([~ ... ~])
                case Viewer_Token::URL_START_TYPE:
                    $this->stream->next();
                    $token = $this->stream->getCurrent();
                    $expr = $this->setNode(new Viewer_Node_Url($token->getValue()));
                    $this->stream->next();
                    $this->stream->expect(Viewer_Token::URL_END_TYPE);
                    $rv[] = $expr;
                    break;

                // Обработка комментариев ({# ... #})
                case Viewer_Token::COMMENT_START_TYPE:
                    $this->stream->next();
                    $token = $this->stream->getCurrent();
                    $expr = $this->setNode(new Viewer_Node_Comment($token->getValue()));
                    $this->stream->next();
                    $this->stream->expect(Viewer_Token::COMMENT_END_TYPE);
                    $rv[] = $expr;
                    break;

                // Обработка блоков ({% ... %})
                case Viewer_Token::BLOCK_START_TYPE:
                    $this->stream->next();
                    $token = $this->getCurrentToken();

                    // Проверка токена с помощью callback-функции
                    if (null !== $test && call_user_func($test, $token)) {
                        return $rv;
                    }

                    // Получение специализированного парсера для текущего токена
                    $subparser = $this->getTokenParser($token->getValue());
                    $node = $subparser->parse($token);
                    
                    if (null !== $node) {
                        $rv[] = $node;
                    }
                    break;

                default:
                    // Переход к следующему токену для необработанных типов
                    $token = $this->stream->next();
                    // TODO: Добавить обработку других типов токенов
            }
        }

        $this->nodesTree = $rv;
        return new Viewer_NodeTree($rv);
    }

    /**
     * Возвращает текущий стек парсера
     *
     * @return array Текущий стек
     */
    public function getStack()
    {
        return $this->stack;
    }

    /**
     * Добавляет значение в стек парсера
     *
     * @param mixed $key Значение для добавления в стек
     */
    public function setStack($key)
    {
        $this->stack[] = $key;
    }

    /**
     * Очищает стек парсера
     */
    public function cleanStack()
    {
        $this->stack = array();
    }

    /**
     * Возвращает парсер для указанного типа токена
     *
     * @param string $value Тип токена
     * @return object Парсер для указанного типа токена
     * @throws Exception Если парсер не найден
     */
    private function getTokenParser($value)
    {
        if (!array_key_exists($value, $this->tokenParsers)) {
            throw new Exception("Parser for token '$value' not found.");
        }

        return new $this->tokenParsers[$value]($this);
    }

    /**
     * Возвращает парсер выражений
     *
     * @return Viewer_ExpressionParser Парсер выражений
     */
    public function getExpression()
    {
        return $this->expressionParser;
    }

    /**
     * Возвращает текущий поток токенов
     *
     * @return Viewer_TokenStream Поток токенов
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Возвращает текущий токен
     *
     * @return Viewer_Token Текущий токен
     */
    public function getCurrentToken()
    {
        return $this->stream->getCurrent();
    }

    /**
     * Возвращает текущее окружение парсера
     *
     * @return string Текущее окружение
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Устанавливает окружение парсера
     *
     * @param string $env Новое окружение (if, for и т.д.)
     */
    public function setEnv($env)
    {
        $this->env = $env;
    }

    /**
     * Устанавливает текущее значение
     *
     * @param mixed $value Значение для установки
     */
    public function setCurrentValue($value)
    {
        $this->currentValue = $value;
    }

    /**
     * Возвращает текущее значение
     *
     * @return mixed Текущее значение
     */
    public function getCurrentValue()
    {
        return $this->currentValue;
    }

    /**
     * Создает узел для вывода значения
     *
     * @param mixed $node Узел или значение для вывода
     * @return Viewer_Node_Print Узел вывода
     */
    public function setPrint($node)
    {
        return new Viewer_Node_Print($node);
    }

    /**
     * Обрабатывает узел в зависимости от текущего окружения
     *
     * @param mixed $node Узел для обработки
     * @param bool $inFunc Флаг, указывающий что узел находится внутри функции
     * @return mixed Обработанный узел
     */
    public function setNode($node, $inFunc = false)
    {
        // Пропускаем обработку комментариев
        if ($node instanceof Viewer_Node_Comment) {
            return $node;
        }

        // Обработка узла в зависимости от текущего окружения
        switch ($this->getEnv()) {
            case 'set_left':
                // Особый случай - не изменяем узел
                break;
                
            case 'if':
            case 'for_definition':
            case 'set_right':
                // Помечаем переменные как определенные
                if ($node instanceof Viewer_Node_Var) {
                    $node->setDef(true);
                }
                break;

            default:
                // Общий случай - помечаем переменные и оборачиваем в узел вывода
                if ($node instanceof Viewer_Node_Var) {
                    $node->setDef(true);
                }
                if (!$inFunc) {
                    $node = $this->setPrint($node);
                }
                break;
        }

        return $node;
    }
}