<?php
/**
 * Файл инициализации системы (boot.php)
 * 
 * Основные функции:
 * - Установка временной зоны и базовых констант
 * - Проверка установки системы
 * - Автозагрузка классов и функций
 * - Инициализация реестра (Registry)
 * - Настройка параметров PHP
 * - Конфигурация вывода ошибок
 * - Защитные механизмы системы
 *
 * @project     DarsiPro
 * @author      Петров Евгений <email@mail.ru>
 * @package     System Core
 * @url         https://darsi.pro
 * @version     1.0
 * @php         5.6+
 */

// Включение отладки при необходимости
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Замер времени выполнения скрипта
if (!isset($usec) && !isset($sec)) {
    list($usec, $sec) = explode(" ", microtime());
}

/**
 * Настройки сессии ДО ее запуска
 */
ini_set('session.gc_maxlifetime', 3600); // 1 час

// Базовые параметры cookies сессии
$cookieParams = [
    'lifetime' => 3000,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true
];

// Добавляем SameSite только для PHP 7.3+
if (PHP_VERSION_ID >= 70300) {
    $cookieParams['samesite'] = 'Lax';
}

// Установка параметров cookies
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($cookieParams);
} else {
    // Для старых версий PHP
    session_set_cookie_params(
        $cookieParams['lifetime'],
        $cookieParams['path'],
        $cookieParams['domain'],
        $cookieParams['secure'],
        $cookieParams['httponly']
    );
}

// Инициализация сессии
session_start([
    'use_strict_mode' => true,
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
]);

// Ручная установка SameSite для PHP < 7.3
if (PHP_VERSION_ID < 70300 && !headers_sent()) {
    $cookie = session_name() . '=' . session_id() . '; path=/';
    if ($cookieParams['secure']) {
        $cookie .= '; Secure';
    }
    $cookie .= '; HttpOnly; SameSite=Lax';
    header('Set-Cookie: ' . $cookie, false);
}

// Очистка предыдущих запросов к БД, если они есть
if (isset($_SESSION['db_querys'])) {
    unset($_SESSION['db_querys']);
}

// Установка временной зоны по умолчанию
date_default_timezone_set('UTC');

/**
 * Константы системы
 */
define('DARSI_VERSION', '002 (26.06.2025)');  // Версия системы
define('DS', DIRECTORY_SEPARATOR);             // Разделитель директорий
define('ROOT', dirname(realpath(__DIR__)));    // Корневая директория
define('R', ROOT . DS);                        // Корень с разделителем

// Определение базового URL для работы с путями
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$wwwRoot = str_replace('\\', '/', substr(ROOT, strlen($documentRoot)));
define('WWW_ROOT', $wwwRoot);

// Защита админ-панели по рефереру
define('ADM_REFER_PROTECTED', 0);

/**
 * Проверка установки системы
 * 
 * @return bool Возвращает true, если система установлена
 */
function isInstall() {
    return !file_exists(ROOT . '/install') || !is_dir(ROOT . '/install');
}

/**
 * Автозагрузка классов и функций
 */
require_once 'lib/AL.class.php';
AL::register();

/**
 * Загрузка вспомогательных функций
 */
function loadFuncs() {
    $files = glob(ROOT . '/sys/fnc/*.php');
    if ($files !== false) {
        foreach ($files as $file) {
            require_once $file;
        }
    }
}
loadFuncs();

/**
 * Инициализация реестра системы
 */
$Register = Register::getInstance();
$Register['boot_start_time'] = ((float)$usec + (float)$sec); // Время начала загрузки

// Загрузка конфигурации системы
$configPath = ROOT . '/sys/settings/config.php';
if (!file_exists($configPath)) {
    die('Configuration file not found!');
}
$Register['Config'] = new Config($configPath);

// Регистрация пространств имен для модулей
$installed_modules = Config::read('installed_modules');
if (!empty($installed_modules) && is_array($installed_modules)) {
    foreach ($installed_modules as $module) {
        $moduleName = ucfirst($module);
        AL::addNamespace("{$moduleName}Module", [R . 'modules/' . $module]);
    }
}

/**
 * Настройки PHP
 */
// Безопасность
ini_set('register_globals', 0);
ini_set('magic_quotes_gpc', 0);
ini_set('magic_quotes_runtime', 0);

// Лимиты
ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');
if (function_exists('set_time_limit')) {
    @set_time_limit(200);
}

// Настройки ошибок
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE);
ini_set('log_errors', 1);
ini_set('error_log', ROOT . '/sys/logs/php_errors.log');

// Включение режима отладки если нужно
if (Config::read('debug_mode') == 1) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Установка кодировки
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

/**
 * Основная загрузка системы (только если установлена)
 */
if (isInstall()) {
    // Защита от magic quotes (для старых версий PHP)
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        strips($_GET);
        strips($_POST);
        strips($_COOKIE);
        strips($_REQUEST);
        if (isset($_SERVER['PHP_AUTH_USER'])) strips($_SERVER['PHP_AUTH_USER']);
        if (isset($_SERVER['PHP_AUTH_PW'])) strips($_SERVER['PHP_AUTH_PW']);
    }

    // Защитные механизмы
    Protect::checkIpBan();
    
    if (Config::read('anti_ddos', '__secure__') == 1) {
        Protect::antiDdos();
    }
    
    if (Config::read('antisql', '__secure__') == 1) {
        Protect::antiSQL();
    }

    // Авторизация пользователя
    if (!isset($_SESSION['user']['name'])) {
        // Автовход по кукам
        if (isset($_COOKIE['autologin'])) {
            UserAuth::autoLogin();
        }
        
        // Обновление времени последнего визита
        UserAuth::setTimeVisit();
    }
}