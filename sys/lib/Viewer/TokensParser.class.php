<?php

class Viewer_TokensParser
{
    // Приватные свойства класса
    private $delimiters;     // Разделители тегов (начало и конец)
    private $regexes;        // Регулярные выражения для разбора
    private $state;          // Текущее состояние парсера
    private $states;         // Стек состояний парсера
    private $position;       // Текущая позиция в массиве позиций
    private $positions;      // Массив позиций тегов в коде
    private $cursor;        // Текущая позиция в коде
    private $code;          // Исходный код для разбора
    private $lineno;        // Номер текущей строки
    private $end;           // Длина кода
    private $tokens;        // Массив токенов
    private $brackets;      // Стек скобок для проверки вложенности
    private $filename;      // Имя файла (если есть)

    // Константы состояний парсера
    const STATE_DATA            = 0;  // Обработка обычного текста
    const STATE_BLOCK           = 1;  // Обработка блока ({% %})
    const STATE_VAR             = 2;  // Обработка переменной ({{ }} или $ $)
    const STATE_STRING          = 3;  // Обработка строки в двойных кавычках
    const STATE_URL             = 4;  // Обработка URL ([~ ~])
    const STATE_COMMENT         = 5;  // Обработка комментария ({# #})

    // Регулярные выражения для разбора
    const REGEX_NAME            = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/uA';  // Имена переменных
    const REGEX_NUMBER          = '/\-?[0-9]+(?:\.[0-9]+)?/uA';                     // Числа
    const REGEX_STRING          = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/uAs';  // Строки
    const REGEX_DQ_STRING_DELIM = '/"/uA';                                          // Двойные кавычки
    const REGEX_DQ_STRING_PART  = '/[^#"\\\\]*(?:(?:\\\\.|#(?!\{))[^#"\\\\]*)*/uAs'; // Части строки в двойных кавычках
    const PUNCTUATION           = '()[]{}?:.,~|';                                   // Знаки пунктуации

    public function __construct($code = '')
    {
        // Устанавливаем кодировку
        mb_internal_encoding("UTF-8");

        // Определяем разделители тегов
        $this->delimiters = array(
            'tag_var' => array('{{', '}}'),     // Переменные
            'tag_var_alt' => array('$', '$'),   // Альтернативный синтаксис переменных
            'tag_block' => array('{%', '%}'),   // Блоки
            'tag_url' => array('[~', '~]'),     // URL
            'tag_comment' => array('{#', '#}'), // Комментарии
        );

        // Компилируем регулярные выражения для разбора
        $this->regexes = array(
            'lex_var' => '#\s*(?:' . preg_quote($this->delimiters['tag_var'][1], '#') . '|' . preg_quote($this->delimiters['tag_var_alt'][1], '#') . ')#uA',
            'lex_url' => '#\s*' . preg_quote($this->delimiters['tag_url'][1], '#') . '#uA',
            'lex_comment' => '#\s*' . preg_quote($this->delimiters['tag_comment'][1], '#') . '#uA',
            'lex_block' => '#\s*(?:' . preg_quote($this->delimiters['tag_block'][1]) . ')#uA',
            'lex_start' => '#(' . 
                preg_quote($this->delimiters['tag_var'][0]) . '|' .
                '(?<!\$)\$(?![\$\w])|' . // Одиночный $, не перед другим $ или буквой
                preg_quote($this->delimiters['tag_block'][0]) . '|' .
                preg_quote($this->delimiters['tag_url'][0]) . '|' .
                preg_quote($this->delimiters['tag_comment'][0], '#') .
                ')(?:\s|$)#us',
            'operators' => '#not in(?=[\s()])|and(?=[\s()])|not(?=[\s()])|in(?=[\s()])|\<\=|\>\=|\=\=\=|\=\=|or(?=[\s()])|\!\=\=|\!\=|%|\>|\+|(?<!\(|,\s)-|\<|/{1,2}|\=|\*{1,2}#uA',
            'var_alt_end_lookahead' => '#(?=\S*' . preg_quote($this->delimiters['tag_var_alt'][1], '#') . ')#uA',
        );
    }

    /**
     * Основной метод разбора кода в токены
     * @param string $code Исходный код
     * @param string|null $filename Имя файла (опционально)
     * @return Viewer_TokenStream Поток токенов
     */
    public function parseTokens($code, $filename = null)
    {
        // Инициализация состояния парсера
        $this->state = self::STATE_DATA;
        $this->code = $this->prepareCode($code);
        $this->lineno = 1;
        $this->end = strlen($this->code);
        $this->tokens = array();
        $this->position = -1;
        $this->cursor = 0;
        $this->filename = $filename;

        // Находим все начальные теги в коде
        preg_match_all($this->regexes['lex_start'], $this->code, $matches, PREG_OFFSET_CAPTURE);
        $this->positions = $matches;

        // Основной цикл разбора
        while ($this->cursor < $this->end) {
            switch ($this->state) {
                case self::STATE_DATA:
                    $this->lexData();    // Обработка обычного текста
                    break;

                case self::STATE_BLOCK:
                    $this->lexBlock();    // Обработка блока
                    break;

                case self::STATE_VAR:
                    $this->lexVar();     // Обработка переменной
                    break;

                case self::STATE_STRING:
                    $this->lexString();   // Обработка строки
                    break;

                case self::STATE_URL:
                    $this->lexUrl();      // Обработка URL
                    break;
                case self::STATE_COMMENT:
                    $this->lexComment();  // Обработка комментария
                    break;
            }
        }

        // Добавляем токен конца файла
        $this->pushToken(Viewer_Token::EOF_TYPE);

        // Проверяем незакрытые скобки
        if (!empty($this->brackets)) {
            list($expect, $lineno) = array_pop($this->brackets);
            throw new Exception(sprintf('Unclosed "%s"', $expect), $lineno);
        }

        // Возвращаем поток токенов
        return new Viewer_TokenStream($this->tokens, $this->filename);
    }

    /**
     * Обработка строки в двойных кавычках
     */
    protected function lexString()
    {
        if (preg_match($this->regexes['interpolation_start'], $this->code, $match, null, $this->cursor)) {
            $this->brackets[] = array($this->options['interpolation'][0], $this->lineno);
            $this->pushToken(Viewer_Token::INTERPOLATION_START_TYPE);
            $this->moveCursor($match[0]);
            $this->pushState(self::STATE_INTERPOLATION);
        } else if (preg_match(self::REGEX_DQ_STRING_PART, $this->code, $match, null, $this->cursor) && strlen($match[0]) > 0) {
            $this->pushToken(Viewer_Token::STRING_TYPE, stripcslashes($match[0]));
            $this->moveCursor($match[0]);
        } else if (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, null, $this->cursor)) {
            list($expect, $lineno) = array_pop($this->brackets);
            if ($this->code[$this->cursor] != '"') {
                throw new Exception(sprintf('Unclosed "%s"', $expect), $lineno);
            }
            $this->popState();
            ++$this->cursor;
            return;
        }
    }

    /**
     * Обработка URL ([~ ~])
     */
    protected function lexUrl()
    {
        if (empty($this->brackets) && preg_match($this->regexes['lex_url'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::URL_END_TYPE);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();  // Разбираем выражение внутри URL
        }
    }

    /**
     * Обработка комментария ({# #})
     */
    protected function lexComment()
    {
        $start = $this->cursor;
        // Пропускаем весь текст до закрывающего тега комментария
        while (!(empty($this->brackets) && preg_match($this->regexes['lex_comment'], $this->code, $match, null, $this->cursor))) {
            $this->cursor++;
        }
        $end = $this->cursor;

        $text = substr($this->code, $start, $end - $start);
        $this->pushToken(Viewer_Token::TEXT_TYPE, $text);
        $this->pushToken(Viewer_Token::COMMENT_END_TYPE);
        $this->moveCursor($match[0]);
        $this->popState();
    }

    /**
     * Обработка переменной ({{ }} или $ $)
     */
    protected function lexVar()
    {
        $endTags = [
            $this->delimiters['tag_var'][1],
            $this->delimiters['tag_var_alt'][1]
        ];
        
        $pattern = '#\s*(?:' . preg_quote($endTags[0], '#') . '|' . preg_quote($endTags[1], '#') . ')#uA';
        
        // Специальная обработка для альтернативного синтаксиса ($ $)
        if ($this->positions[1][$this->position][0] == '$') {
            $remainingCode = substr($this->code, $this->cursor);
            if (strpos($remainingCode, '$') === false) {
                // Если нет закрывающего $, обрабатываем как обычный текст
                $this->cursor = $this->positions[0][$this->position][1];
                $this->pushToken(Viewer_Token::TEXT_TYPE, '$');
                $this->cursor++;
                $this->popState();
                return;
            }
        }

        // Если нашли закрывающий тег
        if (empty($this->brackets) && preg_match($pattern, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::VAR_END_TYPE);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();  // Разбираем выражение внутри переменной
        }
    }

    /**
     * Обработка обычного текста (вне тегов)
     */
    private function lexData()
    {
        // Если это последняя позиция, обрабатываем оставшийся текст
        if ($this->position === count($this->positions[1]) - 1) {
            $this->pushToken(Viewer_Token::TEXT_TYPE, substr($this->code, $this->cursor));
            $this->cursor = $this->end;
            return;
        }

        // Переходим к следующей позиции
        $position = $this->positions[0][++$this->position];

        // Пропускаем позиции, которые уже обработаны
        while ($position[1] < $this->cursor) {
            if ($this->position == count($this->positions[0]) - 1) {
                return;
            }
            $position = $this->positions[0][++$this->position];
        }

        // Извлекаем текст до следующего тега
        $text = $textContent = substr($this->code, $this->cursor, $position[1] - $this->cursor);
        if (isset($this->positions[2][$this->position][0])) {
            $text = rtrim($text);
        }

        $this->pushToken(Viewer_Token::TEXT_TYPE, $text);
        $this->moveCursor($textContent.$position[0]);

        // Определяем тип тега и переходим в соответствующее состояние
        switch ($this->positions[1][$this->position][0]) {
            case $this->delimiters['tag_block'][0]:  // {% - начало блока
                $this->pushToken(Viewer_Token::BLOCK_START_TYPE);
                $this->pushState(self::STATE_BLOCK);
                break;

            case $this->delimiters['tag_var'][0]:     // {{ - начало переменной
                $this->pushToken(Viewer_Token::VAR_START_TYPE);
                $this->pushState(self::STATE_VAR);
                break;

            case $this->delimiters['tag_var_alt'][0]: // $ - альтернативный синтаксис переменной
                // Проверяем что это действительно тег (есть закрывающий $)
                $nextDollarPos = strpos($this->code, '$', $this->cursor);
                if ($nextDollarPos === false || preg_match('/\s/', substr($this->code, $this->cursor, $nextDollarPos - $this->cursor))) {
                    // Если нет закрывающего $ или между $ есть пробелы - обрабатываем как текст
                    $this->pushToken(Viewer_Token::TEXT_TYPE, '$');
                    $this->cursor++;
                    break;
                }
                $this->pushToken(Viewer_Token::VAR_START_TYPE);
                $this->pushState(self::STATE_VAR);
                break;

            case $this->delimiters['tag_url'][0]:     // [~ - начало URL
                $this->pushToken(Viewer_Token::URL_START_TYPE);
                $this->pushState(self::STATE_URL);
                break;
            case $this->delimiters['tag_comment'][0]: // {# - начало комментария
                $this->pushToken(Viewer_Token::COMMENT_START_TYPE);
                $this->pushState(self::STATE_COMMENT);
                break;
        }
    }

    /**
     * Обработка блока ({% %})
     */
    protected function lexBlock()
    {
        if (empty($this->brackets) && preg_match($this->regexes['lex_block'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::BLOCK_END_TYPE);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();  // Разбираем выражение внутри блока
        }
    }

    /**
     * Разбор выражений (общая часть для блоков, переменных и URL)
     */
    protected function lexExpression()
    {
        // Пропускаем пробелы
        if (preg_match('/\s+/uA', $this->code, $match, null, $this->cursor)) {
            $this->moveCursor($match[0]);

            if ($this->cursor >= $this->end) {
                throw new Exception(sprintf('Unexpected end of file: Unclosed "%s"', $this->state === self::STATE_BLOCK ? 'block' : 'variable or url'));
            }
        }

        // Разбор операторов
        if (preg_match($this->regexes['operators'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::OPERATOR_TYPE, $match[0]);
            $this->moveCursor($match[0]);
        }
        // Разбор имен переменных
        elseif (preg_match(self::REGEX_NAME, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::NAME_TYPE, $match[0]);
            $this->moveCursor($match[0]);
        }
        // Разбор чисел
        elseif (preg_match(self::REGEX_NUMBER, $this->code, $match, null, $this->cursor)) {
            $number = (float) $match[0];
            if (ctype_digit($match[0]) && $number <= PHP_INT_MAX) {
                $number = (int) $match[0];
            }

            $this->pushToken(Viewer_Token::NUMBER_TYPE, $number);
            $this->moveCursor($match[0]);
        }
        // Разбор пунктуации
        elseif (false !== mb_strpos(self::PUNCTUATION, $this->code[$this->cursor])) {
            // Открывающие скобки добавляем в стек
            if (false !== mb_strpos('([{', $this->code[$this->cursor])) {
                $this->brackets[] = array($this->code[$this->cursor], $this->lineno);
            }
            // Закрывающие скобки проверяем на соответствие
            elseif (false !== mb_strpos(')]}', $this->code[$this->cursor])) {
                if (empty($this->brackets)) {
                    throw new Exception(sprintf('Unexpected "%s"', $this->code[$this->cursor]), $this->lineno);
                }

                list($expect, $lineno) = array_pop($this->brackets);
                if ($this->code[$this->cursor] != strtr($expect, '([{', ')]}')) {
                    throw new Exception(sprintf('Unclosed "%s"', $expect), $lineno);
                }
            }

            $this->pushToken(Viewer_Token::PUNCTUATION_TYPE, $this->code[$this->cursor]);
            ++$this->cursor;
        }
        // Разбор строк в кавычках
        elseif (preg_match(self::REGEX_STRING, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::STRING_TYPE, stripcslashes(substr($match[0], 1, -1)));
            $this->moveCursor($match[0]);
        }
        // Начало строки в двойных кавычках
        elseif (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, null, $this->cursor)) {
            $this->brackets[] = array('"', $this->lineno);
            $this->pushState(self::STATE_STRING);
            $this->moveCursor($match[0]);
        }
        else {
            throw new Exception(sprintf('Unexpected character "%s"', $this->code[$this->cursor]), $this->lineno);
        }
    }

    /**
     * Добавление токена в массив
     * @param string $type Тип токена
     * @param string $value Значение токена
     */
    private function pushToken($type, $value = '') {
        if (Viewer_Token::TEXT_TYPE === $type && '' === $value) {
            return;
        }

        $this->tokens[] = new Viewer_Token($type, $value, $this->lineno);
    }

    /**
     * Подготовка кода (нормализация переводов строк)
     * @param string $code Исходный код
     * @return string Подготовленный код
     */
    private function prepareCode($code)
    {
        return str_replace(array("\r\n", "\r"), "\n", $code);
    }

    /**
     * Перемещение курсора с подсчетом строк
     * @param string $text Текст, на который перемещаем курсор
     */
    protected function moveCursor($text)
    {
        $this->cursor += strlen($text);
        $this->lineno += mb_substr_count($text, "\n");
    }

    /**
     * Сохранение текущего состояния и переход в новое
     * @param int $state Новое состояние
     */
    protected function pushState($state)
    {
        $this->states[] = $this->state;
        $this->state = $state;
    }

    /**
     * Восстановление предыдущего состояния
     */
    protected function popState()
    {
        if (0 === count($this->states)) {
            throw new Exception('Cannot pop state without a previous state');
        }

        $this->state = array_pop($this->states);
    }
}