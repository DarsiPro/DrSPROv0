<?php
/**
 * Библиотека вспомогательных функций DarsiPro CMS
 * 
 * Содержит набор функций для работы с:
 * - Данными и ORM
 * - Безопасностью и валидацией
 * - URL и маршрутизацией
 * - Локализацией и шаблонами
 * - Пользователями и аутентификацией
 * - Кэшированием и производительностью
 * - Временем и датами
 * 
 * @project    DarsiPro CMS
 * @package    Core
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.0
 * @php        5.6+
 */

## ==============================================
## Секция 1: Функции работы с базой данных и ORM
## ==============================================

/**
 * Функция для получения данных из модели с поддержкой кеширования, фильтрации и связей
 * 
 * @param string $modelName Название модели или алиас
 * @param mixed $id ID записи или массив ID (опционально)
 * @param array $gets_params Параметры выборки (сортировка, лимит и т.д.)
 * @param array $binded_fields Связанные поля для подгрузки
 * @return array Результат выборки или сообщение об ошибке
 */
function fetch($modelName, $id = false, $gets_params = array(), $binded_fields = array()) {
    $result = false;
    $params = array();
    
    // Обработка кеширования
    $is_cache = false;
    if (isset($gets_params['cache']) && (is_int($gets_params['cache']) || $gets_params['cache'] == true)) {
        $is_cache = true;
        $Cache = new Cache;
        $Cache->cacheDir = R.'sys/cache/fetch/';
        // Время жизни кеша: если true - 1 час, иначе используем переданное значение
        $Cache->lifeTime = $gets_params['cache'] === true ? 3600 : $gets_params['cache'];
        unset($gets_params['cache']); // Удаляем параметр cache из дальнейшей обработки
        $Cache->prefix = $modelName;

        // Создаем уникальный идентификатор для кеша на основе параметров
        $identifient = ($id ? (is_array($id) ? implode('.', $id) : $id) : 'all');
        foreach ($gets_params as $k => $v) {
            $identifient .= '.'.$k.'.'.(is_array($v) ? implode('.', $v) : $v);
        }
        if (!empty($binded_fields)) {
            $identifient .= '.'.implode('.', $binded_fields);
        }

        // Проверяем наличие данных в кеше
        if ($Cache->check($identifient)) {
            return unserialize($Cache->read($identifient));
        }
    }

    try {
        // Получаем полное имя модели через OrmManager
        $modelName = OrmManager::getModelName($modelName);

        if (class_exists($modelName)) {
            $model = new $modelName;

            // Обработка параметра ID
            if ($id !== false) {
                if (is_numeric($id)) {
                    // Простой случай - один числовой ID
                    $params['cond'] = array('id' => $id);
                } elseif (is_array($id)) {
                    if (count($id) > 1) {
                        if (is_numeric(end($id))) {
                            // Множество ID: WHERE id IN (1,2,3)
                            $params['cond'] = array('`id` IN (' . implode(',', array_map('intval', $id)) . ')');
                        } else {
                            // Пользовательское поле и значения: WHERE field IN (val1,val2) OR field = val
                            $name = array_pop($id);
                            if (count($id) > 1) {
                                $params['cond'] = array('`'.$name.'` IN (' . implode(',', array_map(array($model, 'escape'), $id)) . ')');
                            } else {
                                $params['cond'] = array($name => $id[0]);
                            }
                        }
                    } else {
                        return array('error' => 'Неверный запрос: массив ID должен содержать более одного элемента');
                    }
                } else {
                    return array('error' => 'Неверный запрос: неподдерживаемый тип ID');
                }
            } else {
                // Если ID не указан, выбираем все записи
                $params['cond'] = array();
            }

            // Тип выборки по умолчанию
            $DB_TYPE = DB_ALL;
            
            // Обработка дополнительных параметров
            if (!empty($gets_params)) {
                // Установка типа выборки
                if (isset($gets_params['type']) && in_array($gets_params['type'], array('DB_FIRST', 'DB_ALL', 'DB_COUNT'))) {
                    $DB_TYPE = $gets_params['type'];
                }

                // Обработка сортировки
                if (isset($gets_params['sort'])) {
                    $sort = array();
                    if (is_array($gets_params['sort'])) {
                        $sort[0] = $gets_params['sort'][0];
                        $sort[1] = isset($gets_params['sort'][1]) ? $gets_params['sort'][1] : 'DESC';
                    } else {
                        $sort[0] = $gets_params['sort'];
                        $sort[1] = 'DESC';
                    }
                    
                    // Проверка безопасности имени поля для сортировки
                    if (preg_match('#^[-_0-9A-Za-z]+$#ui', $sort[0])) {
                        if (isset($sort[1]) && !in_array($sort[1], array('DESC', 'ASC'))) {
                            $sort[1] = 'DESC';
                        }
                        $params['order'] = '`'.$sort[0].'` '.$sort[1];
                    }
                }
                
                // Обработка лимита
                if (isset($gets_params['limit']) && is_numeric($gets_params['limit'])) {
                    $params['limit'] = (int)$gets_params['limit'];
                }
                
                // Обработка пагинации
                if (isset($gets_params['page']) && is_numeric($gets_params['page'])) {
                    $params['page'] = (int)$gets_params['page'];
                }
                
                // Обработка выбираемых полей
                if (isset($gets_params['fields'])) {
                    $params['fields'] = $gets_params['fields'];
                }
            }

            // Подгрузка связанных моделей
            if (!empty($binded_fields) && is_array($binded_fields)) {
                foreach ($binded_fields as $i => $field) {
                    $model->bindModel($field);
                }
            }

            // Выполнение запроса в зависимости от типа
            switch ($DB_TYPE) {
                case DB_FIRST:
                    $params['limit'] = 1;
                    $result = $model->getCollection($params['cond'], $params);
                    break;
                case DB_COUNT:
                    $result = $model->getTotal($params);
                    break;
                case DB_ALL:
                    $result = $model->getCollection($params['cond'], $params);
                    break;
            }
        }

        // Обработка результата для API (если это не подсчет количества)
        if ($DB_TYPE !== DB_COUNT && $result !== false) {
            $entity = $result;
            $result = array();
            
            foreach ($entity as $n => $obj) {
                if (is_object($obj)) {
                    // Проверяем наличие метода __getAPI для безопасного вывода данных
                    if (method_exists($obj, '__getAPI')) {
                        $__getAPI = $obj->__getAPI();
                        
                        if (!empty($__getAPI)) {
                            $result[$n] = $__getAPI;
                            
                            // Обработка связанных полей
                            if (!empty($binded_fields) && is_array($binded_fields)) {
                                foreach ($binded_fields as $i => $bfield) {
                                    $bindname = 'get'.ucfirst($bfield);
                                    
                                    // Проверяем наличие метода для получения связанных данных
                                    if (!method_exists($obj, $bindname)) {
                                        return array('error' => "Метод $bindname не существует в модели");
                                    }
                                    
                                    $binddata = $obj->$bindname();
                                    
                                    if (empty($binddata)) {
                                        continue;
                                    }
                                    
                                    if (!is_object($binddata) && !is_array($binddata)) {
                                        return array('error' => "Метод $bindname вернул неподдерживаемый тип данных");
                                    }
                                    
                                    // Нормализация данных (всегда работаем с массивом)
                                    $one_element = false;
                                    if (is_object($binddata)) {
                                        $one_element = true;
                                        $binddata = array($binddata);
                                    }
                                    
                                    $allowed_bdata = array();
                                    
                                    // Обработка каждого элемента связанных данных
                                    foreach ($binddata as $i => $bdata_element) {
                                        if (is_object($bdata_element) && method_exists($bdata_element, '__getAPI')) {
                                            $subAPI = $bdata_element->__getAPI();
                                            if (!empty($subAPI)) {
                                                // Удаляем пустые значения
                                                $allowed_bdata[$i] = array_filter($subAPI, function($val) {
                                                    return !empty($val);
                                                });
                                            }
                                        } else {
                                            return array('error' => "Связанная сущность ($bfield) не поддерживает API");
                                        }
                                    }
                                    
                                    // Если был передан один элемент, возвращаем его напрямую, а не массив
                                    $result[$n][$bfield] = $one_element ? $allowed_bdata[0] : $allowed_bdata;
                                }
                            }
                            
                            // Удаляем пустые значения из основного результата
                            $result[$n] = array_filter($result[$n], function($val) {
                                return !empty($val);
                            });
                        }
                    } else {
                        return array('error' => 'Эта сущность не поддерживает API');
                    }
                } elseif (!empty($obj)) {
                    // Если это не объект, но данные есть - сохраняем как есть
                    $result[$n] = $obj;
                }
            }
            
            // Сбрасываем ключи массива (делаем последовательную нумерацию)
            $result = array_values($result);

            // Для DB_FIRST возвращаем только первый элемент
            if ($DB_TYPE == DB_FIRST && !empty($result)) {
                $result = $result[0];
            }
        }

    } catch (Exception $e) {
        // Ловим и возвращаем исключения
        return array('error' => $e->getMessage());
    }

    // Нормализация результата (всегда возвращаем массив)
    $result = (!empty($result) && $result !== false) ? $result : array();
    
    // Сохраняем результат в кеш, если он был запрошен
    if ($is_cache) {
        $Cache->write(serialize($result), $identifient, array());
    }
    
    return $result;
}

/**
 * Получает экземпляр класса DrsQB для работы с базой данных
 * 
 * @return DrsQB Экземпляр построителя запросов
 */
function getDB() {
    // Создаем новый экземпляр DrsQB, передавая подключение к PDO
    return new DrsQB(getDrsPdo());
}

/**
 * Получает экземпляр класса DrsPdo (подключение к БД) с использованием шаблона Singleton
 * 
 * @return DrsPdo Экземпляр подключения к базе данных
 * @throws PDOException Если подключение не удалось
 */
function getDrsPdo() {
    // Статическая переменная для хранения подключения (Singleton)
    static $dbh = null;

    // Если подключение еще не установлено
    if ($dbh === null) {
        // Получаем параметры подключения из конфигурации
        $dblocation = Config::read('host', '__db__');
        $dbuser = Config::read('user', '__db__');
        $dbpasswd = Config::read('pass', '__db__');
        $dbname = Config::read('name', '__db__');
        
        // Проверяем наличие обязательных параметров
        if (empty($dblocation) || empty($dbname)) {
            throw new RuntimeException('Не заданы параметры подключения к БД');
        }

        // Формируем DSN строку для подключения
        $dsn = "mysql:dbname={$dbname};host={$dblocation};charset=utf8";
        
        // Опции подключения PDO
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Генерировать исключения при ошибках
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Возвращать ассоциативные массивы
            PDO::ATTR_EMULATE_PREPARES => false, // Использовать настоящие prepared statements
            PDO::ATTR_PERSISTENT => false, // Не использовать постоянные подключения
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'" // Явно указываем кодировку
        );

        try {
            // Создаем подключение к базе данных
            $dbh = new DrsPdo($dsn, $dbuser, $dbpasswd, $options);
            
            // Устанавливаем таймауты для дополнительной надежности
            $dbh->setAttribute(PDO::ATTR_TIMEOUT, 5); // 5 секунд таймаут
        } catch (PDOException $e) {
            // Логируем ошибку подключения
            error_log("Ошибка подключения к БД: " . $e->getMessage());
            throw $e; // Пробрасываем исключение дальше
        }
    }
    
    return $dbh;
}

## ==============================================
## Секция 2: Функции работы с ошибками и сообщениями
## ==============================================

/**
 * Показывает сообщение об ошибке с возможностью редиректа
 * 
 * @param string $message Понятное сообщение для пользователя
 * @param string $error Техническое сообщение об ошибке (показывается только в debug режиме)
 * @param bool|string $redirect Флаг редиректа или URL для редиректа
 * @param string $queryString Дополнительные параметры URL
 * @return void
 */
function showErrorMessage($message = '', $error = '', $redirect = false, $queryString = '') {

    // Обработка редиректа
    if ($redirect !== false) {
        // Определяем URL для редиректа
        $redirectUrl = is_string($redirect) ? $redirect : 
            (used_https() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . get_url($queryString);
        
        // Устанавливаем задержку из конфига или по умолчанию 5 секунд
        $delay = Config::read('redirect_delay', 5);
        
        // Проверяем, не был ли уже отправлен заголовок
        if (!headers_sent()) {
            header("Refresh: {$delay}; url={$redirectUrl}");
        } else {
            // Если заголовки уже отправлены, используем JavaScript для редиректа
            echo '<script>setTimeout(function(){ window.location.href="'.$redirectUrl.'"; }, '.($delay * 1000).');</script>';
        }
    }

    try {
        // Создаем экземпляр Viewer_Manager для отображения шаблона
        $View = new Viewer_Manager();
        
        // Подготавливаем данные для шаблона
        $templateData = array(
            'data' => array(
                'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                'errors' => Config::read('debug_mode') ? htmlspecialchars($error, ENT_QUOTES, 'UTF-8') : null,
                'url' => htmlspecialchars($queryString, ENT_QUOTES, 'UTF-8'),
                'status' => 'error'
            )
        );

        // Рендерим шаблон с сообщением об ошибке
        $html = $View->view('infomessagegrand.html', $templateData);
        
        // Выводим результат
        echo $html;
        
    } catch (Exception $e) {
        // Если что-то пошло не так, выводим простое сообщение об ошибке
        die('Произошла ошибка: ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    }
}

## ==============================================
## Секция 3: Функции работы с данными и массивами
## ==============================================

/**
 * Безопасно получает значение из GET-параметра
 * 
 * @param string $key Ключ параметра
 * @param mixed $default Значение по умолчанию (необязательно)
 * @return mixed Значение параметра или пустая строка/значение по умолчанию
 */
function ReadGET($key, $default = '') {
    // Проверяем существование ключа в массиве GET
    if (!isset($_GET[$key])) {
        return $default;
    }
    
    // Обрабатываем значение для безопасности
    if (is_array($_GET[$key])) {
        return array_map(function($item) {
            return htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
        }, $_GET[$key]);
    }
    
    return htmlspecialchars($_GET[$key], ENT_QUOTES, 'UTF-8');
}

/**
 * Генерирует массив чисел для использования в шаблонизаторе
 * Аналог range(), но с дополнительными возможностями
 * 
 * @param int $n Начальное значение или конечное (если $k = null)
 * @param int|null $k Конечное значение (необязательно)
 * @param int $step Шаг (по умолчанию 1)
 * @return array Массив чисел
 */
function a($n, $k = null, $step = 1) {
    // Нормализация параметров
    $start = ($k === null) ? 0 : $n;
    $end = ($k === null) ? $n : $k;
    $step = max(1, abs($step)); // Шаг не может быть меньше 1
    
    // Генерация диапазона и возврат среза (для гарантированного результата)
    return array_slice(range($start, $end, $step), 0);
}

/**
 * Рекурсивно объединяет два массива с ограничением глубины рекурсии (2 уровня)
 * 
 * @param array $arr1 Базовый массив
 * @param array $arr2 Массив для слияния
 * @return array Объединенный массив
 */
function array_merge_recursive2(array $arr1, array $arr2) {
    foreach ($arr2 as $k => $v) {
        if (isset($arr1[$k]) && is_array($arr1[$k]) && is_array($v)) {
            // Если оба значения - массивы, объединяем их (1 уровень вложенности)
            $arr1[$k] = array_merge($arr1[$k], $v);
        } else {
            // Иначе просто перезаписываем значение
            $arr1[$k] = $v;
        }
    }
    
    return $arr1;
}

## ==============================================
## Секция 4: Функции работы с датами и временем
## ==============================================

/**
 * Форматирует дату согласно настройкам системы с HTML5 тегом <time>
 * 
 * @param string $date Строка с датой
 * @param string|bool $format Формат даты (если false, берется из конфига)
 * @return string Отформатированная дата в HTML
 */
function DrsDate($date, $format = false) {
    // Проверка на "пустую" дату
    if ($date == '0000-00-00 00:00:00' || empty($date)) {
        return __('never');
    }
    
    // Получаем формат из конфига, если не указан
    $format = $format ?: Config::read('date_format', 'Y-m-d H:i:s');
    
    // Преобразуем дату в timestamp
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return __('invalid date');
    }
    
    // Формируем HTML с машинно-читаемым форматом datetime
    return sprintf(
        '<time datetime="%s" data-type="%s">%s</time>',
        date('c', $timestamp),
        htmlspecialchars($format, ENT_QUOTES, 'UTF-8'),
        date($format, $timestamp)
    );
}

/**
 * Форматирует относительное время (сколько осталось до наступления события)
 * 
 * @param int $time Timestamp времени события
 * @return string Строка с относительным временем
 */
function DrsOffsetDate($time) {
    $now = time();
    
    // Если время уже прошло
    if ($time <= $now) {
        return __('time passed');
    }
    
    $diff = $time - $now;
    $parts = array();
    
    // Разбиваем разницу на составляющие
    $years = floor($diff / (365 * 24 * 3600));
    if ($years > 0) {
        return sprintf(__('after %d years'), $years);
    }
    
    $months = floor($diff / (30 * 24 * 3600));
    if ($months > 0) {
        $parts[] = sprintf(__('%d month.'), $months);
    }
    
    $days = floor(($diff % (30 * 24 * 3600)) / (24 * 3600));
    if ($days > 0) {
        $parts[] = sprintf(__('%d day.'), $days);
    }
    
    $hours = floor(($diff % (24 * 3600)) / 3600);
    if ($hours > 0 && count($parts) < 2) {
        $parts[] = sprintf(__('%d hour.'), $hours);
    }
    
    $minutes = floor(($diff % 3600) / 60);
    if ($minutes > 0 && count($parts) < 2) {
        $parts[] = sprintf(__('%d minute.'), $minutes);
    }
    
    $seconds = $diff % 60;
    if (count($parts) < 2) {
        $parts[] = sprintf(__('%d sec.'), $seconds);
    }
    
    return __('after') . ' ' . implode(' ', array_slice($parts, 0, 2));
}

## ==============================================
## Секция 5: Функции работы с реферерами
## ==============================================

/**
 * Сохраняет безопасный referer URL в сессии
 */
function setReferer() {
    if (empty($_SERVER['HTTP_REFERER'])) {
        return;
    }
    
    $referer = $_SERVER['HTTP_REFERER'];
    $urlParts = parse_url($referer);
    
    // Проверяем, что referer с нашего домена
    if (!empty($urlParts['host']) && $urlParts['host'] === $_SERVER['SERVER_NAME']) {
        $path = isset($urlParts['path']) ? ltrim($urlParts['path'], '/') : '';
        $query = isset($urlParts['query']) ? '?'.$urlParts['query'] : '';
        $fragment = isset($urlParts['fragment']) ? '#'.$urlParts['fragment'] : '';
        
        $_SESSION['redirect_to'] = get_url($path . $query . $fragment, true);
    }
}

/**
 * Получает сохраненный referer URL или определяет его автоматически
 * 
 * @return string URL для редиректа
 */
function getReferer() {
    $Register = Register::getInstance();
    
    // Если referer уже был вычислен - возвращаем его
    if (isset($Register['__compiled_referer__'])) {
        return $Register['__compiled_referer__'];
    }
    
    $defaultUrl = get_url('/');
    
    // Проверяем сохраненный referer в сессии
    if (!empty($_SESSION['redirect_to'])) {
        $url = get_url('/' . ltrim($_SESSION['redirect_to'], '/'), true);
        unset($_SESSION['redirect_to']);
        $Register['__compiled_referer__'] = $url;
        return $url;
    }
    
    // Пытаемся определить referer из заголовка
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $urlParts = parse_url($_SERVER['HTTP_REFERER']);
        
        if (!empty($urlParts['host']) && $urlParts['host'] === $_SERVER['SERVER_NAME']) {
            $path = isset($urlParts['path']) ? $urlParts['path'] : '';
            $query = isset($urlParts['query']) ? '?'.$urlParts['query'] : '';
            $fragment = isset($urlParts['fragment']) ? '#'.$urlParts['fragment'] : '';
            
            $url = get_url($path . $query . $fragment, true);
            $Register['__compiled_referer__'] = $url;
            return $url;
        }
    }
    
    // Возвращаем URL по умолчанию
    $Register['__compiled_referer__'] = $defaultUrl;
    return $defaultUrl;
}

## ==============================================
## Секция 6: Функции сортировки и порядка
## ==============================================

/**
 * Получает параметр сортировки с проверкой допустимых значений для разных модулей
 * 
 * @param string $class_name Имя класса модуля
 * @return string|false Строка для ORDER BY или false при ошибке
 */
function getOrderParam($class_name) {
    // Получаем параметры сортировки из GET-запроса
    $order = isset($_GET['order']) ? trim($_GET['order']) : '';
    $direction = isset($_GET['asc']) ? 'ASC' : 'DESC';
    
    // Определяем допустимые поля сортировки для каждого модуля
    $allowed_keys = array();
    $default_key = '';
    
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
            $allowed_keys = array('puttime', 'last_visit', 'name', 'rating', 'posts', 
                                'status', 'warnings', 'byear', 'pol', 'ban_expire');
            $default_key = 'puttime';
            break;
            
        default:
            return false;
    }
    
    // Проверяем валидность параметра сортировки
    if (empty($order)) {
        $out = $default_key;
    } else {
        $out = in_array($order, $allowed_keys) ? $order : $default_key;
    }
    
    return $out ? $out . ' ' . $direction : false;
}

## ==============================================
## Секция 7: Функции CRON и автоматических задач
## ==============================================

/**
 * Эмуляция CRON-задач с контролем интервала выполнения
 * 
 * @param string $func Имя функции для выполнения
 * @param int $interval Интервал в секундах между запусками
 */
function drsCron($func, $interval) {
	
    $interval = max(60, (int)$interval); // Минимальный интервал - 60 секунд
    $cron_file = ROOT . '/sys/tmp/' . md5($func) . '_cron.dat';
    
    // Проверяем время последнего выполнения
    if (file_exists($cron_file)) {
        $extime = (int)file_get_contents($cron_file);
        if ($extime > time()) {
            return; // Еще не время запускать
        }
    }
    
    // Выполняем функцию, если она существует
    if (function_exists($func)) {
        // Блокировка для предотвращения параллельного выполнения
        $lock_file = $cron_file . '.lock';
        $lock = fopen($lock_file, 'w');
        
        if (flock($lock, LOCK_EX | LOCK_NB)) {
            try {
                call_user_func($func);
                file_put_contents($cron_file, time() + $interval);
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
                @unlink($lock_file);
            }
        }
    }
}

## ==============================================
## Секция 8: Функции SEO и карты сайта
## ==============================================

/**
 * Генератор sitemap.xml
 */
function createSitemap() {
    try {
        $obj = new Sitemap();
        $obj->createMap();
    } catch (Exception $e) {
        error_log('Ошибка генерации sitemap: ' . $e->getMessage());
    }
}

/**
 * Автоматическое обновление robots.txt с текущим хостом и sitemap
 */
function createRobots() {
    $robots_file = ROOT . '/robots.txt';
    
    // Читаем существующий файл
    $lines = array();
    if (file_exists($robots_file)) {
        $content = file_get_contents($robots_file);
        $lines = explode("\n", $content);
    }
    
    // Фильтруем старые директивы Host и Sitemap
    $filtered = array();
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (strncasecmp($trimmed, 'Host:', 5) !== 0 && 
            strncasecmp($trimmed, 'Sitemap:', 8) !== 0) {
            $filtered[] = $line;
        }
    }
    
    // Добавляем актуальные директивы
    $protocol = used_https() ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    $filtered[] = "Host: {$protocol}{$host}\n";
    $filtered[] = "Sitemap: {$protocol}{$host}/sitemap.xml\n";
    
    // Сохраняем обновленный файл
    file_put_contents($robots_file, implode('', $filtered));
}

## ==============================================
## Секция 9: Функции работы с URL
## ==============================================

/**
 * Генератор ЧПУ-ссылки для материала
 * 
 * @param object $material Объект материала
 * @param string $module Название модуля
 * @return string URL материала
 */
function entryUrl($material, $module) {
    if (!is_object($material) || !method_exists($material, 'getId') || 
        !method_exists($material, 'getTitle')) {
        return '#';
    }
    
    $matId = $material->getId();
    $matTitle = $material->getTitle();
    
    return matUrl($matId, $matTitle, $module);
}

/**
 * Генератор ЧПУ-ссылки по параметрам
 * 
 * @param int $matId ID материала
 * @param string $matTitle Заголовок материала
 * @param string $module Название модуля
 * @return string URL материала
 */
function matUrl($matId, $matTitle, $module) {
    try {
        $urlGenerator = Register::getClass('DrsUrl');
        if (is_object($urlGenerator) && method_exists($urlGenerator, 'getEntryUrl')) {
            return $urlGenerator->getEntryUrl($matId, $matTitle, $module);
        }
    } catch (Exception $e) {
        error_log('Ошибка генерации URL: ' . $e->getMessage());
    }
    
    return '#';
}

## ==============================================
## Секция 10: Функции CAPTCHA
## ==============================================

/**
 * Генератор CAPTCHA
 * 
 * @param string $name Имя поля формы (необязательно)
 * @return string HTML-код CAPTCHA
 */
function getCaptcha($name = false) {
    $kcaptcha_path = '/sys/inc/kcaptcha/kc.php?' . mt_rand(1000, 999999);
    if ($name) {
        $kcaptcha_path .= '&name=' . urlencode($name);
    }
    
    $template_file = ROOT . '/template/' . getTemplate() . '/html/default/captcha.html';
    if (file_exists($template_file)) {
        $tpl = file_get_contents($template_file);
        return str_replace('{CAPTCHA}', htmlspecialchars($kcaptcha_path, ENT_QUOTES, 'UTF-8'), $tpl);
    }
    
    return '<img src="' . htmlspecialchars($kcaptcha_path, ENT_QUOTES, 'UTF-8') . '" alt="CAPTCHA">';
}

## ==============================================
## Секция 11: Функции локализации
## ==============================================

/**
 * Система локализации (переводов)
 * 
 * @param string $key Ключ перевода
 * @param bool $tpl_lang_important Приоритет языкового файла шаблона
 * @param string $module Название модуля для поиска переводов
 * @return string Переведенная строка или исходный ключ
 */
function __($key, $tpl_lang_important = false, $module = false) {
    static $loaded_files = array();
    
    // Проверка входных параметров
    if (!is_string($key) || empty($key)) {
        return '';
    }
    
    // Получаем текущий язык
    $language = getLang();
    if (!is_string($language)) {
        $language = 'rus';
    }
    
    // Инициализация Register
    $Register = Register::getInstance();
    if (!$module && isset($Register['module'])) {
        $module = $Register['module'];
    }
    
    // Пути к файлам локализации
    $lang_files = array(
        'global' => R.'data/languages/' . $language . '.php',
        'template' => R.'template/' . getTemplate() .'/languages/' . $language . '.php',
        'module' => $module ? R.'modules/'.$module.'/lang/' . $language . '.php' : false
    );
    
    // Загрузка переводов
    $translations = array();
    foreach ($lang_files as $type => $file) {
        if (!$file || !file_exists($file)) continue;
        
        if (!isset($loaded_files[$file])) {
            $loaded_files[$file] = include $file;
            if (!is_array($loaded_files[$file])) {
                $loaded_files[$file] = array();
            }
        }
        
        $translations[$type] = $loaded_files[$file];
    }
    
    // Определение приоритета переводов
    if ($tpl_lang_important) {
        // Приоритет: шаблон > модуль > глобальный
        if (isset($translations['template'][$key])) {
            return $translations['template'][$key];
        } elseif ($module && isset($translations['module'][$key])) {
            return $translations['module'][$key];
        } elseif (isset($translations['global'][$key])) {
            return $translations['global'][$key];
        }
    } else {
        // Приоритет: модуль > глобальный > шаблон
        if ($module && isset($translations['module'][$key])) {
            return $translations['module'][$key];
        } elseif (isset($translations['global'][$key])) {
            return $translations['global'][$key];
        } elseif (isset($translations['template'][$key])) {
            return $translations['template'][$key];
        }
    }
    
    // Если перевод не найден, возвращаем ключ
    return $key;
}

/**
 * Получение текущего языка системы
 * 
 * @return string Код текущего языка
 */
function getLang() {
    // В админке используем язык из конфига
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], WWW_ROOT . '/admin') === 0) {
        return Config::read('language');
    }
    
    // Проверяем язык в сессии и куках
    if (!empty($_SESSION['lang'])) {
        return $_SESSION['lang'];
    }
    
    return Config::read('language');
}

/**
 * Получение списка разрешенных языков системы
 * 
 * @return array Массив кодов разрешенных языков
 */
function getPermittedLangs() {
    $langs = Config::read('permitted_languages');
    
    if (!empty($langs)) {
        // Обработка строки из конфига
        $langs = explode(',', $langs);
        $langs = array_map('trim', $langs);
        return array_filter($langs);
    }
    
    // Автоматическое определение языков по файлам
    $lang_files = glob(ROOT . '/data/languages/*.php');
    $detected_langs = array();
    
    foreach ($lang_files as $file) {
        $lang = pathinfo($file, PATHINFO_FILENAME);
        $detected_langs[] = $lang;
    }
    
    return $detected_langs ?: array('rus');
}

## ==============================================
## Секция 12: Функции работы с HTML и URL
## ==============================================

/**
 * Генерирует HTML-код изображения с корректным URL
 * Автоматически учитывает установку системы в поддиректорию
 * 
 * @param string $url URL изображения
 * @param array $params Дополнительные атрибуты тега img
 * @param bool $notRoot Если true, URL не будет обрабатываться как корневой
 * @return string HTML-код изображения
 */
function get_img($url, $params = array(), $notRoot = false) {
    $additional = '';
    if (!empty($params) && is_array($params)) {
        foreach ($params as $key => $value) {
            $additional .= h($key) . '="' . h($value) . '" ';
        }
    }
    return '<img ' . $additional . 'src="' . get_url($url, $notRoot) . '" alt="" />';
}

/**
 * Генерирует корректный URL с учетом поддиректорий
 * 
 * @param string $url Исходный URL
 * @param bool $notRoot Если true, URL не будет обрабатываться как корневой
 * @return string Обработанный URL
 */
function get_url($url, $notRoot = false) {
    // Обработка URL для админки
    if (strpos($url, '/admin/') !== false) {
        return $url;
    }

    // Добавляем корневой путь, если требуется
    if (!$notRoot) {
        $url = '/' . WWW_ROOT . $url;
        $url = str_replace('//', '/', $url); // Убираем дублирующиеся слеши
    }

    // Парсим маршруты через систему роутинга
    return DrsUrl::parseRoutes($url);
}

/**
 * Генерирует HTML-ссылку с корректным URL
 * 
 * @param string $anchor Текст ссылки
 * @param string $url URL ссылки
 * @param array $params Дополнительные атрибуты тега a
 * @param bool $notRoot Если true, URL не будет обрабатываться как корневой
 * @param bool $translate Нужно ли переводить текст ссылки
 * @return string HTML-код ссылки
 */
function get_link($anchor, $url, $params = array(), $notRoot = false, $translate = false) {
    $additional = '';
    if (!empty($params) && is_array($params)) {
        foreach ($params as $key => $value) {
            $additional .= h($key) . '="' . h($value) . '" ';
        }
    }
    $text = $translate ? __($anchor) : $anchor;
    return '<a ' . $additional . 'href="' . get_url($url, $notRoot) . '">' . $text . '</a>';
}

/**
 * Выполняет редирект с HTTP-кодом ответа
 * 
 * @param string $url URL для перенаправления
 * @param bool $notRoot Если true, URL не будет обрабатываться как корневой
 * @param int $header HTTP-код ответа (301 или 302)
 */
function redirect($url, $notRoot = false, $header = 302) {
    $allowed_headers = array(301, 302);
    $header = in_array($header, $allowed_headers) ? $header : 302;

    header('Location: ' . get_url($url, $notRoot), true, $header);
    exit;
}

## ==============================================
## Секция 13: Функции генерации HTML элементов
## ==============================================

/**
 * Генерирует HTML-опции для select
 * 
 * @param int $offset Начальное значение
 * @param int $limit Конечное значение
 * @param mixed $selected Выбранное значение
 * @return string HTML-код опций
 */
function createOptionsFromParams($offset, $limit, $selected = false) {
    $output = '';
    for ($i = $offset; $i <= $limit; $i++) {
        $select = ($selected !== false && $i == $selected) ? ' selected="selected"' : '';
        $output .= '<option value="' . $i . '"' . $select . '>' . $i . '</option>';
    }
    return $output;
}

## ==============================================
## Секция 14: Функции отладки и вывода
## ==============================================

/**
 * Выводит переменную в удобочитаемом формате
 * 
 * @param mixed $param Переменная для вывода
 */
function pr($param) {
    echo '<pre>' . print_r($param, true) . '</pre>';
}

/**
 * Экранирует спецсимволы HTML (короткий аналог htmlspecialchars)
 * 
 * @param mixed $param Входные данные (строка или массив)
 * @return mixed Экранированные данные
 */
function h($param) {
    if (is_array($param)) {
        return array_map('h', $param);
    }
    
    if (!is_string($param)) {
        return $param;
    }
    
    $param = htmlspecialchars($param, ENT_QUOTES, 'UTF-8');
    // Восстанавливаем специальные символы, которые могли быть заэкранированы
    $symbols = array(
        '&amp;#125;' => '&#125;',
        '&amp;#123;' => '&#123;',
    );
    return strtr($param, $symbols);
}

/**
 * Возвращает текущее время в микросекундах
 * 
 * @return float Текущее время
 */
function getMicroTime() {
    return microtime(true);
}

/**
 * Записывает переменную в файл дампа (для отладки)
 * 
 * @param mixed $var Переменная для дампа
 */
function dumpVar($var) {
    $content = is_string($var) ? $var : print_r($var, true);
    file_put_contents(ROOT . '/dump.dat', $content . "\n", FILE_APPEND);
}

## ==============================================
## Секция 15: Функции безопасности
## ==============================================

/**
 * Экранирует строку для SQL-запросов
 * 
 * @param string $str Исходная строка
 * @return string Экранированная строка
 */
function resc($str) {
    return getDB()->escape($str);
}

/**
 * Удаляет экранирующие слеши из данных
 * 
 * @param mixed $param Входные данные (строка или массив)
 */
function strips(&$param) {
    if (is_array($param)) {
        foreach ($param as $k => $v) {
            strips($param[$k]);
        }
    } else {
        $param = stripslashes($param);
    }
}

/**
 * Фильтрует строку, оставляя только UTF-8 символы
 * 
 * @param string $str Исходная строка
 * @return string Отфильтрованная строка
 */
function utf8Filter($str) {
    return preg_match('#.{1}#us', $str) ? $str : '';
}

## ==============================================
## Секция 16: Функции работы с памятью
## ==============================================

/**
 * Выводит разницу использования памяти
 * 
 * @param int $base_memory_usage Базовое значение использования памяти
 */
function memoryUsage($base_memory_usage) {
    printf("Bytes diff: %s<br />\n", getSimpleFileSize(memory_get_usage() - $base_memory_usage));
}

## ==============================================
## Секция 17: Функции работы с шаблонами
## ==============================================

/**
 * Возвращает имя текущего шаблона
 * 
 * @return string Имя шаблона
 */
function getTemplate() {
    // Проверяем шаблон пользователя из сессии
    if (isset($_SESSION['user']['template']) && 
        !empty($_SESSION['user']['template']) &&
        file_exists(ROOT . '/template/' . $_SESSION['user']['template'])) {
        $template = $_SESSION['user']['template'];
    } else {
        $template = Config::read('template');
    }
    
    // Позволяем модифицировать выбор шаблона через события
    return Events::init('select_template', $template);
}

## ==============================================
## Секция 18: Функции работы с паролями
## ==============================================

/**
 * Проверяет соответствие пароля его хешу
 * 
 * @param string $md5_password Хеш пароля
 * @param string $password Проверяемый пароль
 * @return bool Результат проверки
 */
function checkPassword($md5_password, $password) {
    // Проверка для хешей с солью
    if (strpos($md5_password, '$1$') === 0 && CRYPT_MD5 == 1) {
        return crypt($password, $md5_password) === $md5_password;
    }
    // Проверка обычного MD5
    return md5($password) === $md5_password;
}

/**
 * Генерирует хеш пароля
 * 
 * @param string $password Пароль
 * @return string Хеш пароля
 */
function md5crypt($password) {
    $Register = Register::getInstance();
    if ($Register['Config']->read('use_md5_salt', 'users') == 1) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
        $salt = '';
        for ($i = 0; $i < 4; $i++) {
            $salt .= $alphabet[rand(0, strlen($alphabet)-1)];
        }
        return crypt($password, '$1$' . $salt . '$');
    }
    return md5($password);
}

## ==============================================
## Секция 19: Функции сравнения и сортировки
## ==============================================

/**
 * Сравнивает элементы массива по полю 'text'
 * 
 * @param array $a Первый элемент
 * @param array $b Второй элемент
 * @return int Результат сравнения
 */
function cmpText($a, $b) {
    if (is_array($a) && is_array($b) && isset($a['text']) && isset($b['text'])) {
        return strcmp($a['text'], $b['text']);
    }
    return 0;
}

## ==============================================
## Секция 20: Функции проверки доступа
## ==============================================

/**
 * Проверяет доступ пользователя
 * 
 * @param array|null $params Параметры проверки
 * @return bool Результат проверки
 */
function checkAccess($params = null) {
    if (!empty($params) && is_array($params)) {
        return ACL::turnUser($params, false);
    }
    return false;
}

## ==============================================
## Секция 21: Функции работы с конфигурацией
## ==============================================

/**
 * Читает значение из конфигурации
 * 
 * @param array $params Параметры конфига
 * @return mixed Значение из конфига или false
 */
function config($params = null) {
    if (isset($params) && is_array($params) && count($params) >= 1 && $params[0] != '__db__') {
        return Config::read(implode('.', $params));
    }
    return false;
}

## ==============================================
## Секция 22: Функции генерации ссылок сортировки
## ==============================================

/**
 * Генерирует ссылку для сортировки
 * 
 * @param array $params Параметры сортировки
 * @return string HTML-код ссылки
 */
function getOrderLink($params) {
    if (!$params || !is_array($params) || count($params) < 2) return '';
    
    $order = isset($_GET['order']) ? strtolower(trim($_GET['order'])) : '';
    $new_order = strtolower($params[0]);
    $active = ($order === $new_order);
    $asc = ($active && isset($_GET['asc']));
    
    return '<a href="?order=' . $new_order . ($asc ? '' : '&asc=1') . '">' . 
           $params[1] . ($active ? ' ' . ($asc ? '↑' : '↓') : '') . '</a>';
}

## ==============================================
## Секция 23: Функции работы со строками
## ==============================================

/**
 * Применяет trim() ко всем элементам массива
 * 
 * @param array $old_array Исходный массив
 * @return array Обработанный массив
 */
function atrim($old_array) {
    return array_map('trim', $old_array);
}

/**
 * Работа с куками (чтение/запись)
 * 
 * @param string $key Ключ куки
 * @param mixed $value Значение (если null - чтение)
 * @return mixed Значение куки или null
 */
function cookie($key, $value = null) {
    $protected = array('PHPSESSID', 'autologin', 'userid', 'password');
    if (in_array($key, $protected)) return null;
    
    if ($value === null) {
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : '';
    }
    setcookie($key, $value);
}

/**
 * Возвращает подстроку с добавлением окончания, если нужно
 * 
 * @param string $str Исходная строка
 * @param int $n Начальная позиция или длина
 * @param int|null $k Длина подстроки (если null, используется $n)
 * @param string $ext Окончание (по умолчанию '...')
 * @return string Результирующая подстрока
 */
function substr_ext($str, $n, $k = null, $ext = '...') {
    if ($k === null) {
        $k = $n;
        $n = 0;
    }
    
    if (mb_strlen($str) <= $n + $k) {
        $ext = '';
    }
    
    return mb_substr($str, $n, $k) . $ext;
}

## ==============================================
## Секция 24: Функции работы с пользователями
## ==============================================

/**
 * Получает список пользователей, у которых сегодня день рождения
 * Использует кеширование на 1 час для уменьшения нагрузки
 * 
 * @return array Массив пользователей или пустой массив, если нет именинников
 */
function getBornTodayUsers() {
    $Register = Register::getInstance();
    $DrsDB = getDB();
    $cacheFile = ROOT . '/sys/logs/today_born.dat';

    // Проверяем актуальность кеша (1 час)
    if (!file_exists($cacheFile) || (filemtime($cacheFile) + 3600) < time()) {
        // Формируем условие для поиска по дате рождения (месяц и день)
        $today = date("nj"); // Текущий день и месяц без ведущих нулей
        $today_born = $DrsDB->select('users', DB_ALL, array(
            'cond' => array(
                "CONCAT(LPAD(`bmonth`, 2, '0'), LPAD(`bday`, 2, '0')) = ?" => $today
            ),
        ));
        
        // Сохраняем результат в кеш
        file_put_contents($cacheFile, serialize($today_born), LOCK_EX);
    } else {
        // Получаем данные из кеша
        $today_born = file_get_contents($cacheFile);
        $today_born = !empty($today_born) ? unserialize($today_born) : array();
    }

    return is_array($today_born) ? $today_born : array();
}

/**
 * Возвращает общее количество зарегистрированных пользователей
 * Использует кеширование на 1 час для уменьшения нагрузки
 * 
 * @return int Количество пользователей
 */
function getAllUsersCount() {
    $Cache = new Cache;
    $Cache->lifeTime = 3600;
    $Cache->prefix = 'users';
    $Cache->cacheDir = ROOT . '/sys/cache/users/';

    if ($Cache->check('cnt_registered_users')) {
        $cnt = $Cache->read('cnt_registered_users');
    } else {
        $cnt = getDB()->select('users', DB_COUNT);
        $Cache->write($cnt, 'cnt_registered_users', array());
    }

    return !empty($cnt) ? (int)$cnt : 0;
}

/**
 * Очищает кеш количества зарегистрированных пользователей
 */
function cleanAllUsersCount() {
    $Cache = new Cache;
    $Cache->lifeTime = 3600;
    $Cache->prefix = 'users';
    $Cache->cacheDir = ROOT . '/sys/cache/users/';
    
    if ($Cache->check('cnt_registered_users')) {
        $Cache->remove('cnt_registered_users');
    }
}

/**
 * Генерирует URL Gravatar для указанного email
 * 
 * @param string $email Email пользователя
 * @param int $s Размер аватара (1-2048)
 * @param string $d Тип дефолтного аватара (404|mm|identicon|monsterid|wavatar)
 * @param string $r Максимальный рейтинг (g|pg|r|x)
 * @return string URL Gravatar
 */
function getGravatar($email, $s = 120, $d = 'mm', $r = 'g') {
    $email = strtolower(trim($email));
    $hash = md5($email);
    $size = max(1, min(2048, (int)$s));
    
    return sprintf(
        'https://www.gravatar.com/avatar/%s?%s',
        $hash,
        http_build_query(array(
            's' => $size,
            'd' => $d,
            'r' => $r
        ))
    );
}

/**
 * Возвращает URL аватара пользователя
 * 
 * @param int|null $id_user ID пользователя
 * @param string|null $email_user Email пользователя (опционально)
 * @return string URL аватара
 */
function getAvatar($id_user = null, $email_user = null) {
    $defaultAvatar = get_url('/template/' . getTemplate() . '/img/noavatar.png');
    
    if (empty($id_user) || $id_user <= 0) {
        return $defaultAvatar;
    }

    // Проверяем наличие загруженного аватара
    $avatarPath = ROOT . '/data/avatars/' . $id_user . '.jpg';
    if (file_exists($avatarPath)) {
        return get_url('/data/avatars/' . $id_user . '.jpg');
    }

    // Используем Gravatar, если включено в настройках
    if (Config::read('use_gravatar', 'users')) {
        $Cache = new Cache;
        $Cache->prefix = 'gravatar';
        $Cache->cacheDir = ROOT . '/sys/cache/users/gravatars/';
        $Cache->lifeTime = 86400; // 24 часа
        
        $cacheKey = 'user_' . $id_user;
        
        // Пытаемся получить из кеша
        if ($Cache->check($cacheKey)) {
            return $Cache->read($cacheKey);
        }
        
        // Если email не передан, получаем из БД
        if (empty($email_user)) {
            $usersModel = OrmManager::getModelInstance('Users');
            $user = $usersModel->getById($id_user);
            $email_user = $user ? $user->getEmail() : '';
        }
        
        if (!empty($email_user)) {
            $gravatarUrl = getGravatar($email_user);
            $Cache->write($gravatarUrl, $cacheKey, array());
            return $gravatarUrl;
        }
    }
    
    return $defaultAvatar;
}

/**
 * Вычисляет возраст по дате рождения
 * 
 * @param int $y Год рождения
 * @param int $m Месяц рождения
 * @param int $d День рождения
 * @return int Возраст
 */
function getAge($y = 1970, $m = 1, $d = 1) {
    $y = (int)$y;
    $m = (int)$m;
    $d = (int)$d;
    
    $currentYear = date('Y');
    $currentMonth = date('n');
    $currentDay = date('j');
    
    $age = $currentYear - $y;
    
    // Уменьшаем возраст, если день рождения еще не наступил
    if ($currentMonth < $m || ($currentMonth == $m && $currentDay < $d)) {
        $age--;
    }
    
    return max(0, $age); // Возраст не может быть отрицательным
}

/**
 * Генерирует URL профиля пользователя
 * 
 * @param int $user_id ID пользователя
 * @param bool $notRoot Не обрабатывать как корневой URL
 * @return string URL профиля
 */
function getProfileUrl($user_id, $notRoot = false) {
    return get_url('/users/info/' . (int)$user_id . '/', $notRoot);
}

/**
 * Определяет рейтинг пользователя на основе количества постов
 * 
 * @param int $posts Количество постов пользователя
 * @return array Массив с данными рейтинга ('rank' => текст, 'img' => имя файла)
 */
function getUserRating($posts) {
    if (!is_numeric($posts)) {
        return array('rank' => '', 'img' => 'star0.png');
    }
    
    $params = Config::read('users.stars');
    if (!is_array($params)) {
        return array('rank' => '', 'img' => 'star0.png');
    }
    
    $posts = (int)$posts;
    $rating = array('rank' => '', 'img' => 'star0.png');
    
    // Определяем уровень рейтинга
    for ($i = 0; $i <= 10; $i++) {
        $condKey = 'cond' . $i;
        $ratKey = 'rat' . $i;
        
        if (isset($params[$condKey]) && $posts >= $params[$condKey]) {
            $rating['img'] = 'star' . $i . '.png';
            $rating['rank'] = isset($params[$ratKey]) ? $params[$ratKey] : '';
        } else {
            break;
        }
    }
    
    return $rating;
}

/**
 * Возвращает только имя файла изображения рейтинга
 * 
 * @param int $posts Количество постов
 * @return string Имя файла
 */
function getUserRatingImg($posts) {
    $info = getUserRating($posts);
    return isset($info['img']) ? $info['img'] : 'star0.png';
}

/**
 * Возвращает только текстовое описание рейтинга
 * 
 * @param int $posts Количество постов
 * @return string Текст рейтинга
 */
function getUserRatingText($posts) {
    $info = getUserRating($posts);
    return isset($info['rank']) ? $info['rank'] : '';
}

/**
 * Заменяет плейсхолдеры в строке значениями из массива
 * 
 * @param string $str Исходная строка с плейсхолдерами %(key)
 * @param array $data Ассоциативный массив подстановок
 * @return string Результирующая строка
 */
function string_f($str, $data) {
    if (!is_array($data)) {
        return $str;
    }
    
    $search = array();
    $replace = array();
    
    foreach ($data as $key => $value) {
        $search[] = '%(' . $key . ')';
        $replace[] = $value;
    }
    
    return str_replace($search, $replace, $str);
}

/**
 * Проверяет, используется ли HTTPS на сайте
 * 
 * @return bool True если используется HTTPS
 */
function used_https() {
    // Проверка стандартных признаков HTTPS
    if ((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') || 
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) {
        return true;
    }
    
    // Проверка настройки в конфигурации
    return (bool)Config::read('used_https', '__secure__');
}