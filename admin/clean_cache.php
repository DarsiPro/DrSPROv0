<?php
/**
* @project    DarsiPro CMS
* @package    Clean cache
* @url        https://darsi.pro
*/

include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';

// Keep it simple, stupid! Будь проще, тупица!
_unlink(ROOT . '/sys/cache/', True);

$previews = glob(ROOT.'/data/images/*/*', GLOB_ONLYDIR);
foreach ($previews as $attach) {
    _unlink($attach, True);
}

$meta_file = ROOT . '/sys/tmp/search/meta.dat';
if (file_exists($meta_file)) unlink($meta_file);

$_SESSION['message'][] = __('Cache is cleared');

redirect(getReferer(), true);