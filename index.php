<?php
/**
 * Файл index.php расположен в корне CMS.
 *
 * Содержание:
 *  - 
 *
 * @project     DarsiPro
 * @author      Петров Евгений <email@mail.ru>
 * @package     Entry dot
 * @url         https://darsi.pro
 */


list($usec, $sec) = explode(" ", microtime());
// Установка кодировки для вывода контента.
header('Content-Type: text/html; charset=utf-8');


if (file_exists('install')) {
    $set = array();
    if (file_exists('data/config/__main__.php'))
        $set = include_once ('data/config/__main__.php');
    if (!empty($set) && !empty($set['__db__']['name']))
        die('Before use your site, delete INSTALL dir! <br />Перед использованием удалите папку INSTALL');
    
    header('Location: install');
    die();
}


include_once 'sys/boot.php';













/**
 * Parser URL
 * Get params from URL and launch needed module and action
 * Получаем параметры из URL-адреса и запускаем необходимый модуль и действие
 */
Events::init('before_pather', array());
new DrsUrl($Register);
Events::init('after_pather', array());

?>