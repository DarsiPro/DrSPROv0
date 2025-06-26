<?php
/**
* @project    DarsiPro CMS
* @package    Plugins Class
* @url        https://darsi.pro
*/


class Plugins {
    
    /**
    * @map Plugins configs storage
    *
    * @var array(
    *     event_name => array(
    *         config1,
    *         config2,
    *         ...
    *     ),
    *     event_name2 => ...
    *     ...
    * )
    *
    */
    static $map = null;
    
    private $errors = '';
    private $files = array();
    

    public function __construct() {
        
    }
/**
    * Create plugins configs storage
    *
    * @return void
    */
    static function create_map() {
        $dirs = glob(ROOT . '/plugins/*');
        
        self::$map = array();
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                if (!is_dir($dir)) continue;

                if (file_exists($dir . '/config.json'))
                    $config = json_decode(file_get_contents($dir . '/config.json'), true);
                elseif (file_exists($dir . '/config.php'))
                    $config = include($dir . '/config.php');
                else
                    continue;
                
                if (isset($config)) {
                    if (!isset($config['active']) || !$config['active'] || !isset($config['points']) || empty($config['points']))
                        continue;
                    
                    $config['__dir__'] = $dir;
                    
                    if (is_string($config['points']))
                        $config['points'] = array($config['points']);
                    
                    foreach ($config['points'] as $point) {
                        if (empty(self::$map[$point]))
                        self::$map[$point] = array();
                        
                        unset($config['active']);
                        unset($config['points']);
                        
                        self::$map[$point][] = $config;
                    }
                }
            }
        }
    }
    
    /**
     * Find plugin by key and launch his
     *
     * @param string $key
     * @param mixed $params
     */
    static function intercept($key, $params = null, $etc = false) {
        if (self::$map === null)
            self::create_map();
        
        if (isset(self::$map[$key]) && !empty(self::$map[$key])) {
            foreach (self::$map[$key] as $pl_conf) {
                if (!is_dir($pl_conf['__dir__'])) continue;

                // Если у плагина есть индивидуальная настройка загрузки,
                // то нужно еще выяснить, загружать или нет
                if (!empty($pl_conf['action'])) {
                    $Register = Register::getInstance();
                    $actions = $Register['params'];
                    $pl_actions = $pl_conf['action'];
                    for ($i=0;$i <= count($actions);$i++) {
                        if (!empty($actions[$i])) {
                            // Если есть поле allow, то загружаем плагин только,
                            // если текущий экшен есть в списке разрешенных
                            if (!empty($pl_actions['allow'])) {
                                if (
                                    // Запрещаем, если для текущего экшена нет настройки
                                    isset($pl_actions['allow'][$i]) 
                                    // Разрешаем, если текущий экшен указан в разрешенных экшенах плагина
                                    and !in_array($actions[$i],$pl_actions['allow'][$i]) 
                                )
                                    continue 2;
                            }
                            // Если есть поле disallow, то включаем плагин только,
                            // если его нет в списке запрещенных экшенов(disalow)
                            if (!empty($pl_actions['disallow'])) {
                                if (
                                    // Запрещаем, если для текущего экшена нет настройки
                                    isset($pl_actions['disallow'][$i]) 
                                    // Запрещаем, если текущий экшен указан в запрещенных экшенах плагина
                                    and in_array($actions[$i],$pl_actions['disallow'][$i]) 
                                )
                                    continue 2;
                            }
                        }
                    }
                }
                
                // Если класс плагина еще не проинициализирован
                if (!isset($pl_conf['__instance__'])) {
                    include_once $pl_conf['__dir__'] . '/index.php';

                    $plugin_basename = basename($pl_conf['__dir__']);
                    if (isset($pl_conf['className']))
                        $className = $pl_conf['className'];
                    else
                        $className = $plugin_basename;

                    if (!class_exists($className)) {
                        throw new Exception("Undefined classname '$className' in '$plugin_basename' plugin.");
                        continue;
                    }
                
                    $pl_conf['__instance__'] = new $className($params,$etc);
                    $pl_conf['__instance__']->plugin_path = get_url('/plugins/' . $plugin_basename);
                }
                
                $params = $pl_conf['__instance__']->common($params, $key, $etc);

                // Processign tag {{ plugin_path }} (Для совместимости со старыми плагинами)
                if (is_string($params) && mb_strpos($params, 'plugin_path') !== false) {
                    $path = get_url('/plugins/' . $plugin_basename);
                    $params = preg_replace('#{{\s*plugin_path\s*}}#i', $path, $params);
                }
            }
        }
        return $params;
    }


    public function getErrors() {
        return $this->errors;
    }

    public function getFiles() {
        return $this->files;
    }

    public function install($filename, $dir = False) {
        $src = ROOT . '/sys/tmp/' . $filename;
        $dest = ROOT . '/sys/tmp/install_plugin/';

        _unlink($dest);
        Zip::extractZip($src, $dest);
        if (!file_exists($dest)) {
            $this->errors = __('Some error occurred');
            return false;
        }

        $tmp_plugin_path = glob($dest . '*', GLOB_ONLYDIR);
        $tmp_plugin_path = $tmp_plugin_path[0];
        if ($dir)
            $plugin_basename = $dir;
        else
            $plugin_basename = substr(strrchr($tmp_plugin_path, '/'), 1);
        $plugin_path = ROOT . '/plugins/' . $plugin_basename;


        copyr($tmp_plugin_path, $plugin_path);
        $this->files = getDirFiles($plugin_path);


        $conf_pach = $plugin_path . '/config.json';
        if (file_exists($conf_pach)) {
            $config = json_decode(file_get_contents($conf_pach), true);


            include_once $plugin_path . '/index.php';
            if (isset($config['className']))
                $className = $config['className'];
            else
                $className = $plugin_basename;

            if (!class_exists($className))
                return false;

            $obj = new $className(null);

            if (method_exists($obj, 'install')) {
                if ($obj->install() == False) {
                    $this->errors = __('Some error occurred');
                    return false;
                }
            }

            $config['active'] = 1;
            file_put_contents($conf_pach, json_encode($config, JSON_UNESCAPED_UNICODE));
        }

        _unlink($src);
        _unlink($dest);

        return true;
    }

    public function foreignUpload($url) {
        $headers = get_headers($url,1);
        if (!empty($headers['Content-Disposition']))
            preg_match('#filename="(.*)"#iU', $headers['Content-Disposition'], $matches);
        $filename = (isset($matches[1])) ? $matches[1] : basename($url);

        if (copy($url, ROOT . '/sys/tmp/'.$filename)) {
            return $filename;
        } else {
            $this->errors = __('Some error occurred');
            return false;
        }
    }

    public function localUpload($field) {
        if (empty($_FILES[$field]['name'])) {
            $this->errors = __('File not found');
            return false;
        }

        $ext = strrchr($_FILES[$field]['name'], '.');
        if (strtolower($ext) !== '.zip') {
            $this->errors = sprintf(__('Wrong file format'),$_FILES[$field]['name']);
            return false;
        }

        $filename = $_FILES[$field]['name'];

        if (move_uploaded_file($_FILES[$field]['tmp_name'], ROOT . '/sys/tmp/'.$filename)) {
            return $filename;
        } else {
            $this->errors = __('Some error occurred');
            return false;
        }
    }
}