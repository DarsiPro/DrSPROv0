<?php
/**
 * Компилятор шаблонов для DarsiPro CMS
 * 
 * Отвечает за преобразование AST (абстрактного синтаксического дерева) 
 * в исполняемый PHP-код
 * 
 * @project    DarsiPro CMS
 * @package    VpsViewer
 * @url        https://darsi.pro
 * @version    1.0
 * @author     Петров Евгений <email@mail.ru>
 */
class Viewer_CompileParser
{
    /**
     * Узлы AST для компиляции
     * @var Viewer_NodeTree
     */
    protected $nodes;

    /**
     * Сгенерированный PHP-код
     * @var string
     */
    private $output = '';

    /**
     * Текущий уровень отступа
     * @var int
     */
    private $indent = 3;

    /**
     * Имя временного класса для шаблона
     * @var string
     */
    private $tmpClassName = 'ViewerTemplate';

    /**
     * Загрузчик шаблонов
     * @var Viewer_Loader
     */
    public $loader;

    /**
     * Конструктор класса
     * @param Viewer_Loader $loader Объект загрузчика
     */
    public function __construct(Viewer_Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Очистка сгенерированного кода
     */
    public function clean()
    {
        $this->output = '';
    }

    /**
     * Получение сгенерированного кода
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Получение имени временного класса
     * @return string
     */
    protected function getTmpClassName()
    {
        return $this->tmpClassName;
    }

    /**
     * Установка имени временного класса
     * @param string $className
     */
    public function setTmpClassName($className)
    {
        $this->tmpClassName = $className;
    }

    /**
     * Запись в выходной буфер с учетом отступов
     * @return self
     */
    public function write()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            $this->addIndent();
            $this->output .= $arg;
        }
        return $this;
    }

    /**
     * Увеличение отступа
     * @param int $step Шаг увеличения
     * @return self
     */
    public function indent($step = 1)
    {
        $this->indent += (int)$step;
        return $this;
    }

    /**
     * Уменьшение отступа
     * @param int $step Шаг уменьшения
     * @return self
     * @throws Twig_Error Если отступ становится отрицательным
     */
    public function outdent($step = 1)
    {
        $this->indent -= (int)$step;

        if ($this->indent < 0) {
            throw new Twig_Error('Unable to call outdent() as the indentation would become negative');
        }

        return $this;
    }

    /**
     * Добавление строкового значения
     * @param string $value Значение
     * @param bool $quoted Экранировать ли строку
     * @return self
     */
    public function string($value, $quoted = true)
    {
        if ($quoted) {
            $value = str_replace(array("\n\t", "\t"), array("\n", ""), $value);
            $this->output .= sprintf('"%s"', addcslashes($value, "\0\t\"\$\\"));
        } else {
            $this->output .= $value;
        }
        return $this;
    }

    /**
     * Добавление константы
     * @param string $value Имя константы
     * @return self
     */
    public function constant($value)
    {
        $this->output .= strtoupper($value);
        return $this;
    }

    /**
     * Добавление сырой строки без обработки
     * @param string $string
     * @return self
     */
    public function raw($string)
    {
        $this->output .= $string;
        return $this;
    }

    /**
     * Представление значения в PHP-коде
     * @param mixed $value
     * @return self
     */
    public function repr($value)
    {
        if (is_int($value) || is_float($value)) {
            $this->raw($value);
        } elseif (null === $value) {
            $this->raw('null');
        } elseif (is_bool($value)) {
            $this->raw($value ? 'true' : 'false');
        } elseif (is_array($value)) {
            $this->raw('array(');
            $i = 0;
            foreach ($value as $key => $val) {
                if ($i++) {
                    $this->raw(', ');
                }
                $this->repr($key);
                $this->raw(' => ');
                $this->repr($val);
            }
            $this->raw(')');
        } elseif (defined($value)) {
            $this->constant($value);
        } else {
            $this->string($value);
        }
        return $this;
    }

    /**
     * Добавление текущего отступа
     */
    public function addIndent()
    {
        $this->output .= str_repeat('    ', $this->indent);
    }

    /**
     * Компиляция подузла
     * @param Viewer_NodeInterface $node
     * @return self
     */
    public function subcompile($node)
    {
        $node->compile($this);
        return $this;
    }

    /**
     * Компиляция всего дерева узлов
     * @param Viewer_NodeTree $nodes
     * @return self
     */
    public function compile(Viewer_NodeTree $nodes)
    {
        $this->nodes = $nodes;
        $nodesBody = $nodes->getBody();

        if (!empty($nodesBody)) {
            foreach ($nodesBody as $node) {
                $node->compile($this);
            }
        }

        $this->finishSourceCode();
        return $this;
    }

    /**
     * Формирование итогового PHP-кода шаблона
     */
    private function finishSourceCode()
    {
        $class = $this->getTmpClassName();
        $output = $this->output;

        $this->output = sprintf(
            '<?php
if (!class_exists("%1$s")) {
    class %1$s extends Viewer_Template {
        public function display() {
%2$s
        }
    }
}
$%1$s = new %1$s($context);
$%1$s->display();',
            $class,
            $output
        );
    }
}