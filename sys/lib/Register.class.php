<?php
/**
 * Класс Register - реализация паттерна "Реестр" (Registry)
 *
 * Предоставляет глобальное хранилище для объектов и данных в приложении.
 * Реализует интерфейсы ArrayAccess, Iterator и Countable для удобной работы.
 *
 * @project    DarsiPro CMS
 * @package    Core
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.0
 * @php        5.6+
 */

class Register implements ArrayAccess, Iterator, Countable
{
    /**
     * @var Register|null Единственный экземпляр класса (реализация Singleton)
     */
    private static $instance = null;
    
    /**
     * @var array Внутреннее хранилище данных
     */
    private $storage = array();
    
    /**
     * @var int Текущая позиция итератора
     */
    private $iteratorPosition = 0;
    
    /**
     * @var array Ключи массива для корректной работы итератора
     */
    private $iteratorKeys = array();

    /**
     * Закрытый конструктор (запрещаем прямое создание экземпляра)
     */
    private function __construct()
    {
    }

    /**
     * Получение экземпляра класса (Singleton)
     *
     * @return Register Экземпляр класса Register
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Добавление значения в реестр
     *
     * @param string|null $name Ключ (если не указан, значение будет добавлено с числовым индексом)
     * @param mixed $value Значение для сохранения
     */
    public function set($name, $value)
    {
        if (empty($name)) {
            $this->storage[] = $value;
        } else {
            $this->storage[$name] = $value;
        }
        $this->updateIteratorKeys();
    }

    /**
     * Получение значения из реестра
     *
     * @param string $name Ключ значения
     * @return mixed|null Значение или null, если ключ не существует
     */
    public function get($name)
    {
        return isset($this->storage[$name]) ? $this->storage[$name] : null;
    }

    /**
     * Получение экземпляра класса с автоматическим созданием при необходимости
     *
     * @param string $name Имя класса
     * @return object Экземпляр запрошенного класса
     * @throws Exception Если класс не существует
     */
    public static function getClass($name)
    {
        $Register = self::getInstance();
        if (!isset($Register[$name])) {
            if (!class_exists($name)) {
                throw new Exception("Class {$name} does not exist");
            }
            $Register[$name] = new $name();
        }
        return $Register[$name];
    }

    /**
     * Обновление ключей для итератора
     */
    private function updateIteratorKeys()
    {
        $this->iteratorKeys = array_keys($this->storage);
    }

    /***************************/
    /* Реализация ArrayAccess  */
    /***************************/

    /**
     * Проверка существования ключа в реестре
     *
     * @param mixed $offset Ключ для проверки
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->storage[$offset]);
    }

    /**
     * Установка значения по ключу
     *
     * @param mixed $offset Ключ
     * @param mixed $value Значение
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->storage[] = $value;
        } else {
            $this->storage[$offset] = $value;
        }
        $this->updateIteratorKeys();
    }

    /**
     * Получение значения по ключу
     *
     * @param mixed $offset Ключ
     * @return mixed|null Значение или null, если ключ не существует
     */
    public function offsetGet($offset)
    {
        return isset($this->storage[$offset]) ? $this->storage[$offset] : null;
    }

    /**
     * Удаление значения из реестра
     *
     * @param mixed $offset Ключ
     */
    public function offsetUnset($offset)
    {
        unset($this->storage[$offset]);
        $this->updateIteratorKeys();
    }

    /***************************/
    /* Реализация Iterator    */
    /***************************/

    /**
     * Получение текущего элемента итератора
     *
     * @return mixed Текущее значение
     */
    public function current()
    {
        return $this->storage[$this->iteratorKeys[$this->iteratorPosition]];
    }

    /**
     * Получение текущего ключа итератора
     *
     * @return mixed Текущий ключ
     */
    public function key()
    {
        return $this->iteratorKeys[$this->iteratorPosition];
    }

    /**
     * Переход к следующему элементу
     */
    public function next()
    {
        $this->iteratorPosition++;
    }

    /**
     * Сброс итератора
     */
    public function rewind()
    {
        $this->iteratorPosition = 0;
    }

    /**
     * Проверка валидности текущей позиции итератора
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->iteratorKeys[$this->iteratorPosition]) && 
               isset($this->storage[$this->iteratorKeys[$this->iteratorPosition]]);
    }

    /***************************/
    /* Реализация Countable   */
    /***************************/

    /**
     * Подсчет элементов в реестре
     *
     * @return int Количество элементов
     */
    public function count()
    {
        return count($this->storage);
    }
}