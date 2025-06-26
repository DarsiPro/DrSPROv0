<?php

$output    = '';
$conf_pach = dirname(__FILE__).'/config.json';
$config = json_decode(file_get_contents($conf_pach), true);

$Viewer = new Viewer_Manager;
$template_settings = file_get_contents(dirname(__FILE__).'/template/settings.html');

$template_view_path = dirname(__FILE__).'/template/index.html';
$template_view = file_get_contents($template_view_path);

$arr_default = array(
    "min_word" => "4",
    "min_repeat" => "3",
    "ignoring" => "",
    "ignoring_hide" => "nbsp,amp,quot,"
);
$config = array_merge($arr_default, $config);

if (isset($_POST['min_word'])) {
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


