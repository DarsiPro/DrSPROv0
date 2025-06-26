<?php
/**
* @project    DarsiPro CMS
* @package    ACL library
* @url        https://darsi.pro
*/

//rules and groups files

class ACL {

    static private $rules = array();
    static private $rules_path;
    static private $rules_data_path;
    static private $groups = array();
    static private $groups_path;
    static private $modulesRules = array();
    static private $CustomFilesRules = array();


    public function __construct() {

        self::$rules_path = R.'sys/settings/acl_rules.php';
        self::$rules_data_path = R.'data/acl/__main__.php';
        if (file_exists(self::$rules_data_path)) {
            self::$rules = array_merge_recursive2(
                include_once (self::$rules_path),
                include_once (self::$rules_data_path)
            );
        } else {
            self::$rules = include_once(self::$rules_path);
        }

        self::$groups_path = R.'sys/settings/acl_groups.php';
        self::$groups = include_once(self::$groups_path);

        self::$modulesRules = array();
        self::$CustomFilesRules = array();
    }

    // Запускает проверку прав turn для индивидуальных прав юзера и для прав его группы(последний ключ не нужно ровнять group или users)
    // id группы и id юзера берутся из сессии текущего юзера, но можно указать и другие во втором параметре-массиве: array($group,$us_id)
    static function turnUser($params,$redirect = false,$ids = null) {
        $params_group = $params;
        $params_group[] = 'groups';
        $params_user = $params;
        $params_user[] = 'users';
        if ($ids === null || !is_array($ids) || count($ids) !== 2)
            $ids = array(null,null);
        if (self::turn($params_group, false, $ids[0]) || self::turn($params_user, false, $ids[1]))
            return self::returnResult(true,$redirect);
        return self::returnResult(false,$redirect);
    }

    /*
     * Проверяет есть ли доступ(права модуля и основые права)
     *
     * @$params - массив поиска правила доступа. первый элемент с именем модуля, второй и т.д. цепочка ключей до нужного права.
     * @$redirect - определяет делать ли редирект на страницу с оповещением о недоступности ресурса пользователю, или просто возвращать результат
     * @$id - принимает id юзера или его группы(в зависимости от значения последнего элемента массива $params), права котрого следует проверить. Если не указывать, то подставляется группа/id текущего юзера.
     *
     примеры:
         $params = array('news','view_materials','groups')
         $id = $user_group or $user_id or $some_id
    */
    static function turn($params, $redirect = false, $id = null) {

        //return true;
        if (!isset($params) || !is_array($params) || !isset($params[0]))
            return self::returnResult(false,$redirect);

        // Облегчаем жизнь разработчикам(получаем сами недостающие данные)
        // Если не указано(ы) значение(я) поиска
        if ($id === null) {
            $end = end($params);
            switch ($end) {
                // Если последние звено параметров группа, то подставляем id группы текущего юзера
                case 'groups':
                    $id = (isset($_SESSION['user']['name'])) ? $_SESSION['user']['status'] : 0;
                    break;
                // Если последнее звено юзеры, то подставляем id текущего юзера
                case 'users':
                    $id = (isset($_SESSION['user']['name'])) ? $_SESSION['user']['id'] : 0;
                    break;
                default:
                    return self::returnResult(false,$redirect);
                    break;

            }
        }

        // Если данные не корректные - ложь
        if (!is_numeric($id))
            return self::returnResult(false,$redirect);

        $id = (int)$id;

        // Если это глобальные права
        if (array_key_exists($params[0],self::$rules))
            return self::returnResult(self::findRule($params,self::$rules,$id),$redirect);
        // Если права в модуле
        else {
            $module = array_shift($params);
            return self::returnResult(self::findRule($params,self::getModuleRules($module),$id),$redirect);
        }

    }


    // Возвращает результат в отдельном окне или как результат функции, в зависимости от значения второго парметра
    static function returnResult($access,$redirect) {
        if ((empty($access) || $access === false) && $redirect) {
            http_response_code(403);
            include_once R.'sys/inc/error.php';
            die();
        } else
            return $access;
    }


    // Ищет право юзера в массиве прав
    static function findRule($params,$rules,$id) {

        if (!is_array($rules)) return false;

        // Получение списка id с разрешением
        $allowIds = $rules;
        foreach($params as $key) {
            if (isset($allowIds[$key]))
                $allowIds = $allowIds[$key];
            else
                return false;
        }

        // Проверка на вхождение нашего $id в список полученных id
        if (is_array($allowIds) && in_array($id,$allowIds))
            return true;
        else
            return false;

    }

    // Возвращает содержимое файла с правами модуля
    static function getModuleRules($module) {
        // Проверка на повторное получение одних и техже данных
        if (isset(self::$modulesRules[$module]))
            return self::$modulesRules[$module];
        else {
            $path_data = R.'data/acl/'.$module.'.php';
            $path_module = R.'modules/'.$module.'/acl_rules.php';
            if (file_exists($path_data) and file_exists($path_module)) {
                $data = array_merge(include $path_module, include $path_data);
            } else if (file_exists($path_module)) {
                $data = include_once($path_module);
            } else {
                return false;
            }
            if (!is_array($data)) return false;
            self::$modulesRules[$module] = $data;
            return $data;
        }
    }


    /**
    * Функция компактного сохранения прав доступа.
    */
    static function save_rules($rules,$path = '/data/acl/__main__.php') {
        if ($fopen = fopen(ROOT . $path, 'w')) {

            // Получаем строчное представление массива
            $data = var_export($rules, true);
            // Убираем перенос перед array (
            $data = preg_replace('#=>[\s]+array \(#is', '=> array(', $data);
            // Убираем числовые индексы
            $data = preg_replace('#[\s]*\d+ => (\d+,)[\s]*#is', '\\1', $data);
            // Убираем лишний пробел внутри array()
            $data = preg_replace('#array\([\s]+\)#is', 'array()', $data);

            // Записываем в файл
            fputs($fopen, '<?php ' . "\n" . 'return ' . $data . "\n" . '?>');
            fclose($fopen);
            return true;
        } else {
            return false;
        }
    }

    // Функция компактного сохранения всех прав доступа(массива прав модулей как внешних так и системных)
    static function save_rules_full($rules, $errors = array()) {
        
        foreach ($rules as $module => $rules) {
            if (array_key_exists($module,self::$rules))
                // Для системных модулей изменяем массив текущих прав(системных)
                self::$rules[$module] = $rules;
            else
                // Для внешних сразу записываем в файл
                if (!self::save_rules($rules,'/data/acl/'.$module.'.php')) {
                    $errors[] = sprintf(__('Error saving group rights'), __($module,false,$module));
                }
        }
        // Сохраняем изменные права системных модулей
        if (!self::save_rules(self::$rules)) {
            $errors[] = sprintf(__('Error saving group rights'), __('General rights'));
        }
        
        return $errors;
    }

    // Получает список групп(массив)
    static function get_group_info() {
        return self::$groups;
    }

    // Получает полный список прав доступа
    static function getRules()
    {
        return array_merge(self::$rules,self::getAllModulesRules());
    }

    // Получает системные права доступа
    static function getSysRules()
    {
        return self::$rules;
    }

    // Получает права доступа для всех установленных модулей
    static function getAllModulesRules()
    {
        $modules = Config::read('installed_modules');
        $rules = array();
        foreach($modules as $module) {
            $m_rules = self::getModuleRules($module);
            if (empty($m_rules) || !is_array($m_rules)) continue;
            $rules[$module] = $m_rules;
        }
        return $rules;
    }

    // Для файлов не стандартного расположения и применения
    static function turnInFile($file_path,$params,$id,$redirect = false) {

        if (!isset($params) || !is_array($params) || !isset($params[0]) || !is_numeric($id) || !is_string($file_path) || !file_exists($file_path))
            return self::returnResult(false,$redirect);

        // Проверка на повторное получение одних и техже данных
        if (isset(self::$CustomFilesRules[$file_path]))
            $rules = self::$CustomFilesRules[$file_path];
        else {
            $rules = include_once($file_path);
            if (!is_array($rules)) return self::returnResult(false,$redirect);
            self::$CustomFilesRules[$file_path] = $rules;
            return $rules;
        }

        return self::returnResult(self::findRule($params,$rules,$id),$redirect);
    }


    // Сохраняет список групп
    static function save_groups($groups) {
        return save_export_file($groups,self::$groups_path);
    }

    // Сохраняет или создает группу, безопасно
    static function save_group($id,$title = null,$color = null,$other = null) {

        if (!is_numeric($id))
            return false;

        if (!isset(self::$groups[$id]))
            self::$groups[$id] = array();
        if (!empty($title))
            self::$groups[$id]['title'] = $title; // Пройтись валидатором
        if (!empty($color))
            self::$groups[$id]['color'] = $color; // Пройтись валидатором

        if (!empty($other) && is_array($other) && !array_key_exists('title',$other) && !array_key_exists('color',$other))
            self::$groups[$id][] = $other;


        return save_export_file(self::$groups,self::$groups_path);
    }

    // Удаляет группу
    static function delete_group($id) {
        if (!is_numeric($id)) return false;
        unset(self::$groups[$id]);
        return save_export_file(self::$groups,self::$groups_path);
    }

    // Получает данные о группе
    static function get_group($id) {
        if (!empty(self::$groups[$id]))
            return self::$groups[$id];
        return false;
    }

    // Получает удобный для вывода массив групп
    static function getGroups()
    {
        $out = array();
        foreach (self::$groups as $k => $v) {
            $out[$k] = $v;
            $out[$k]['id'] = $k;
        }
        return $out;
    }

    // Ищет в строке(массив тоже можно) $list число $uid(по умолчанию равно id группы текущего юзера), значения в которой перечислены через $sep(по умолчанию запятая).
    static function checkAccessInList($list,$uid = false,$sep = ',') {

        // Проверка типов
        if (empty($list) || !is_string($list) && !is_array($list) || !is_string($sep))
            return false;
        // Если передана строка - формируем массив
        if (is_string($list))
            $list = explode($sep, $list);
        // Проверка на пустой массив
        if (count($list) < 1)
            return false;
        // Если значение для поиска не передано - заполняем его id группы текущего юзера
        if ($uid === false)
            $uid = (!empty($_SESSION['user']['status'])) ? intval($_SESSION['user']['status']) : 0;

        // Поиск значения и результат поиска
        return in_array($uid,$list);
    }

}



new ACL();