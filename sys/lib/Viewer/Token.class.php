<?php

/**
 * Класс для работы с токенами в шаблонизаторе DarsiPro CMS
 * 
 * @project    DarsiPro CMS
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.0
 * @php        5.6+
 */
class Viewer_Token
{
    /**
     * @var string Значение токена
     */
    protected $value;
    
    /**
     * @var int Тип токена (использует константы класса)
     */
    protected $type;
    
    /**
     * @var int Номер строки в исходном коде, где был найден токен
     */
    protected $lineno;

    // Константы типов токенов
    const EOF_TYPE                  = -1;  // Конец файла
    const TEXT_TYPE                 = 0;   // Текстовый контент
    const BLOCK_START_TYPE          = 1;   // Начало блока (например {%)
    const VAR_START_TYPE            = 2;   // Начало переменной (например {{)
    const BLOCK_END_TYPE            = 3;   // Конец блока (например %})
    const VAR_END_TYPE              = 4;   // Конец переменной (например }})
    const NAME_TYPE                 = 5;   // Имя (переменной, функции и т.д.)
    const NUMBER_TYPE               = 6;   // Число
    const STRING_TYPE               = 7;   // Строка
    const OPERATOR_TYPE             = 8;   // Оператор (+, -, *, / и т.д.)
    const PUNCTUATION_TYPE          = 9;   // Пунктуация (точка, запятая и т.д.)
    const INTERPOLATION_START_TYPE  = 10;  // Начало интерполяции
    const INTERPOLATION_END_TYPE    = 11;  // Конец интерполяции
    const URL_START_TYPE            = 12;  // Начало URL
    const URL_END_TYPE              = 13;  // Конец URL
    const COMMENT_START_TYPE        = 14;  // Начало комментария
    const COMMENT_END_TYPE          = 15;  // Конец комментария

    /**
     * Конструктор класса
     *
     * @param int    $type   Тип токена (используйте константы класса)
     * @param string $value  Значение токена
     * @param int    $lineno Номер строки в исходном коде
     */
    public function __construct($type, $value, $lineno)
    {
        $this->type   = (int)$type;
        $this->value  = (string)$value;
        $this->lineno = (int)$lineno;
    }

    /**
     * Преобразует токен в строковое представление
     *
     * @return string Строковое представление токена
     */
    public function __toString()
    {
        return sprintf(
            '%s(%s)', 
            self::typeToString($this->type, true, $this->lineno), 
            $this->value
        );
    }

    /**
     * Проверяет токен на соответствие типу и/или значению
     *
     * Можно использовать в трех вариантах:
     * 1. Только тип: $token->test(TYPE)
     * 2. Тип и значение: $token->test(TYPE, 'value')
     * 3. Только значение (тип по умолчанию NAME_TYPE): $token->test('value')
     *
     * @param array|int       $type   Тип для проверки или значение, если тип не указан
     * @param array|string|null $values Значение или массив возможных значений для проверки
     *
     * @return bool Результат проверки
     */
    public function test($type, $values = null)
    {
        // Если передан только второй аргумент или первый аргумент не число
        if (null === $values && !is_int($type)) {
            $values = $type;
            $type = self::NAME_TYPE;
        }

        // Проверяем соответствие типа и значения
        return ($this->type === $type) && (
            null === $values ||
            (is_array($values) && in_array($this->value, $values)) ||
            $this->value == $values
        );
    }

    /**
     * Возвращает номер строки, в которой был найден токен
     *
     * @return int Номер строки
     */
    public function getLine()
    {
        return $this->lineno;
    }

    /**
     * Возвращает тип токена
     *
     * @return int Тип токена (используются константы класса)
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Возвращает значение токена
     *
     * @return string Значение токена
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Преобразует числовой тип токена в строковое представление
     *
     * @param int  $type  Числовой тип токена
     * @param bool $short Использовать короткое имя (без префикса класса)
     * @param int  $line  Номер строки для сообщения об ошибке
     *
     * @return string Строковое представление типа
     * @throws Exception Если передан неизвестный тип
     */
    public static function typeToString($type, $short = false, $line = -1)
    {
        // Маппинг типов токенов на их строковые представления
        static $typeMap = [
            self::EOF_TYPE                 => 'EOF_TYPE',
            self::TEXT_TYPE                => 'TEXT_TYPE',
            self::BLOCK_START_TYPE         => 'BLOCK_START_TYPE',
            self::VAR_START_TYPE          => 'VAR_START_TYPE',
            self::BLOCK_END_TYPE           => 'BLOCK_END_TYPE',
            self::VAR_END_TYPE            => 'VAR_END_TYPE',
            self::NAME_TYPE                => 'NAME_TYPE',
            self::NUMBER_TYPE             => 'NUMBER_TYPE',
            self::STRING_TYPE              => 'STRING_TYPE',
            self::OPERATOR_TYPE           => 'OPERATOR_TYPE',
            self::PUNCTUATION_TYPE         => 'PUNCTUATION_TYPE',
            self::INTERPOLATION_START_TYPE => 'INTERPOLATION_START_TYPE',
            self::INTERPOLATION_END_TYPE   => 'INTERPOLATION_END_TYPE',
            self::URL_START_TYPE           => 'URL_START_TYPE',
            self::URL_END_TYPE             => 'URL_END_TYPE',
        ];

        if (!isset($typeMap[$type])) {
            throw new Exception(
                sprintf('Token of type "%s" does not exist.', $type), 
                $line
            );
        }

        return $short ? $typeMap[$type] : 'Viewer_Token::' . $typeMap[$type];
    }
}