<?php
/**
* @project    DarsiPro CMS
* @package    DrsPdo Wrapper class
* @url        https://darsi.pro
*/

class DrsPdo extends PDO {
    
    public function __construct($dsn, $user = null, $pass = null, $options = array()) {
        $options = array_merge(array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ), $options);
        
        parent::__construct($dsn, $user, $pass, $options);
        
        parent::query("SET GLOBAL time_zone = '+00:00';");
        parent::query("SET @@session.time_zone = '+00:00';");
    }
    
    
    /*
     * Экранирует название таблицы или поля
     * 
     * @param string $name
     * @return string
    */
    private function escapeName($name) {
        $name = explode('.',$name);
        
        $out = "`".str_replace("`","``",array_shift($name))."`";
        
        foreach($name as $part_name)
            $out .= ".`".str_replace("`","``",$name)."`";
        
        return $out;
    }
    
    /*
     * Является своеобразным препроцессорм, расширяющим возможности подготавливаемых PDO запросов
     * А именно упрощает использование типизованных "плейсхолдеров":
     * 
     * - ?s ("string") - строки (а также DATE, FLOAT и DECIMAL). 
     * - ?i ("integer") - целые числа. 
     * - ?b ("boolean") - булев тип данных. 
     * - ?n ("name") - имена полей и таблиц 
     * - ?p ("parsed") - для вставки уже обработанных частей запроса
     * - ?a ("array") - набор значений для IN (строка вида 'a','b','c')
     * - ?u ("update") - набор значений для SET (строка вида `field`='value',`field`='value')
     * 
     * Метод работает таким образом, что адаптирует указанные плейсхолдеры и соответствующие им значения
     * в поддерживаемые стандартным драйвером PDO. Поэтому при использовании типизованных плейсхолдеров
     * не желательно использовать явное привязывание плейсхолдера через bindValue/bindParam
     * В случае с именованными плейсхолдерами типов ?i,?b,?s произойдет просто переназначение значения,
     * в остальных случаях произойдет ошибка
     * 
     * @param string $query - строка запроса
     * @param array $args - аргументы запроса(плейсхолдеры)
     * @param array $driver_options -  значения атрибутов объекта PDOStatement, который будет возвращен
     * @return PDOStatement
    */
    public function prepare_improved($query, $args=array(), $driver_options=array()) {
        
        $params = array();
        $split_query = preg_split('~(\?[nsiuapb]{1}(\:[\w]+)?)~u',$query,null,PREG_SPLIT_DELIM_CAPTURE);

        $new_query = '';
        
        // Номер элемента в выходном массиве(тот, что в PDO уходит)
        $n = 1;
        // Разница между номером аргумента во входном массиве аргументов
        // и выходном массиве
        $d = -1;
        // Флаг, говорящий пропускать ли следущий элемент входного запроса
        $skip = false;
        
        $named_args = array();
        
        foreach($split_query as $haystack) {
            if ($skip) {
                $skip = false;
                continue;
            }
            if (!empty($haystack) && $haystack[0] == '?') {
                $type = substr($haystack,1,1);
                $name = substr($haystack,2);
                
                if (empty($name)) {
                    $haystack = '?';
                    $place = $n+$d; // $args position
                } else {
                    $haystack = $place = $name;
                    $skip = true;
                }
                
                switch($type) {
                    case 'n':
                        // Пропускаем элемент во входном массиве
                        // (если аргумент именованный, то его позиция не играет роли)
                        if (empty($name)) $d++; 
                        
                        $haystack = $this->escapeName($args[$place]);
                        $new_query .= $haystack;
                        continue 2;
                        break;
                    case 'p':
                        // Пропускаем элемент во входном массиве
                        // (если аргумент именованный, то его позиция не играет роли)
                        if (empty($name)) $d++; 
                        $new_query .= $args[$place];
                        continue 2;
                        break;
                    case 'a':
                        // Пропускаем элемент во входном массиве
                        // (если аргумент именованный, то его позиция не играет роли)
                        if (empty($name)) $d++; 
                        
                        // Подменяем маркер массива, на несколько одиночных маркеров
                        $haystack = '';
                        foreach($args[$place] as $k) {
                            $haystack .= '?, ';
                            $params[] = array(
                                "type" => (is_int($k)) ? PDO::PARAM_INT : PDO::PARAM_STR,
                                "var" => $k,
                                "id" => $n++
                            );
                            $d--; 
                        }
                        // Убираем лишнюю запятую
                        $haystack = substr($haystack, 0, -2);
                        
                        $new_query .= $haystack;
                        continue 2;
                        break;
                    case 'u': 
                        // Пропускаем элемент во входном массиве
                        // (если аргумент именованный, то его позиция не играет роли)
                        if (empty($name)) $d++; 
                        
                        // Подменяем плейсхолдер массива, на несколько одиночных плейсхолдеров
                        $haystack = '';
                        foreach($args[$place] as $k => $v) {
                            $haystack .= $this->escapeName($k).' = ?, ';
                            $params[] = array(
                                "type" => (is_int($v)) ? PDO::PARAM_INT : PDO::PARAM_STR,
                                "var" => $v,
                                "id" => $n++
                            );
                            $d--; 
                        }
                        // Убираем лишнюю запятую
                        $haystack = substr($haystack, 0, -2);
                        
                        $new_query .= $haystack;
                        continue 2;
                        break;
                    case 'i': $type = PDO::PARAM_INT; break;
                    case 'b': $type = PDO::PARAM_BOOL; break;
                    case 's':
                    default:
                        $type = PDO::PARAM_STR;
                }
                
                
                if (empty($name)) {
                    $params[] = array(
                        "type" => $type,
                        "var" => $args[$place],
                        "id" => $n++
                    );
                } elseif (!in_array($name, $named_args)) {
                    $params[] = array(
                        "type" => $type,
                        "var" => $args[$place],
                        "id" => ($named_args[] = $name)
                    );
                }
                
            }
            $new_query .= $haystack;
        }
        
        
        $sth = parent::prepare($new_query, $driver_options);
        foreach($params as $param) {
            $sth->bindValue($param["id"], $param["var"], $param["type"]);
        }
        return $sth;
    }
    
    /*
     * Выполняет запрос, с поддержкой типизованных плейсхолдеров.
     * 
     * @param string $query - строка запроса
     * @param array $args - аргументы запроса(значения плейсхолдеров)
     * @param array $fetch_style - параметры выборки
     * @param array $driver_options -  значения атрибутов объекта PDOStatement
     * @return array - одна запись из БД (field=>value)
    */
    public function getRow($query, $args=array(), $fetch_style=null, $driver_options=array()) {
        $sth = $this->prepare_improved($query, $args, $driver_options);
        $sth->execute();
        
        return $sth->fetch($fetch_style);
    }
    
    /*
     * Выполняет запрос, с поддержкой типизованных плейсхолдеров.
     * 
     * @param string $query - строка запроса
     * @param array $args - аргументы запроса(значения плейсхолдеров)
     * @param array $fetch_style - параметры выборки
     * @param array $driver_options -  значения атрибутов объекта PDOStatement
     * @return array - многомерный массив записей из БД (field=>value)
    */
    public function getAll($query, $args=array(), $fetch_style=null, $driver_options=array()) {
        $sth = $this->prepare_improved($query, $args, $driver_options);
        $sth->execute();
        
        return $sth->fetchAll($fetch_style);
    }
}

