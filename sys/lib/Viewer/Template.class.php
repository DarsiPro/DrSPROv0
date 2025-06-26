<?php

abstract class Viewer_Template {

    // Массив(или обьект) переменных, созданных пользователем
    protected $context;

    // Массив результатов "ленивых" переменных
    // Значения этих переменных генерируются непосредственно при использовании соотв. метки
    // Этот массив нужен для того,
    // чтобы не генерировать значение этих меток при каждом вызове, а только при первом вызове
    protected $returns_closure = array();


    public function __construct($context) {
        $this->context = $context;
    }

    public function getContext() {
        return $this->context;
    }


    public function addToContext($array) {
        $this->context = array_merge($this->context, $array);
    }

    // Инклудит файл
    public function includeFile($path, array $subcontext) {
        $context = array_merge($this->context, $subcontext);
        $Viewer = new Viewer_Manager(array('root_dir' => ''));
        echo $Viewer->view($path, $context);
    }

    // Возвращает значение переданное в контекст к обрабатываемой странице
    // {{ $need }}
    protected function getValue($context, $need, $return_closure = false) {
        $out = '';
        if (is_array($context)) {
            if (array_key_exists($need, $context))
                $out = $context[$need];
            else
                $out = null;

        } else if (is_object($context)) {
            $getter = 'get' . ucfirst(strtolower($need));
            //if (null !== $var = $context->$getter()) return $var;
            $out = $context->$getter();
        }

        // Если мы имеем дело с анонимной функцией,
        // то запускаем её для получения значения,
        // само значение сохраняем в контекст,
        // чтобы не запускать функцию несколько раз.
        if (is_object($out) && is_callable($out) && !$return_closure) {
            $id = spl_object_hash($out);
            if (!array_key_exists($id,$this->returns_closure)) $this->returns_closure[$id] = $out();
            return $this->returns_closure[$id];
        } else
            return $out;

        return '';
    }

    // Метод, содержащий откомпилированный шаблон в виде php кода для вывода страницы
    abstract public function display();
}