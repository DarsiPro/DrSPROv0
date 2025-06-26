<?php

$output    = '';
$conf_pach = dirname(__FILE__).'/config.json';
$config = json_decode(file_get_contents($conf_pach), true);

$Viewer = new Viewer_Manager;
$template_settings = file_get_contents(dirname(__FILE__).'/template/settings.html');

$template_view_path = dirname(__FILE__).'/template/users.html';
$template_view = file_get_contents($template_view_path);

$arr_default = array(
    "limit" => "",
    "view_banned" => "",
    "usersort" => ""
);
$config = array_merge($arr_default, $config);

if (isset($_POST['limit'])) {
    foreach (array_keys($arr_default) as $key) {
        if ($key=="view_banned") {
            $config[$key] = (!empty($_POST[$key]) ? 1 : 0);
        } else {
            $config[$key] = $_POST[$key];
        }
    }
    file_put_contents($conf_pach, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $f = fopen($template_view_path, "w");
    fwrite($f,$_POST['template']);
    $Cache = new Cache;
    $Cache->remove('pl_users_rating');
} else {
    $output .= $Viewer->parseTemplate($template_settings, array('config' => $config, 'template' => $template_view));
}

?>