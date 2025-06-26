<?php

$output    = '';
$conf_pach = dirname(__FILE__).'/config.json';
$config = json_decode(file_get_contents($conf_pach), true);

$Viewer = new Viewer_Manager;
$template_settings = file_get_contents(dirname(__FILE__).'/templates/settings.html');

$template_view_path = dirname(__FILE__).'/templates/popmat.html';
$template_view = file_get_contents($template_view_path);

$arr_default = array(
    "limit" => "",
    "short_main" => "",
    "short_title" => "",
    "module" => "",
    "sort" => ""
);
$config = array_merge($arr_default, $config);

if (isset($_POST['limit'])) {
    foreach (array_keys($arr_default) as $key) {
        $config[$key] = $_POST[$key];
    }
    file_put_contents($conf_pach, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $f = fopen($template_view_path, "w");
    fwrite($f,$_POST['template']);
} else {
    $output .= $Viewer->parseTemplate($template_settings, array('config' => $config, 'template' => $template_view));
}

?>