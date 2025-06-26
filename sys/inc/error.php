<?php

$code = http_response_code();

if (is_numeric($code)) {
    $headers = array(
        '404' => "HTTP/1.0 404 Not Found",
        '403' => "HTTP/1.0 403 Forbidden You don't have permission to access / on this server.",
        '429' => "HTTP/1.1 429 Too Many Requests",
    );
    if (!empty($headers[$code])) header($headers[$code]);
}
include_once dirname(__FILE__).'/../boot.php';

switch ($code) {
    case '429':
        if (Config::read('request_per_second', '__secure__')) {
            header('Retry-After: ' . \Config::read('request_per_second', '__secure__'));
        }
        $html = @file_get_contents(ROOT . '/data/errors/hack.html');
        break;
    case '403':
        $html = @file_get_contents(ROOT . '/data/errors/' . (isset($reason) && $reason == 'ban' ? 'ban.html' : '403.html'));
        break;
    case '404':
        $html = @file_get_contents(ROOT . '/data/errors/404.html');
        break;
    default:
        $html = @file_get_contents(ROOT . '/data/errors/default.html');
}

$Viewer = new Viewer_Manager();

$markers = array();
$markers['code'] = $code;
$markers['site_title'] = Config::read('site_title');
$markers['site_domain'] = $_SERVER['SERVER_NAME'];

echo $Viewer->parseTemplate($html, array('error' => $markers));

?>