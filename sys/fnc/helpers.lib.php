<?php
/**
* @project    DarsiPro CMS
* @package    Helpers library
* @url        https://darsi.pro
*/


/**
 * Get one or couple entities.
 *
 * @param $modelName
 * @param array or int $id
 * @param array $gets_params
 * @param array $gets_params['cache'] = $lifeTime or true(3600)
 * @return array
 * @throws Exception
 */
function fetch($modelName, $id = false, $gets_params = array(), $binded_fields = array()){
    $result = false;
    $params = array();

    $is_cache = false;
    if (isset($gets_params['cache']) && (is_int($gets_params['cache']) || $gets_params['cache'] == true)) {
        $is_cache = true;
        $Cache = new Cache;
        $Cache->cacheDir = R.'sys/cache/fetch/';
        $Cache->lifeTime = $gets_params['cache'] === true ? 3600 : $gets_params['cache'];
        unset($gets_params['cache']);
        $Cache->prefix = $modelName;

        $identifient = ($id ? $id : 'all');
        foreach($gets_params as $k => $v) {
            $identifient .= '.'.$k.'.'.(is_array($v) ? implode('.',$v) : $v);
        }
        if (!empty($binded_fields))
            $identifient .= '.'.implode($binded_fields);


        if ($Cache->check($identifient)) {
            return unserialize($Cache->read($identifient));
        }
    }

    try {
        $modelName = OrmManager::getModelName($modelName);

        if (class_exists($modelName)) {

            $model = new $modelName;

            // Формируем ID, IDS или другие значения для поиска
            if ($id !== false) {
                if (is_numeric($id)) {
                    $params['cond'] = array('id' => $id);
                } else if (is_array($id) && count($id) > 1) {
                        if (is_numeric(end($id)))
                            $params['cond'] = array('`id` IN (' . implode(',',$id) . ')');
                        else {
                            $name = array_pop($id);
                            if (count($id) > 1)
                                $params['cond'] = array('`'.$name.'` IN (' . implode(',',$id) . ')');
                            else
                                $params['cond'] = array($name => $id[0]);
                        }
                } else {
                    return array('error' => 'Bad request');
                }
            } else {
                $params['cond'] = array();
            }

            $DB_TYPE = DB_ALL;
            if (!empty($gets_params)) {
                // Устанавливаем режим
                if (isset($gets_params['type']) && in_array($gets_params['type'], array('DB_FIRST', 'DB_ALL', 'DB_COUNT')))
                    $DB_TYPE = $gets_params['type'];



                // Устанавливаем параметры
                if (isset($gets_params['sort'])) {
                    $sort = array();
                    if (is_array($gets_params['sort'])) {
                        $sort[0] = $gets_params['sort'][0];
                        $sort[1] = $gets_params['sort'][1];
                    } else {
                        $sort[0] = $gets_params['sort'];
                        $sort[1] = 'DESC';
                    }
                    if (preg_match('#^[-_0-9A-Za-z]+$#ui', $sort[0])) {
                        if (isset($sort[1]) && !in_array($sort[1],array('DESC','ASC'))) $sort[1] = 'DESC';
                        $params['order'] = '`'.$sort[0].'` '.$sort[1];
                    }
                }
                if (isset($gets_params['limit']) && is_numeric($gets_params['limit'])) $params['limit'] = $gets_params['limit'];
                if (isset($gets_params['page']) && is_numeric($gets_params['page'])) $params['page'] = $gets_params['page'];
                if (isset($gets_params['fields'])) $params['fields'] = $gets_params['fields'];
            }
            if (!empty($binded_fields) && is_array($binded_fields))
                foreach($binded_fields as $i => $field)
                    $model->bindModel($field);

            switch($DB_TYPE) {
                case DB_FIRST:
                    $params['limit'] = 1;
                    $result = $model->getCollection($params['cond'],$params);
                    break;
                case DB_COUNT:
                    $result = $model->getTotal($params);
                    break;
                case DB_ALL:
                    $result = $model->getCollection($params['cond'],$params);
                    break;
            }
        }
        if ($DB_TYPE !== DB_COUNT && $result !== false) {
            // Запускаем метод ORM::__getAPI() чтобы получить данные для API
            $entity = $result;
            $result = array();
            foreach($entity as $n => $obj) {
                if (is_object($obj)) {
                    if (method_exists($obj,'__getAPI')) {
                        $__getAPI = $obj->__getAPI();
                        if (!empty($__getAPI)) {
                            $result[$n] = $__getAPI;
                            // Если есть бинды
                            if (!empty($binded_fields) && is_array($binded_fields)) {
                                foreach($binded_fields as $i => $bfield) {
                                    $bindname = 'get'.ucfirst($bfield);
                                    $binddata = $obj->$bindname();
                                    
                                    if (empty($binddata))
                                        break;
                                    
                                    if (!is_object($binddata) && !is_array($binddata)) {
                                        return array('error' => 'Method subentity('.$bfield.') returned unallowed type.');
                                        break;
                                    }
                                    
                                    $one_element = false;
                                    if (is_object($binddata)) {
                                        $one_element = true;
                                        $binddata = array($binddata);
                                    }
                                    
                                    $allowed_bdata = array();
                                    
                                    foreach($binddata as $i => $bdata_element) {
                                        if (is_object($bdata_element) && method_exists($bdata_element, '__getAPI')) {
                                            // И если у этого бинда есть безопасный метод __getAPI()
                                            $__getAPI = $bdata_element->__getAPI();
                                            if (!empty($__getAPI)) {
                                                // И если этот метод хоть что то вернул
                                                $allowed_bdata[$i] = $__getAPI;
                                                // Удаляем пустые ключи.(к ним либо нет доступа либо они просто пусты)
                                                foreach($allowed_bdata[$i] as $field => $val) {
                                                    if (empty($val)) {
                                                        unset($allowed_bdata[$i][$field]);
                                                    }
                                                }
                                            }
                                        } else {
                                            return array('error' => 'This subentity('.$bfield.') does not supported API');
                                            break;
                                        }
                                    }
                                    
                                    if ($one_element)
                                        $allowed_bdata = $allowed_bdata[0];
                                    
                                    $result[$n][$bfield] = $allowed_bdata;
                                }
                            }
                            // Удаляем пустые ключи.(к ним либо нет доступа либо они просто пусты)
                            foreach($result[$n] as $field => $val) {
                                if (empty($val)) {
                                    unset($result[$n][$field]);
                                }
                            }
                        }
                    } else {
                        return array('error' => 'This entity does not supported API');
                        break;
                    }
                } else {
                    if (!empty($obj)) {
                        $result[$n] = $obj;
                    }
                }
            }
            // Восстанавливаем нумерацию
            $result = array_values($result);

            if ($DB_TYPE == DB_FIRST) {
                $result = $result[0];
            }
        }

    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }

    $result = (!empty($result) && $result !== false) ? $result : array();
    if ($is_cache) $Cache->write(serialize($result),$identifient,array());
    return $result;
}

/** Get DrsQB class */
function getDB() {
    return new DrsQB(getDrsPdo());
}

/** Get DrsPdo class with db settings and use singleton(Register) */
function getDrsPdo() {
    static $dbh;

    if (!isset($dbh)) {
        // Присвоить ссылку статической переменной
        $dblocation = Config::read('host', '__db__');
        $dbuser = Config::read('user', '__db__');
        $dbpasswd = Config::read('pass', '__db__');
        $dbname = Config::read('name', '__db__');
        
        $dbh = new DrsPdo("mysql:dbname=$dbname;host=$dblocation;charset=utf8", $dbuser, $dbpasswd);
    }
    
    return $dbh;
}



// Вспомогательная функция - выдает сообщение об ошибке
// и делает редирект на нужную страницу с задержкой
function showErrorMessage( $message = '', $error = '', $redirect = false, $queryString = '' ) {
    if ($redirect === true) {
        header('Refresh: ' . Config::read('redirect_delay') . '; url=' . (used_https() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . get_url($queryString));
    }
    $View = new Viewer_Manager();
    $html = $View->view('infomessagegrand.html', array(
        'data' => array(
            'message' => $message,
            'errors' => Config::read('debug_mode') ? $error : null,
            'url' => $queryString,
            'status' => 'error'
        )
    ));
    echo $html;
}

// Функция для получения содержимого GET запросов из шаблонизатора
function ReadGET($key) {
    return (isset($_GET[$key])) ? $_GET[$key] : '';
}

// Аналог range() но возвращает пригодный для for массив.
// Эта функция используется для перебора по индексу в шаблонизаторе.
function a($n, $k = null, $step = 1) {
    if ($k == null) {
        $k = $n;
        $n = 0;
    }

    return array_slice(range($n, $k, $step), 0);
}

// аналог array_merge_recursive(), но с ограниченной глубиной рекурсии - 2
function array_merge_recursive2($arr1, $arr2) {
    foreach($arr2 as $k => $v) {
        if (isset($arr1[$k]) and is_array($arr1[$k])) {
            $arr1[$k] = array_merge($arr1[$k], $arr2[$k]);
            continue;
        }
        $arr1[$k] = $arr2[$k];
    }
    
    return $arr1;
}

// Выводит текущую дату в формате, выбранном в админке. А так же помещает её в тег, который динамически обновляется под клиента.
function DrsDate($date, $format = false) {
    if (!$format) $format = Config::read('date_format');

    if ($date == '0000-00-00 00:00:00')
        return __('never');

    return '<time datetime="' . date('c', strtotime($date)) . '" data-type="' . $format . '">' . date($format, strtotime($date)) . '</time>';
}

// Выводит относительное время(которое еще не настало), по типу текущее время настало "через 20 мин." после времени $time
// Используется для вывода временной метки при группировке постов/комментариев.
function DrsOffsetDate($time) {

    $time = time() - $time;

    $formattime = '';
    // Если разница более года
    if ($time > 31556926)
        return __('after one year');
    // Если время пошло на месяцы
    else if (date('n', $time) - 1) {
        $formattime .= date('n', $time) . ' ' . __('month.') . ' ';
    // Если время пошло на дни то выводим только дни
    } else if (date('j', $time) - 1) {
        $formattime .= date('j', $time) . ' ' . __('day.') . ' ';
    // Если время пошло на часы то выводим только часы и минуты(если не ноль)
    } else if (round($time / 3600)) {
        $formattime .= round($time / 3600) . ' ' . __('hour.') . ' ';
    // Если время пошло на минуты то выводим только минуты и секунды(если не ноль)
    } else if (round($time / 60)) {
        $formattime .= round($time / 60) . ' ' . __('minute.') . ' ';
    // Если времени прошло менее минуты то выводим секунды
    } else
        $formattime .= $time . ' ' . __('sec.') . ' ';

    return __('after') . ' ' . $formattime;
}


/**
 * Function for safe and get referer
 */
function setReferer() {
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $url_args = parse_url($_SERVER['HTTP_REFERER']);
        if (!empty($url_args['host']) && !empty($url_args['path']) && $url_args['host'] == $_SERVER['SERVER_NAME']) {
            $_SESSION['redirect_to'] = get_url(
                '/' . $url_args['path']
                . (!empty($url_args['query']) ? '?'.$url_args['query'] : '')
                . (!empty($url_args['fragment']) ? '#'.$url_args['fragment'] : ''),
                true
            );
        }
    }
}
function getReferer() {
    $Register = Register::getInstance();
    if (isset($Register['__compiled_referer__']))
        return $Register['__compiled_referer__'];
    
    $redirect_to = get_url('/');

    if (isset($_SESSION['redirect_to'])) {
        $redirect_to = get_url('/' . $_SESSION['redirect_to'], true);
        unset($_SESSION['redirect_to']);

    } else if (!empty($_SERVER['HTTP_REFERER'])) {
        $url_args = parse_url($_SERVER['HTTP_REFERER']);
        if (!empty($url_args['host']) && !empty($url_args['path']) && $url_args['host'] == $_SERVER['SERVER_NAME']) {
            $redirect_to = get_url(
                '' . $url_args['path']
                . (!empty($url_args['query']) ? '?'.$url_args['query'] : '')
                . (!empty($url_args['fragment']) ? '#'.$url_args['fragment'] : ''),
                true
            );
        }
    }
    
    $Register['__compiled_referer__'] = $redirect_to;
    return $redirect_to;
}




/**
 * Check and return order param
 */
function getOrderParam($class_name) {
    $order = (!empty($_GET['order'])) ? trim($_GET['order']) : '';

    switch ($class_name) {
        case 'FotoModule\ActionsHandler':
        case 'StatModule\ActionsHandler':
        case 'NewsModule\ActionsHandler':
            $allowed_keys = array('title', 'views', 'date', 'comments');
            $default_key = 'date';
            break;
        case 'LoadsModule\ActionsHandler':
            $allowed_keys = array('title', 'views', 'date', 'comments', 'downloads');
            $default_key = 'date';
            break;
        case 'ForumModule\ActionsHandler':
            $allowed_keys = array('title', 'time', 'last_post', 'posts', 'views');
            $default_key = 'last_post';
            break;
        case 'UsersModule\ActionsHandler':
            $allowed_keys = array('puttime', 'last_visit', 'name', 'rating', 'posts', 'status', 'warnings', 'byear', 'pol', 'ban_expire');
            $default_key = 'puttime';
            break;
    }

    if (empty($order) && empty($default_key)) return false;
    else if (empty($order) && !empty($default_key)) $out = $default_key;
    else {
        if (!empty($allowed_keys) && in_array($order, $allowed_keys)) {
            $out = $order;
        } else {
            $out = $default_key;
        }
    }

    return (!empty($_GET['asc'])) ? $out . ' ASC' : $out . ' DESC';
}



/**
 * CRON simulyation
 */
function drsCron($func, $interval) {
    $cron_file = ROOT . '/sys/tmp/' . md5($func) . '_cron.dat';
    if (file_exists($cron_file)) {
        $extime = file_get_contents($cron_file);
        if (!empty($extime) && is_numeric($extime) && $extime > time()) {
            return;
        }
    }

    if (function_exists($func)) {
        file_put_contents($cron_file, (time() + intval($interval)));
        call_user_func($func);
    }
}




/**
 * Launch auto sitemap generator
 */
function createSitemap() {
    $obj = new Sitemap;
    $obj->createMap();
}




// Автоподстановка значений Host и Sitemap
function createRobots() {
    $robots = array();
    $file = file(ROOT . "/robots.txt");

    if (isset($file) and is_array($file))
        foreach ($file as $buffer)
            if (substr($buffer,0,4)!='Host' and substr($buffer,0,7)!='Sitemap')
                $robots[] = $buffer;
    $robots[] = 'Host: '.(used_https() ? 'https://' : '').$_SERVER['HTTP_HOST']."\n";
    $robots[] = 'Sitemap: '.(used_https() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/sitemap.xml';

    $str = '';
    foreach($robots as $line){
        $str .= $line;
    }
    file_put_contents(ROOT . "/robots.txt", $str);
}



/**
 * Create human like URL.
 * Get title of material and create url
 * from this title. OR create simple URL, if hlu is off.
 *
 * @param array $materila
 * @param string $module
 * @return string
 */
function entryUrl($material, $module) {
    $matId = $material->getId();
    $matTitle = $material->getTitle();

    return Register::getClass('DrsUrl')->getEntryUrl($matId, $matTitle, $module);
}

function matUrl($matId, $matTitle, $module) {
    return Register::getClass('DrsUrl')->getEntryUrl($matId, $matTitle, $module);
}




/**
 * Create captcha input field and image with
 * security code.
 * TODO
 */
function getCaptcha($name = false) {
    $kcaptcha = '/sys/inc/kcaptcha/kc.php?' . rand(rand(0, 1000), 999999);
    if (!empty($name)) $kcaptcha .= '&name=' . $name;
    $tpl = file_get_contents(ROOT . '/template/' . getTemplate() . '/html/default/captcha.html');
    return str_replace('{CAPTCHA}', $kcaptcha, $tpl);
}



/**
 * Work for language pack.
 * Open language file and return needed string.
 *
 * @param int $key
 * @param string $tpl_lang_important - приоритет в совпадающих ключах в пользу шаблона. Если равна true то приоритет такой: template > module > global иначе: module > global > template
 * @param string $module - название модуля, что бы дополнительно брать ключи из локализации модуля
 * @return string
 */

function __($key, $tpl_lang_important = false, $module = false) {
    // Получаем доступ к глобальной переменной, в неё, вероятно уже загружено все что нам нужно.
    global $SaveDataLang;

    // Получаем текущий язык
    $language = getLang();
    if (empty($language) || !is_string($language)) $language = 'rus';

    // Формируем адреса до мест, где лежат файлы локализации
    $lang_file = R.'data/languages/' . $language . '.php';
    $tpl_lang_file = R.'template/' . getTemplate() .'/languages/' . $language . '.php';

    $Register = Register::getInstance();
    // Узнаем в каком модуле была вызвана функция, если он не указан принудительно
    if (!$module)
        $module = $Register['module'];

    if (is_array($Register['params']))
        $tpl_lang_important = True;

    // Если удалось получить имя модуля, формируем адрес до файла локализации модуля
    if (!empty($module))
        $module_lang_file = R.'modules/'.$module.'/lang/' . $language . '.php';
    // Если попытки тщетны, прекращаем дальнейшие попытки
    else
        $module = false;

    // Если у модуля есть свой файл локализации и если содержимое этого файла еще не загружено, загружаем и его.
    if ($module and !isset($SaveDataLang[$module_lang_file])) {
        $module_lang = array();
        if (file_exists($module_lang_file)) {
            $module_lang = include $module_lang_file;
            $SaveDataLang[$module_lang_file] = $module_lang;
        }
    // Если файл уже загружен, то получаем все что нужно, без повторной загрузки файла.
    } else
        $module_lang = ($module && !empty($SaveDataLang[$module_lang_file]) && is_array($SaveDataLang[$module_lang_file])) ? $SaveDataLang[$module_lang_file] : array();

    // Проверка на повторный запук функции вообще. Для глобальной и локализации шаблона.
    if (!isset($SaveDataLang[$lang_file]) && !isset($SaveDataLang[$tpl_lang_file])) {
        // Если файл с глобальной локализацией не существует - возмущаемся! Если существует - загружаемся!
        if (!file_exists($lang_file)) {
            if ($language == Config::read('language'))
                throw new Exception('Main language file not found');
            else {
                $_SESSION['lang'] = Config::read('language');
                return __($key, $tpl_lang_important, $module);
            }
        }
        $lang = include $lang_file;

        // Если у шаблона есть свой файл локализации, загружаем его.
        $tpl_lang = array();
        if (file_exists($tpl_lang_file))
            $tpl_lang = include $tpl_lang_file;

        // Запоминаем данные в глобальную облась видимости(чтобы не прочитывать файлы локализации при каждом вызове функции)
        $SaveDataLang[$lang_file] = $lang;
        $SaveDataLang[$tpl_lang_file] = $tpl_lang;
    // Функция запущена повторно, все что нам нужно у нас уже есть.
    } else {
        $lang = (!empty($SaveDataLang[$lang_file]) && is_array($SaveDataLang[$lang_file])) ? $SaveDataLang[$lang_file] : array();
        $tpl_lang = (!empty($SaveDataLang[$tpl_lang_file]) && is_array($SaveDataLang[$tpl_lang_file])) ? $SaveDataLang[$tpl_lang_file] : array();
    }

    // Переводим предствленный ключ

    // Если в модуле есть такой ключ, а в шаблоне нету, то берем перевод из модуля
    if (
    $module && array_key_exists($key, $module_lang) &&
    (!array_key_exists($key, $tpl_lang) || array_key_exists($key, $tpl_lang) && $tpl_lang_important)
    )
        // Выбор ключа из локализации модуля
        return $module_lang[$key];

    // Если у шаблона есть свой файл локализации,
    // если в нем есть такойже ключ, что и в основном,
    // а так же выбор значения ключа из него приоритетен,
    // выбираем ключ из файла при шаблоне.
    elseif(
    (array_key_exists($key, $lang) && !array_key_exists($key, $tpl_lang)) || // Если такого ключа в файле при шаблоне нет, а в основном файле есть
    (array_key_exists($key, $lang) && count($tpl_lang) && array_key_exists($key, $tpl_lang) && !$tpl_lang_important) //  Если есть и там и там определяется настройкой $tpl_lang_important
    )
        // Выбор ключа из глобального файла
        return $lang[$key];

    // Если в основном файле нет, а в файле при шаблоне есть.
    // Или при совпадении ключей настройка $tpl_lang_important равна true
    elseif (count($tpl_lang) && array_key_exists($key, $tpl_lang))
        // Выбор ключа из шаблона
        return $tpl_lang[$key];

    // Перевод не обнаружен, хорошо - тренирутесь в угадывании значения по ключу)
    return $key;
}


/**
 * Get the current user language
 */
function getLang() {
    // Если админка, то сессий использовать не нужно.
    if (strpos($_SERVER['REQUEST_URI'], WWW_ROOT . '/admin') === 0)
        return Config::read('language');
    else
        return (!empty($_SESSION['lang'])) ? $_SESSION['lang'] : Config::read('language');
}


/**
 * Get the permitted languages
 */
function getPermittedLangs() {
    $langs = Config::read('permitted_languages');
    if (!empty($langs)) {
        $langs = array_filter(explode(',', $langs));
        $langs = array_map(function($n){
            return trim($n);
        }, $langs);
        return $langs;
    } else {
        $lang_files = glob(ROOT . '/data/languages/*.php');
        $langs = array();
        if (!empty($lang_files)) {
            foreach($lang_files as $lang_file) {
                $lang = substr(substr(strrchr($lang_file, '/'), 1), 0, -4);
                $langs[] = $lang;
            }
        }
    }

    return $langs;
}






/**
 * Uses for valid create HTML tag IMG
 * and fill into him correctli url.
 * When you use this function you
 * mustn't wory obout Fapos install
 * into subdri or SUBDIRS.
 * ALso if we wont change class of IMG or etc,
 * we change this only here and this changes apply
 * for evrywhere.
 *
 * @param string $url
 * @param array $params
 * @param boolean $notRoot
 * @return string HTML link
 */
function get_img($url, $params = array(), $notRoot = false) {
    $additional = '';
    if (!empty($params) && is_array($params)) {
        foreach($params as $key => $value) {
            $additional .= h($key) . '="' . h($value) . '" ';
        }
    }
    return '<img  ' . $additional . 'src="' . get_url($url, $notRoot) . '" />';
}


/**
 * Uses for valid create url.
 * When you use this function you
 * mustn't wory obout DarsiPro install
 * into subdri or SUBDIRS.
 * This function return url only from root (/)
 * But you can send $notRoot and get url from not root.
 *
 * @param string $url
 * @param boolean $notRoot
 * @return string url
 */
function get_url($url, $notRoot = false)
{
    if ($notRoot) {
        if (strpos($url, '/admin/') !== false) {
            return $url;
        } else {
            return DrsUrl::parseRoutes($url);
        }
    }

    $url = '/' . WWW_ROOT . $url;
    // May be collizions
    $url = str_replace('//', '/', $url);
    if (strpos($url, '/admin/') !== false) {
        return $url;
    } else {
        return DrsUrl::parseRoutes($url);
    }
}



/**
 * Uses for valid create HTML tag A
 * and fill into him correctli url.
 * When you use this function you
 * mustn't wory obout Fapos install
 * into subdri or SUBDIRS.
 * ALso if we wont change class of A or etc,
 * we change this only here and this changes apply
 * for evrywhere.
 *
 * @param string $ankor
 * @param string $url
 * @param array $params
 * @param boolean $notRoot
 * @param boolean $translate - пропускать ли $ancor через __($ancor)
 * @return string HTML link
 */
function get_link($ankor, $url, $params = array(), $notRoot = false, $translate = false) {
    $additional = '';
    if (!empty($params) && is_array($params)) {
        foreach($params as $key => $value) {
            $additional .= h($key) . '="' . h($value) . '" ';
        }
    }
    $link = '<a ' . $additional . 'href="' . get_url($url, $notRoot) . '">' . ($translate ? __($ankor) : $ankor) . '</a>';
    return $link;
}


/**
 * doing hard redirect
 * Send header and if header do not
 * work stop script and die. Better redirect
 * user to another page but if can't doing this
 * better stop script.
 */
function redirect($url, $notRoot = false, $header = 302) {

    $allowed_headers = array(301, 302);
    if (!in_array($header, $allowed_headers)) $header = 301;


    header('Location: ' . get_url($url, $notRoot), TRUE, $header);
    // :)
    die() or exit();
}



function createOptionsFromParams($offset, $limit, $selected = false) {
    $output = '';
    for ($i = $offset; $i <= $limit; $i++) {
        $select = ($selected !== false && $i == $selected) ? ' selected="selected"' : '';
        $output .= '<option value="' . $i . '"' . $select . '>' . $i . '</option>';
    }
    return $output;
}


/**
* print visibility param value
* @param string or array
*/
function pr($param) {
    echo '<pre>' . print_r($param, true) . '</pre>';
}


/**
* short version "htmlspecialchars()"
* @param string or array
*/
function h($param) {

    if (!is_array($param)) {
        $param = htmlspecialchars($param);
        $symbols = array(
            '&#125;' => '&amp;#125;',
            '&#123;' => '&amp;#123;',
        );
        return str_replace($symbols, array_keys($symbols), $param);
    }

    if (is_array($param)) {
        foreach ($param as $key => $value) {
            $param[$key] = h($value);
        }

        return $param;
    }

    return false;
}


/**
* @return timestamp on microseconds
*/
function getMicroTime() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}


/**
* for tests and dumps
*/
function dumpVar($var) {
    $f = fopen(ROOT . '/dump.dat', 'a+');
    fwrite($f, $var . "\n");
    fclose($f);
}


/**
 * mysql_real_escape_string copy
 */
function resc($str) {
    return getDB()->escape($str);
}



function strips(&$param) {
    if (is_array($param)) {
        foreach($param as $k=>$v) {
            strips($param[$k]);
        }
    } else {
        $param = stripslashes($param);
        //$param = utf8Filter($param);
    }
}

/**
* cut all variables that not UTF-8
*/
function utf8Filter($str) {
    if (!preg_match('#.{1}#us', $str)) return '';
    else return $str;
}



function memoryUsage($base_memory_usage) {
    printf("Bytes diff: %s<br />\n", getSimpleFileSize(memory_get_usage() - $base_memory_usage));
}


/**
* Get correct name of template for current user
*/
function getTemplate() {
    if (isset($_SESSION['user']) &&
        isset($_SESSION['user']['template']) && // Проверяем, чтобы брать имя шаблона из сессии юзера, и проверяем есть ли папка с таким шаблоном
        !empty($_SESSION['user']['template']) &&
        file_exists(ROOT . '/template/' . $_SESSION['user']['template'])) {
            $template = $_SESSION['user']['template'];
    } else {
        $template = Config::read('template');
    }

    $template = Events::init('select_template', $template);
    return $template;
}


function checkPassword($md5_password, $password) {
    $check_password = false;
    if (strpos($md5_password, '$1$') === 0 && CRYPT_MD5 == 1) {
        $check_password = (crypt($password, $md5_password) === $md5_password);
    } else {
        $check_password = (md5($password) === $md5_password);
    }
    return $check_password;
}

function md5crypt($password){
    $Register = Register::getInstance();
    if ($Register['Config']->read('use_md5_salt', 'users') == 1) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
        $salt = '';
        for($i = 0; $i < 4; $i++) {
            $salt .= $alphabet[rand(0, strlen($alphabet)-1)];
        }
        return crypt($password, '$1$' . $salt . '$');
    } else {
        return md5($password);
    }
}

function cmpText($a, $b) {
    if (is_array($a) && is_array($b) && isset($a['text']) && isset($b['text'])) {
        if ($a['text'] == $b['text']) {
            return 0;
        }
        return ($a['text'] < $b['text']) ? -1 : 1;
    } else {
        return 0;
    }
}

function checkAccess($params = null) {
    if (!empty($params) && is_array($params))
        return ACL::turnUser($params,false);

    return false;
}

// how to use in templates:
// config(['param'])
// config(['module','param'])
function config($params = null) {
    if (isset($params) && is_array($params) && count($params) >= 1 && $params[0] != '__db__') {
        $params = implode('.', $params);
        return Config::read($params);
    }
    return false;
}

function getOrderLink($params) {
    if (!$params || !is_array($params) || count($params) < 2) return '';
    $order = (!empty($_GET['order'])) ? strtolower(trim($_GET['order'])) : '';
    $new_order = strtolower($params[0]);
    $active = ($order === $new_order);
    $asc = ($active && isset($_GET['asc']));
    return '<a href="?order=' . $new_order . ($asc ? '' : '&asc=1') . '">' . $params[1] . ($active ? ' ' . ($asc ? '↑' : '↓') : '') . '</a>';
}

// обход массива и применение trim() для элементов
function atrim($old_array) {
    $new_array = array();
    foreach ($old_array as $element) {
        array_push($new_array, trim($element));
    }
    return $new_array;
}

// чтение и запись куков
function cookie($key, $value = null) {

    if (in_array($key,array('PHPSESSID','autologin','userid','password'))) return null;

    if ($value === null)
        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : '';
    else
        setcookie($key, $value);
}

// Возвращает подстроку с добавлением окончания,
// если длина подстроки не меньше длины исходной строки
// если не указывать третий аргумент, то длиной подстроки будет считаться второй аргумент,
// а начальный индекс будет равен 0
function substr_ext($str,$n,$k=null,$ext='...') {
    if (empty($k)) {
        $k=$n;
        $n=0;
    }
    if (mb_strlen($str) <= $n+$k)
        $ext = '';

    return mb_substr($str,$n,$k).$ext;
}






/**
 TODO: Functions for USERS module.
*/

/**
 * Get users that born today
 * TODO: must be in modules
 */
function getBornTodayUsers() {
    $Register = Register::getInstance();
    $DrsDB = getDB();
    $file = ROOT . '/sys/logs/today_born.dat';


    if (!file_exists($file) || (filemtime($file) + 3600) < time()) {
        $today_born = $DrsDB->select('users', DB_ALL, array(
            'cond' => array(
                "concat(`bmonth`,`bday`) ='".date("nj")."'",
            ),
        ));
        file_put_contents($file, serialize($today_born));
    } else {
        $today_born = file_get_contents($file);
        if (!empty($today_born)) $today_born = unserialize($today_born);
    }

    if (count($today_born) < 1) return array();
    return $today_born;
}
/**
 * Return count registered users.
 * Cache results.
 * TODO: must be in modules
 */
function getAllUsersCount() {

    $Cache = new Cache;
    $Cache->lifeTime = 3600;
    $Cache->prefix = 'users';

    if ($Cache->check('cnt_registered_users')) {
        $cnt = $Cache->read('cnt_registered_users');
    } else {
        $cnt = getDB()->select('users', DB_COUNT);
        $Cache->write($cnt, 'cnt_registered_users', array());
    }

    unset($Cache);
    return (!empty($cnt)) ? intval($cnt) : 0;
}

/**
 * Clean getAllUsersCount() cache
 * TODO: must be in modules
 */
function cleanAllUsersCount() {
    $Cache = new Cache;
    $Cache->lifeTime = 3600;
    $Cache->prefix = 'users';
    if ($Cache->check('cnt_registered_users')) {
        $Cache->remove('cnt_registered_users');
    }
}

/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 * TODO: must be in modules
 *
 * @param string $email The email address
 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @return String containing either just a URL or a complete image tag
 */
function getGravatar($email, $s = 120, $d = 'mm', $r = 'g') {
    $url = '//www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . ".png?s=$s&d=$d&r=$r";
    return $url;
}

function getAvatar($id_user = null, $email_user = null) {
    $def = get_url('/template/' . getTemplate() . '/img/noavatar.png');

    if (isset($id_user) && $id_user > 0) {
        if (is_file(ROOT . '/data/avatars/' . $id_user . '.jpg')) {
            return get_url('/data/avatars/' . $id_user . '.jpg');
        } else {
            if (Config::read('use_gravatar', 'users')) {
                $Cache = new Cache;
                $Cache->prefix = 'gravatar';
                $Cache->cacheDir = ROOT . '/sys/cache/users/gravatars/';
                if (!isset($email_user)) {
                    // Может быть ссылка на граватар уже есть в кеше?
                    if ($Cache->check('user_' . $id_user))
                        return $Cache->read('user_' . $id_user);

                    // Если в кеше нет, то выполняем запрос
                    $usersModel = OrmManager::getModelInstance('Users');
                    $user = $usersModel->getById($id_user);

                    if ($user)
                        $email_user = $user->getEmail();
                    else
                        return $def;
                }
                $gravatar = getGravatar($email_user);
                // И после выполнения запроса и получения ссылки, кешируем.
                $Cache->write($gravatar, 'user_' . $id_user,array());
                return $gravatar;
            } else {
                return $def;
            }
        }
    } else {
        return $def;
    }
}


/**
 * Get age from params
 *
 * @param int $y - year
 * @param int $m - month
 * @param int $d - day
 * @return int
 */
function getAge($y = 1970, $m = 1, $d = 1) {
    $y = (int)$y; $m = (int)$m; $d = (int)$d;

    if($m > date('m') || $m == date('m') && $d > date('d'))
      return (date('Y') - $y - 1);
    else
      return (date('Y') - $y);
}

function getProfileUrl($user_id, $notRoot=false) {
    return get_url('/users/info/' . $user_id . '/', $notRoot);
}


function getUserRating($posts) {

    if (!is_numeric($posts)) return array('rank' => '', 'img' => 'star0.gif');

    $params = Config::read('users.stars');
    if (!is_array($params)) return array('rank' => '', 'img' => 'star0.gif');

    if ( $posts < $params['cond1'] ) {
        $rank = $params['rat0'];
        $img = 'star0.png';
    } else if ( $posts >= $params['cond1'] and $posts < $params['cond2'] ) {
        $img = 'star1.png';
        $rank = $params['rat1'];
    } else if ( $posts >= $params['cond2'] and $posts < $params['cond3'] ) {
        $img = 'star2.png';
        $rank = $params['rat2'];
    } else if ( $posts >= $params['cond3'] and $posts < $params['cond4'] ) {
        $img = 'star3.png';
        $rank = $params['rat3'];
    } else if ( $posts >= $params['cond4'] and $posts < $params['cond5'] ) {
        $img = 'star4.png';
        $rank = $params['rat4'];
    } else if ( $posts >= $params['cond5'] and $posts < $params['cond6'] ) {
        $img = 'star5.png';
        $rank = $params['rat5'];
    } else if ( $posts >= $params['cond6'] and $posts < $params['cond7'] ) {
        $img = 'star6.png';
        $rank = $params['rat6'];
    } else if ( $posts >= $params['cond7'] and $posts < $params['cond8'] ) {
        $img = 'star7.png';
        $rank = $params['rat7'];
    } else if ( $posts >= $params['cond8'] and $posts < $params['cond9'] ) {
        $img = 'star8.png';
        $rank = $params['rat8'];
    } else if ( $posts >= $params['cond9'] and $posts < $params['cond10'] ) {
        $img = 'star9.png';
        $rank = $params['rat9'];
    } else {
        $img = 'star10.png';
        $rank = $params['rat10'];
    }
    $result = array('rank' => $rank,
                    'img' => $img);

    return $result;
}

function getUserRatingImg($posts) {
    $info = getUserRating($posts);
    return (isset($info['img']) ? $info['img'] : 'star0.gif');
}

function getUserRatingText($posts) {
    $info = getUserRating($posts);
    return (isset($info['rank']) ? $info['rank'] : '');
}

function string_f($str, $data) {
    $searchTags = array_keys($data);
    foreach ($searchTags as $k => $tag) {
        $searchTags[$k] = '%('.$tag.')';
    }
    return str_replace($searchTags,$data,$str);
}

/**
 * Используется ли HTTPS на сайте
 *
 * @return boolean
 */
function used_https() {
    return (
        (!empty($_SERVER['HTTPS']) and $_SERVER['HTTPS'] !== 'off')
        or $_SERVER['SERVER_PORT'] == 443
        or Config::read('used_https', '__secure__')
    );
}