<?php
/**
 * @project     DarsiPro CMS
 * @package     URL class
 * @url         https://darsi.pro
 */


class DrsUrl {


    public $Register;

    function __construct($Register = false) {
        if ($Register) {
            $this->Register = $Register;
            $this->Register['DrsUrl'] = $this;

            $redirect = Config::read('redirect');
            if (!empty($redirect)) {
                header('Location: ' . Config::read('redirect') . '');
                die();
            }
            
            $url = $this->decodeUrl(trim(substr($_SERVER["REQUEST_URI"], mb_strlen(WWW_ROOT, 'utf-8')), '/'));
			$url = urldecode($url);
            $params = explode("/", $url);
            if ($params[0] == 'admin') {
                $this->parseAdmin($params);
                die();
            }
            if (count($params)>=4 && count($params)<=5 && $params[0] == 'data' && $params[1] == 'images') {
                $module = $params[2];
                if (count($params)==4) {
                    $size = false;
                    $name = $params[3];
                } else {
                    $size = $params[3];
                    $name = $params[4];
                }
                $imageObj = new DrsImg;
                $imageObj->returnImage($module, $name, $size);
                die();
            }
            $params = $this->parsePath(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
            $this->callAction($params);
        }
    }


    /**
     *
     */
    static public function parseRoutes($url)
    {
        $params = self::getRoutesRules();
        $url = explode('/', $url);
        if (!empty($params) && is_array($params)) {
            foreach ($url as $k => $u) {
                if (array_key_exists($url[$k], $params))
                    $url[$k] = $params[$url[$k]];
            }
        }
        $url = implode("/", $url);
        return $url;
    }



    /**
     *
     */
    static public function getRoutesRules()
    {
        $path = ROOT . '/sys/settings/routes.php';
        if (!file_exists($path)) return array();
        $params = include $path;
        return $params;
    }



    static public function decodeUrl($url)
    {
        $params = self::getRoutesRules();
        $url = explode('/', $url);
        if (!empty($params) && is_array($params)) {
            foreach ($url as $k => $u) {
                $surl = array_search($url[$k], $params);
                if ($surl)
                    $url[$k] = $surl;
            }
        }
        $url = implode("/", $url);
        return $url;
    }



    /**
     * @return array
     */
    function parsePath($url) {
        // Routes
        $url = (!empty($url)) ? $this->decodeUrl(substr($url, mb_strlen(WWW_ROOT, 'utf-8'))) : '';
		$url = urldecode($url);

        // Проверяем URL на лишние фрагменты
        $fixed_url = $this->checkAndRepair($_SERVER['REQUEST_URI']);
        if (!empty($url) && $_SERVER['REQUEST_METHOD'] == 'GET' && $fixed_url !== $_SERVER['REQUEST_URI'])
            redirect($fixed_url, true, 301);

        $pathParams = array();

        // Если включен start_mod
        $url = trim($url, '/');
        $start_mod = Config::read('start_mod');
        if ($start_mod) {
            if (empty($url))
                return $this->parsePath($start_mod);
            if ($url === $start_mod){
                $this->Register['is_home_page'] = true;
            }
        }

        $pathParams = explode('/', $url);

        // Получает установку языка пользователя из url
        $this->getLang($pathParams);


        // Если это главная страница, то загружаем главную страницу модуля pages
        if (empty($pathParams[0])) {
            $pathParams = array(
                'pages',
                'index',
            );
        }

        // sort array(keys begins from 0)
        $pathParams = array_map(function($r){
            return trim($r);
        }, $pathParams);


        // inserted URL for Pages module
        if (count($pathParams) >= 1 && !file_exists(ROOT . '/modules/' . $pathParams[0])) {
            $pathParams = array(
                0 => 'pages',
                1 => 'index',
                2 => implode('/', $pathParams),
            );
        }


        return $pathParams;
    }

    // Обрабатывает адреса до страниц настройки модулей из админки
    public function parseAdmin($pathParams) {

        // Если адрес состоит из трех элементов
        if (isset($pathParams[2])) {
            // Выкусываем первый элемент
            array_shift($pathParams);
            $module = array_shift($pathParams);
            // Выкусываем название модуля, установив его в url, а оставшуюся чаcть url перемещаем в каталог /admin/
            $path = R.'modules/'.$module.'/admin/'.implode('/', $pathParams);
            // Говорим всем в каком мы модуле
            $this->Register['module'] = $module;
            // "Красивые" адреса только до исполняемых на сервере файлов
            $clear_path = parse_url($path, PHP_URL_PATH);
            $ext = strrchr(end($pathParams), ".");
            if (($ext == '.php' || strchr($ext, '?', true) == '.php') && file_exists($clear_path))
                include_once $clear_path;
            // Иные файлы следует выводить напрямую, так быстрее и лучше. По этому выводим соответсвующее сообщение
            else {
                http_response_code(404);
                include_once R.'sys/inc/error.php';
                die();
            }
        }
    }


    public function getLang(&$pathParams)
    {
        $permitted_langs = getPermittedLangs();
        
        if (in_array(0, $pathParams) && count($permitted_langs) >= 1 && in_array($pathParams[0], $permitted_langs)) {
            $_SESSION['lang'] = $pathParams[0];
            unset($pathParams[0]);
            redirect('/' . implode('/' ,$pathParams), false, 301);
        }

        if (!isset($_SESSION['lang']))
            $_SESSION['lang'] = Config::read('language');

        return $pathParams;
    }


    /**
     * @param  $params
     * @return void
     */
    function callAction($params)
    {

        // Redirect from not HLU to HLU
        if (count($params) >= 3 &&  $params[1] == 'view' && Config::read('hlu') == 1) {
            $hlufile = $this->searchHluById($params[2], $params[0]);
            if ($hlufile !== false) {
                $hlutitle = strstr(basename($hlufile), '.', true);
                $hlutitle .= Config::read('hlu_extention');
                redirect('/' . $params[0] . '/' . $hlutitle, false, 301);
            }
        }

        // if we have one argument, we get page if it exists or error
        if (!is_file(ROOT . '/modules/' . strtolower($params[0]) . '/ActionsHandler.class.php')) {
            http_response_code(404);
            include_once ROOT . '/sys/inc/error.php';
            die();
        }

        // Загружаем класс-обработчик экшенов модуля
        //include_once ROOT . '/modules/' . strtolower($params[0]) . '/ActionsHandler.class.php';
        $ActionsHandler = ucfirst($params[0]) . 'Module\ActionsHandler';

        if (!class_exists($ActionsHandler))  {
            http_response_code(404);
            include_once ROOT . '/sys/inc/error.php';
            die();
        }

        // Parse two and more arguments
        if (count($params) > 1) {
            // Human Like URL
            if (Config::read('hlu_understanding') || Config::read('hlu')) {
                $mat_id = $this->getHluId($params[1], $params[0]);
                if ($mat_id) {

                    // url only /module/title.ext
                    if (count($params) > 2)  {
                        http_response_code(404);
                        include_once ROOT . '/sys/inc/error.php';
                        die();
                    }

                    $hlu_extention = Config::read('hlu_extention');
                    // Редирект с неправильных окончаний страниц
                    if (strlen($hlu_extention) != 0 and substr($params[1], -strlen($hlu_extention)) != $hlu_extention) {
                        redirect('/' . $params[0] . '/' . substr($params[1], 0, strpos($params[1], '.')) . $hlu_extention, false, 301);
                    } elseif (strlen($hlu_extention) == 0) {
                        $clean_str = substr($params[1], 0, strpos($params[1], '.'));
                        if (!empty($clean_str))
                            redirect('/' . $params[0] . '/' . $clean_str, false, 301);
                    }
                    $params[1] = 'view';
                    $params[2] = $mat_id;
                }
            }
        }

        // Инициализируем модуль
        $this->Register['dispath_params'] = $params;
        if (count($params) == 1) $params[] = 'index';
        $this->moduleActionsHandler = new $ActionsHandler($params);

        // Если модуль еще не установлен или он выключен - останавливаем инициализацию.
        if (!ModuleInstaller::checkInstallModule($params[0]) || !Config::read('active', $params[0])) {
            return $this->moduleActionsHandler->showMessage(__('This module disabled'),'/');
        }

        $this->Register['module_class'] = $this->moduleActionsHandler;

        // Parse second argument
        if (preg_match('#^_+#', $params[1])) {
            http_response_code(404);
            include_once ROOT . '/sys/inc/error.php';
            die();
            //die('Access to action ' . h($params[1]) . ' is denied');
        }
        $use_call_method = is_callable(array($ActionsHandler,'__call'));
        if (!method_exists($this->moduleActionsHandler, $params[1]) && !$use_call_method) {
                http_response_code(404);
                include_once ROOT . '/sys/inc/error.php';
                die();
                //die('Action ' . h($params[1]) . ' not found in ' . h($module) . ' Class.');
        }

        // Проверка на лишние переданные аргументы или их недостаток. Если не отключен контроль переданных аргументов.
        if (!$use_call_method && (!isset($this->moduleActionsHandler->not_control_args) || $this->moduleActionsHandler->not_control_args !== true)) {
            $refClass = new ReflectionClass($this->moduleActionsHandler);
            $refMethod = $refClass->getMethod($params[1]);
            $cnt_params = (count($params)-2);
            if ($refMethod->class == 'Module' || !($cnt_params <= $refMethod->getNumberOfParameters() && $cnt_params >= $refMethod->getNumberOfRequiredParameters())) {
                http_response_code(404);
                include_once ROOT . '/sys/inc/error.php';
                die();
            }
        }

        $params = Events::init('before_call_module', $params);
        call_user_func_array(array($this->moduleActionsHandler, $params[1]), array_slice($params, 2));
    }


    /**
     * Find relation string->id on Human Like Url
     *
     * @param string $string
     * @param string $module
     * @return int ID
     */
    private function getHluId($string, $module) {
        $clean_str = strstr($string, '.', true);

        $tmp_file = $this->getTmpFilePath($clean_str, $module);
        if (!file_exists($tmp_file) || !is_readable($tmp_file)) return false;

        $id = substr(strrchr($tmp_file, '.'), 1);
        $id = (int)$id;
        return (is_int($id)) ? $id : false;
    }


    /**
     * Возвращает корректный URL относительно корня.
     * @param $url
     * @return string
     */
    public static function checkAndRepair($url) {
        $url_params = parse_url((used_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $url);
        if (!empty($url_params['path'])) {
            
            // if path doesn't like file(has extension), add slash at the end
            $url_params['path'] = rtrim($url_params['path'], '/');
            if (!preg_match('#(\.[\w\-_]+$)|([%20|\s+]\w+$)#umi', $url_params['path']))
                $url_params['path'] .= '/';

            if (false !== (strpos($url_params['path'], '//'))) {
                $url_params['path'] = preg_replace('#/+#', '/', $url_params['path']);
            }
        }
        $url = $url_params['path']
            . ((!empty($url_params['query'])) ? '?' . $url_params['query'] : '')
            . ((!empty($url_params['fragment'])) ? '#' . $url_params['fragment'] : '');
        
        return $url;
    }


    public function getTmpFilePath($filename, $module, $id = False) {
        $tmp_file = False;
        $tmp_dir = ROOT . '/sys/tmp/hlu_' . $module . '/';
        touchDir($tmp_dir, 0777);
        if ($id !== False) {
            $tmp_file = $tmp_dir . $filename . '.' . $id;
        } else {
            $tmp_file = $this->searchHluByTitle($filename, $module);
        }
        return $tmp_file;
    }


    function searchHluByTitle($title, $module) {
        if (strlen($title) < 1) return False;

        $files = glob(ROOT.'/sys/tmp/hlu_'.$module.'/'.$title.'.*');
        if (is_array($files) and count($files))
            foreach ($files as $file)
                return $file;

        return False;
    }


    function searchHluById($id, $module) {
        $id = (int)$id;
        if ($id < 1) return False;

        $files = glob(ROOT.'/sys/tmp/hlu_'.$module.'/*.'.$id);
        if (is_array($files) and count($files))
            foreach ($files as $file)
                return $file;

        return False;
    }

    /**
     * Only create HLU URL by title
     *
     * @param stirng title
     * @return string
     */
    public function getUrlByTitle($title) {
        $title = $this->translit($title);
        $title = strtolower(preg_replace('#[^a-z0-9]#i', '_', $title));
        $hlu_extention = Config::read('hlu_extention');
        return $title . $hlu_extention;
    }



    public function getEntryUrl($matId, $matTitle, $module){
        if (empty($matId))
            trigger_error('Empty material ID', E_USER_ERROR);

        if (Config::read('hlu') != 1 || empty($matTitle)) {
            $url = '/' . $module . '/view/' . $matId;
            return $url;
        }

        // extention
        $extention = '';
        $hlu_extention = Config::read('hlu_extention');
        if (!empty($hlu_extention)) {
            $extention = $hlu_extention;
        }

        // URL pattern
        $pattern = '/' . $module . '/%s' . $extention;


        // Check tmp file with assocciations and build human like URL
        clearstatcache();

        $title = $this->translit($matTitle);
        $title = strtolower(preg_replace('#[^a-z0-9]#i', '_', $title));
        $title = preg_replace('/(_)+/', '_', $title);
        $title = preg_replace('/(_)$/', '', $title);


        // Проверка на повторяющиеся ЧПУ
        $i = 0;
        $collision = $this->searchHluByTitle($title, $module);
        while ($collision !== False) {
            $id = substr(strrchr($collision, '.'), 1);
            if ($id != $matId) {
                $i += 1;
                $collision = $this->searchHluByTitle($title.'_'.$i, $module);
            } else {
                break;
            }
        }
        if ($i>0)
            $title .= '_'.$i;

        if (!file_exists($collision))
            file_put_contents($this->getTmpFilePath($title, $module, $matId), '');
        return h(sprintf($pattern, $title));
    }


    /**
     * Translit. Convert cirilic chars to
     * latinic chars.
     *
     * @param string $str
     * @return string
     */
    public function translit($str) {
        $cirilic = array('й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з', 'х', 'ъ', 'ф', 'ы', 'в', 'а', 'п', 'р', 'о', 'л', 'д', 'ж', 'э', 'я', 'ч', 'с', 'м', 'и', 'т', 'ь', 'б', 'ю', 'ё', 'Й', 'Ц', 'У', 'К', 'Е', 'Н', 'Г', 'Ш', 'Щ', 'З', 'Х', 'Ъ', 'Ф', 'Ы', 'В', 'А', 'П', 'Р', 'О', 'Л', 'Д', 'Ж', 'Э', 'Я', 'Ч', 'С', 'М', 'И', 'Т', 'Ь', 'Б', 'Ю', 'Ё');
        $latinic = array('i', 'c', 'u', 'k', 'e', 'n', 'g', 'sh', 'sh', 'z', 'h', '', 'f', 'y', 'v', 'a', 'p', 'r', 'o', 'l', 'd', 'j', 'e', 'ya', 'ch', 's', 'm', 'i', 't', '', 'b', 'yu', 'yo', 'i', 'c', 'u', 'k', 'e', 'n', 'g', 'sh', 'sh', 'z', 'h', '', 'f', 'y', 'v', 'a', 'p', 'r', 'o', 'l', 'd', 'j', 'e', 'ya', 'ch', 's', 'm', 'i', 't', '', 'b', 'yu', 'yo');

        return str_replace($cirilic, $latinic, $str);
    }


    /**
     * Deprecated
     */
    public function check($url) {
        if (empty($url) && $url === '/') return true;
        $url_params = parse_url((used_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $url);
        if (!empty($url_params['path']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
            return (false === (strpos($url_params['path'], '//')) &&
                (preg_match('#(\.[\w\-_]+$)|([%20|\s+]\w+$)#iu', $url_params['path']) ||
                preg_match('#/[^/\?&\.\s(%20)]+/$#iu', $url_params['path'])));
        }
        return true;
    }


    /**
     * @param $url
     * @param bool $notRoot
     * @return mixed
     */
    public function __invoke($url, $notRoot = false) {
        if ($notRoot || substr($url, 0, 7) === 'http://' || substr($url, 0, 8) === 'https://') return self::parseRoutes($url);

        $url = '/' . WWW_ROOT . $url;
        $url = self::checkAndRepair($url);
        return self::parseRoutes($url);
    }
}

