<?php
/**
* Uses for read | write | clean settings
*
* @project    DarsiPro CMS
* @package    Config class
* @url        https://darsi.pro
*/

class Config {


    static $settings;
    static $LocalModSettings;


    /**
    * Set settings for later use Config class
    *
    * @param array $set - settings for save
    */
    public function __construct($path = null)
    {
        if ($path) {
            if (defined('R') and file_exists(R.'data/config/__main__.php')) {
                self::$settings = array_merge(include $path, include (R.'data/config/__main__.php'));
            } else {
                self::$settings = include $path;
            }
            self::$LocalModSettings = array();
        }
    }


    /**
    * writing settings
    *
    * @param array $set - settings for save
    */
    static function write($set, $module = null) {
        if ($module === null) {
            $path = R.'data/config/__main__.php';
        } else {
            $path = R.'data/config/'.$module.'.php';
        }

        if ($fopen=@fopen($path, 'w')) {
            $data = '<?php ' . "\n" . 'return ' . var_export($set, true) . "\n" . '?>';
            fputs($fopen, $data);
            fclose($fopen);
			if ($module === null) {
                self::$settings = $set;
            } else {
                self::$LocalModSettings[$module] = $set;
            }
            return true;
        }
        return false;
    }


    /**
    * read settings
    * Examples:
    * Config::read(param, module)
    * Config::read(module.param.param2.paramN)
    *
    * @param string $title - title of setting
    * @param string $module - parent module of setting
    */
    static function read($title, $module = null) {
        $set = self::$settings;
        if ($title == 'all') {
            if (!empty($module) && !in_array($module,$set['system_modules'])) {
                $set = self::getLocalConfig($module);
            }
            return $set;
        }
        // Если указан модуль и этот модуль системный, то просто возвращаем значение
        if (!empty($module) && in_array($module,$set['system_modules'])) {
            if (isset($set[$module][$title])) return $set[$module][$title];
        } else {
            // Если модуль указан и модуль не системный
            if (!empty($module)) {
                return self::__find($set, array($module,$title));
            // Если модуль указан другим способом
            } elseif (false !== strpos($title, '.')) {
                $params = explode('.', $title);
                return self::__find($set, $params);
            // Если не указан, значит это глобальная настройка
            } else {
                if (isset($set[$title])) return $set[$title];
            }
        }
        return null;
    }



    /**
     * Find value in global config
     *
     * @Recursive
     * @param array $conf
     * @param array $params
     * @param bool $check_sys_mod - определяет, следует ли проводить проверку, системный это модуль или нет.
     */
    static private function __find($conf, $params, $check_sys_mod = true) {

        $first_param = array_shift($params);

        // Если модуль не системный, то продолжаем поиск не в глобальном конфиге, а в конфиге внешнего модуля
        if ($check_sys_mod && isset($conf['system_modules']) && !in_array($first_param,$conf['system_modules'])) {
            $conf = self::getLocalConfig($first_param);
            return self::__find($conf, $params, false);
        }

        if (!isset($conf[$first_param])) return null;

        // last key - only return value
        if (count($params) == 0)
            return $conf[$first_param];

        // not last key - one more iteration
        return self::__find($conf[$first_param], $params, $check_sys_mod);
    }

    // Возвращает данные из конфига модуля, указанного в аргументе.
    static function getLocalConfig($module) {
        // Если мы уже вызывали данные этого конфига
        if (isset(self::$LocalModSettings[$module]) && is_array(self::$LocalModSettings[$module]))
            return self::$LocalModSettings[$module];
        // Первый раз встретились с данными из этого конфига
        $path_data = R.'data/config/'.$module.'.php';
        $path_module = R.'modules/'.$module.'/config.php';
        $conf = array();

        if (file_exists($path_data) and file_exists($path_module)) {
            $conf = array_merge(include $path_module, include ($path_data));
        } else if (file_exists($path_module)) {
            $conf = include $path_module;
        }

        self::$LocalModSettings[$module] = $conf;
        return $conf;
    }

}
