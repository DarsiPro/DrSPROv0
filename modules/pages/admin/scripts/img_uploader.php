<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/sys/boot.php';
include_once R.'admin/inc/adm_boot.php';

// files storage folder
$dir = ROOT . '/data/images/pages/';
if (!file_exists($dir)) mkdir($dir, 0755, true);

if (isImageFile($_FILES['file'])) {

    // setting file's mysterious name
    $filename = md5(date('YmdHis')).'.jpg';
    $file = $dir.$filename;

    // copying
    copy($_FILES['file']['tmp_name'], $file);

    // displaying file
    $array = array(
        'filelink' => WWW_ROOT . '/data/images/pages/' . $filename,
    );
    echo stripslashes(json_encode($array));
}