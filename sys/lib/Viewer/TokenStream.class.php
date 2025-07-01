<?php

/**
 * Класс для работы с потоком токенов в шаблонизаторе DarsiPro CMS
 *
 * @project    DarsiPro CMS
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.0
 * @php        5.6+
 */
class Viewer_TokenStream
{
    /**
     * @var array Массив токенов
     */
    protected $tokens;
    
    /**
     * @var int Текущая позиция в потоке токенов
     */
    protected $current;
    
    /**
     * @var string|null Имя файла, связанного с потоком токенов (для отладки)
     */
    protected $filename;

    /**
     * Конструктор класса
     *
     * @param array $tokens Массив объектов Viewer_Token
     * @param string|null $filename Имя файла (для отладки)
     */
    public function __construct(array $tokens, $filename = null)
    {
        $this->tokens = $tokens;
        $this->current = 0;
        $this->filename = $filename;
    }

    /**
     * Преобразует поток токенов в строку
     *
     * @return string Строковое представление потока токенов
     */
    public function __toString()
    {
        return implode("\n", $this->tokens);
    }

    /**
     * Перемещает указатель на следующий токен и возвращает текущий
     *
     * @return Viewer_Token Текущий токен перед перемещением указателя
     * @throws Exception Если достигнут конец потока токенов
     */
    public function next()
    {
        if (!isset($this->tokens[++$this->current])) {
            throw new Exception(
                'Unexpected end of template', 
                -1, 
                $this->filename
            );
        }

        return $this->tokens[$this->current - 1];
    }

    /**
     * Проверяет текущий токен на соответствие типу и значению
     *
     * @param int $type Ожидаемый тип токена (используйте константы Viewer_Token)
     * @param mixed $value Ожидаемое значение токена (опционально)
     * @param string|null $message Сообщение об ошибке (опционально)
     * @return Viewer_Token Текущий токен
     * @throws Exception Если токен не соответствует ожидаемым параметрам
     */
    public function expect($type, $value = null, $message = null)
    {
        $token = $this->tokens[$this->current];
        
        if (!$token->test($type, $value)) {
            $line = $token->getLine();
            throw new Exception(
                sprintf(
                    '%s Unexpected token "%s" of value "%s" ("%s" expected%s)',
                    $message ? $message.'. ' : '',
                    Viewer_Token::typeToString($token->getType()),
                    $token->getValue(),
                    Viewer_Token::typeToString($type),
                    $value ? sprintf(' with value "%s"', $value) : ''
                ),
                $line
            );
        }
        
        $this->next();
        return $token;
    }

    /**
     * Просматривает токен на указанной позиции относительно текущей
     *
     * @param int $number Смещение относительно текущей позиции (по умолчанию 1)
     * @return Viewer_Token Токен на указанной позиции
     * @throws Exception Если достигнут конец потока токенов
     */
    public function look($number = 1)
    {
        if (!isset($this->tokens[$this->current + $number])) {
            throw new Exception(
                'Unexpected end of template', 
                -1, 
                $this->filename
            );
        }

        return $this->tokens[$this->current + $number];
    }

    /**
     * Проверяет текущий токен на соответствие типу и значению
     *
     * @param int $primary Ожидаемый тип токена
     * @param mixed $secondary Ожидаемое значение токена (опционально)
     * @return bool Результат проверки
     */
    public function test($primary, $secondary = null)
    {
        return $this->tokens[$this->current]->test($primary, $secondary);
    }

    /**
     * Проверяет, достигнут ли конец потока токенов
     *
     * @return bool True если достигнут конец потока, иначе false
     */
    public function isEOF()
    {
        return $this->tokens[$this->current]->getType() === Viewer_Token::EOF_TYPE;
    }

    /**
     * Возвращает текущий токен
     *
     * @return Viewer_Token Текущий токен
     */
    public function getCurrent()
    {
        return $this->tokens[$this->current];
    }

    /**
     * Возвращает имя файла, связанного с потоком токенов
     *
     * @return string|null Имя файла или null, если не установлено
     */
    public function getFilename()
    {
        return $this->filename;
    }
}