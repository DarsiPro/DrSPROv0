<?php
/**
 * Файл boot.php расположен в sys.
 *
 * Содержание:
 *  - Назначение дефолтной временной зоны;
 *  - Устанавливаются константы для работы движка;
 *  - Проверка установлена ли система;
 *  - Autoload - Автоматически подгружает запрошенные классы;
 *  - Registry - Реестр;
 *  - Добавление стандартных местоположений в пространстве имен;
 *  - SET PHP SETTINGS;
 *  - настраивается вывод ошибок.
 */
 
 
 
if (!isset($usec) && !isset($sec))
    list($usec, $sec) = explode(" ", microtime());

session_start();
if (isset($_SESSION['db_querys'])) unset($_SESSION['db_querys']);

// Назначение дефолтной временной зоны, чтобы подсчеты велись так как надо.
date_default_timezone_set('UTC');

/**
 * Текущая версия.
 */
define('DARSI_VERSION', '002 (09.07.2024)');

/**
 * Пути
 */
define('ROOT', dirname(realpath(__DIR__)));
define ('DS', DIRECTORY_SEPARATOR);
define ('R', dirname(realpath(__DIR__)) . DS);

/**
 * Если мы используем CMS из subdir или субдиров,
 * мы должны установить эту переменную, потому что CMS
 * должна знать это для хорошей работы.
 */
define ('WWW_ROOT', str_replace('\\', '/', mb_substr(ROOT , mb_strlen(realpath($_SERVER['DOCUMENT_ROOT']), 'utf-8'))));

/**
 * Если установлено значение 1, проверьте реферера в панели администратора
 * * и, если он не соответствует текущему хосту, перенаправьте на
 * указательную страницу панели администратора. Это не позволяет
 * отправлять запросы с других хостов.
 */
define ('ADM_REFER_PROTECTED', 0);

/**
 * установлена ли система
 */
function isInstall() {
    return !file_exists(ROOT . '/install');
}

/**
 * Autoload
 */
// Загружает класс, если он еще не был загружен при его непосредственном вызове
include_once 'lib/AL.class.php';
AL::register();
function loadFuncs() {
    $files = glob(ROOT . '/sys/fnc/*.php');
    if (count($files)) {
        foreach ($files as $file) {
            include_once $file;
        }
    }
}
loadFuncs();

/**
 * Registry - Реестр
 */
$Register = Register::getInstance();
/**  TOUCH START TIME - ВРЕМЯ НАЧАЛА*/
$Register['boot_start_time'] = ((float)$usec + (float)$sec);
/** /TOUCH START TIME */

$Register['Config'] = new Config(ROOT . '/sys/settings/config.php');

// Добавление стандартных местоположений в пространстве имен
$installed_modules = Config::read('installed_modules');
if (!empty($installed_modules)) {
    foreach($installed_modules as $module) {
        AL::addNamespace(ucfirst($module)."Module", array(R.'modules/'.$module));
    }
}

/**  SET PHP SETTINGS */
@ini_set('session.gc_maxlifetime', 10000);
ini_set('post_max_size', "100M");
ini_set('upload_max_filesize', "100M");
if (function_exists('set_time_limit')) @set_time_limit(200);
ini_set('register_globals', 0);
ini_set('magic_quotes_gpc', 0);
ini_set('magic_quotes_runtime', 0);
session_set_cookie_params(3000);
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE);

/** if debug mode On - view errors 
если включен режим отладки - просмотр ошибок
*/
if (Config::read('debug_mode') == 1) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('log_errors', 1);
ini_set('error_log', ROOT . '/sys/logs/php_errors.log');

/**
 * Установите кодировку по умолчанию
 * После установки этого параметра мы не должны устанавливать кодировку
 * в следующих функциях: mb_substr, mb_strlen и т.д...
 */
if (function_exists('mb_internal_encoding'))
    mb_internal_encoding('UTF-8');

/** /SET PHP SETTINGS */

/** FINAL LOADING */
if (isInstall()) {

    if (get_magic_quotes_gpc()) {
        strips($_GET);
        strips($_POST);
        strips($_COOKIE);
        strips($_REQUEST);
        if (isset($_SERVER['PHP_AUTH_USER'])) strips($_SERVER['PHP_AUTH_USER']);
        if (isset($_SERVER['PHP_AUTH_PW'])) strips($_SERVER['PHP_AUTH_PW']);
    }
    /** Secure checking */
    Protect::checkIpBan();
    if (Config::read('anti_ddos', '__secure__') == 1) Protect::antiDdos();
    if (Config::read('antisql', '__secure__') == 1) Protect::antiSQL();
    if (!isset($_SESSION['user']['name'])) {
        /*
        * Auto login... if user during a previos
        * visit set AUTOLOGIN option
        */
        /* * Автоматический вход... если пользователь во время предыдущего посещения * установил опцию АВТОВХОД */
        if (isset($_COOKIE['autologin']))
            UserAuth::autoLogin();
        /*
        * if user is autorizet set
        * last time visit
        * This information store in <DataBase>.users
        */
        /*
        * * если пользователь авторизован, установите
        * время последнего посещения
        * Эта информация хранится в <Базе данных>.users
        */
        UserAuth::setTimeVisit();
    }
}