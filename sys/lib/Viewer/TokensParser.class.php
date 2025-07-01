<?php

/**
 * Класс для парсинга токенов в шаблонизаторе DarsiPro CMS
 *
 * @project    DarsiPro CMS
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.0
 * @php        5.6+
 */
class Viewer_TokensParser
{
    // Свойства класса
    private $delimiters;    // Разделители тегов
    private $regexes;       // Регулярные выражения для парсинга
    private $state;         // Текущее состояние парсера
    private $states;        // Стек состояний парсера
    private $position;      // Текущая позиция в массиве позиций
    private $positions;     // Массив позиций тегов в коде
    private $cursor;        // Текущая позиция в коде
    private $code;          // Исходный код для парсинга
    private $lineno;        // Номер текущей строки
    private $end;           // Длина кода
    private $tokens;        // Массив распарсенных токенов
    private $brackets;      // Стек скобок для проверки вложенности
    private $filename;      // Имя файла (для отладки)

    // Константы состояний парсера
    const STATE_DATA    = 0;    // Режим парсинга обычного текста
    const STATE_BLOCK   = 1;    // Режим парсинга блока ({% ... %})
    const STATE_VAR     = 2;    // Режим парсинга переменной ({{ ... }})
    const STATE_STRING  = 3;    // Режим парсинга строки в двойных кавычках
    const STATE_URL     = 4;    // Режим парсинга URL ([~ ... ~])
    const STATE_COMMENT = 5;    // Режим парсинга комментария ({# ... #})

    // Регулярные выражения для парсинга
    const REGEX_NAME            = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/uA';
    const REGEX_NUMBER          = '/\-?[0-9]+(?:\.[0-9]+)?/uA';
    const REGEX_STRING          = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/uAs';
    const REGEX_DQ_STRING_DELIM = '/"/uA';
    const REGEX_DQ_STRING_PART  = '/[^#"\\\\]*(?:(?:\\\\.|#(?!\{))[^#"\\\\]*)*/uAs';
    const PUNCTUATION           = '()[]{}?:.,~|';

    /**
     * Конструктор класса
     *
     * @param string $code Исходный код для парсинга
     */
    public function __construct($code = '')
    {
        // Установка кодировки
        mb_internal_encoding("ASCII");
        mb_internal_encoding("UTF-8");

        // Инициализация разделителей тегов
        $this->delimiters = array(
            'tag_var' => array('{{', '}}'),
            'tag_block' => array('{%', '%}'),
            'tag_url' => array('[~', '~]'),
            'tag_comment' => array('{#', '#}'),
        );

        // Инициализация регулярных выражений
        $this->regexes = array(
            'lex_var' => '#\s+' . preg_quote($this->delimiters['tag_var'][1], '#') . '#uA',
            'lex_url' => '#\s+' . preg_quote($this->delimiters['tag_url'][1], '#') . '#uA',
            'lex_comment' => '#\s+' . preg_quote($this->delimiters['tag_comment'][1], '#') . '#uA',
            'lex_block' => '#\s+(?:' . preg_quote($this->delimiters['tag_block'][1]) . '|' . 
                          preg_quote($this->delimiters['tag_block'][1]) . ')#uA',
            'lex_start' => '#(' . preg_quote($this->delimiters['tag_var'][0]) . '|' . 
                           preg_quote($this->delimiters['tag_block'][0]) . '|' . 
                           preg_quote($this->delimiters['tag_url'][0]) . '|' . 
                           preg_quote($this->delimiters['tag_comment'][0], '#') . ')\s#us',
            'operators' => '#not in(?=[\s()])|and(?=[\s()])|not(?=[\s()])|in(?=[\s()])|\<\=|\>\=|\=\=\=|\=\=|or(?=[\s()])|\!\=\=|\!\=|%|\>|\+|(?<!\(|,\s)-|\<|/{1,2}|\=|\*{1,2}#uA',
        );
    }

    /**
     * Основной метод парсинга кода в токены
     *
     * @param string $code Исходный код для парсинга
     * @param string|null $filename Имя файла (для отладки)
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
        $this->brackets = array();

        // Поиск всех стартовых тегов в коде
        preg_match_all($this->regexes['lex_start'], $this->code, $matches, PREG_OFFSET_CAPTURE);
        $this->positions = $matches;

        // Основной цикл парсинга
        while ($this->cursor < $this->end) {
            switch ($this->state) {
                case self::STATE_DATA:
                    $this->lexData();
                    break;

                case self::STATE_BLOCK:
                    $this->lexBlock();
                    break;

                case self::STATE_VAR:
                    $this->lexVar();
                    break;

                case self::STATE_STRING:
                    $this->lexString();
                    break;

                case self::STATE_URL:
                    $this->lexUrl();
                    break;

                case self::STATE_COMMENT:
                    $this->lexComment();
                    break;
            }
        }

        // Добавление токена конца файла
        $this->pushToken(Viewer_Token::EOF_TYPE);

        // Проверка незакрытых скобок
        if (!empty($this->brackets)) {
            list($expect, $lineno) = array_pop($this->brackets);
            throw new Exception(sprintf('Unclosed "%s"', $expect), $lineno);
        }

        return new Viewer_TokenStream($this->tokens, $this->filename);
    }

    /**
     * Парсинг строки в двойных кавычках
     */
    protected function lexString()
    {
        if (preg_match($this->regexes['interpolation_start'], $this->code, $match, null, $this->cursor)) {
            $this->brackets[] = array($this->options['interpolation'][0], $this->lineno);
            $this->pushToken(Viewer_Token::INTERPOLATION_START_TYPE);
            $this->moveCursor($match[0]);
            $this->pushState(self::STATE_INTERPOLATION);
        } elseif (preg_match(self::REGEX_DQ_STRING_PART, $this->code, $match, null, $this->cursor) && strlen($match[0]) > 0) {
            $this->pushToken(Viewer_Token::STRING_TYPE, stripcslashes($match[0]));
            $this->moveCursor($match[0]);
        } elseif (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, null, $this->cursor)) {
            list($expect, $lineno) = array_pop($this->brackets);
            if ($this->code[$this->cursor] != '"') {
                throw new Exception(sprintf('Unclosed "%s"', $expect), $lineno);
            }

            $this->popState();
            ++$this->cursor;
        }
    }

    /**
     * Парсинг URL ([~ ... ~])
     */
    protected function lexUrl()
    {
        if (empty($this->brackets) && preg_match($this->regexes['lex_url'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::URL_END_TYPE);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }

    /**
     * Парсинг комментария ({# ... #})
     */
    protected function lexComment()
    {
        $start = $this->cursor;
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
     * Парсинг переменной ({{ ... }})
     */
    protected function lexVar()
    {
        if (empty($this->brackets) && preg_match($this->regexes['lex_var'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::VAR_END_TYPE);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }

    /**
     * Парсинг обычного текста (вне тегов)
     */
    private function lexData()
    {
        // Если достигли конца массива позиций
        if ($this->position === count($this->positions[1]) - 1) {
            $this->pushToken(Viewer_Token::TEXT_TYPE, substr($this->code, $this->cursor));
            $this->cursor = $this->end;
            return;
        }

        // Находим следующую позицию тега
        $position = $this->positions[0][++$this->position];

        // Пропускаем теги, которые уже были обработаны
        while ($position[1] < $this->cursor) {
            if ($this->position == count($this->positions[0]) - 1) {
                return;
            }
            $position = $this->positions[0][++$this->position];
        }

        // Добавляем текст до тега
        $text = $textContent = substr($this->code, $this->cursor, $position[1] - $this->cursor);
        if (isset($this->positions[2][$this->position][0])) {
            $text = rtrim($text);
        }

        $this->pushToken(Viewer_Token::TEXT_TYPE, $text);
        $this->moveCursor($textContent.$position[0]);

        // Определяем тип тега и переключаем состояние
        switch ($this->positions[1][$this->position][0]) {
            case $this->delimiters['tag_block'][0]:
                $this->pushToken(Viewer_Token::BLOCK_START_TYPE);
                $this->pushState(self::STATE_BLOCK);
                break;

            case $this->delimiters['tag_var'][0]:
                $this->pushToken(Viewer_Token::VAR_START_TYPE);
                $this->pushState(self::STATE_VAR);
                break;

            case $this->delimiters['tag_url'][0]:
                $this->pushToken(Viewer_Token::URL_START_TYPE);
                $this->pushState(self::STATE_URL);
                break;

            case $this->delimiters['tag_comment'][0]:
                $this->pushToken(Viewer_Token::COMMENT_START_TYPE);
                $this->pushState(self::STATE_COMMENT);
                break;
        }
    }

    /**
     * Парсинг блока ({% ... %})
     */
    protected function lexBlock()
    {
        if (empty($this->brackets) && preg_match($this->regexes['lex_block'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::BLOCK_END_TYPE);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }

    /**
     * Парсинг выражений внутри тегов
     */
    protected function lexExpression()
    {
        // Пропускаем пробелы
        if (preg_match('/\s+/uA', $this->code, $match, null, $this->cursor)) {
            $this->moveCursor($match[0]);

            if ($this->cursor >= $this->end) {
                throw new Exception(sprintf('Unexpected end of file: Unclosed "%s"', 
                    $this->state === self::STATE_BLOCK ? 'block' : 'variable or url'));
            }
        }
        // Операторы
        elseif (preg_match($this->regexes['operators'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::OPERATOR_TYPE, $match[0]);
            $this->moveCursor($match[0]);
        }
        // Имена переменных/функций
        elseif (preg_match(self::REGEX_NAME, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Viewer_Token::NAME_TYPE, $match[0]);
            $this->moveCursor($match[0]);
        }
        // Числа
        elseif (preg_match(self::REGEX_NUMBER, $this->code, $match, null, $this->cursor)) {
            $number = (float) $match[0];
            if (ctype_digit($match[0]) && $number <= PHP_INT_MAX) {
                $number = (int) $match[0];
            }
            $this->pushToken(Viewer_Token::NUMBER_TYPE, $number);
            $this->moveCursor($match[0]);
        }
        // Знаки пунктуации
        elseif (false !== mb_strpos(self::PUNCTUATION, $this->code[$this->cursor])) {
            // Открывающая скобка
            if (false !== mb_strpos('([{', $this->code[$this->cursor])) {
                $this->brackets[] = array($this->code[$this->cursor], $this->lineno);
            }
            // Закрывающая скобка
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
        // Строки в кавычках
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
        // Неизвестный символ
        else {
            throw new Exception(sprintf('Unexpected character "%s"', $this->code[$this->cursor]), $this->lineno);
        }
    }

    /**
     * Добавление токена в массив
     *
     * @param int $type Тип токена
     * @param string $value Значение токена
     */
    private function pushToken($type, $value = '')
    {
        // Не добавляем пустые текстовые токены
        if (Viewer_Token::TEXT_TYPE === $type && '' === $value) {
            return;
        }

        $this->tokens[] = new Viewer_Token($type, $value, $this->lineno);
    }

    /**
     * Подготовка кода перед парсингом
     *
     * @param string $code Исходный код
     * @return string Обработанный код
     */
    private function prepareCode($code)
    {
        return str_replace(array("\r\n", "\r"), "\n", $code);
    }

    /**
     * Перемещение курсора и подсчет строк
     *
     * @param string $text Текст, на который перемещается курсор
     */
    protected function moveCursor($text)
    {
        $this->cursor += strlen($text);
        $this->lineno += mb_substr_count($text, "\n");
    }

    /**
     * Сохранение текущего состояния и переход в новое
     *
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