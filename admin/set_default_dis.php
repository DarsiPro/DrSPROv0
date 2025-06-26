<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/

include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';




if (isset($_SESSION['set_default_dis'])) {
	unset($_SESSION['set_default_dis']);
	$_SESSION['info_message'] = __('Backup complete');
	redirect('/admin/default_dis.php');
} else {
	
	$template_standart  = glob(ROOT . '/template/' . Config::read('template') . '/css/*.css');
	$template_standart  = array_merge(glob(ROOT . '/template/' . Config::read('template') . '/html/*/*.html'), $template_standart);

	if (is_array($template_standart)) {
		foreach ($template_standart as $file) {
			if (file_exists($file . '.stand')) {
				if (copy($file . '.stand', $file)) {
					unlink($file . '.stand');
				}
			}
		}
	}
	$_SESSION['set_default_dis'] = 1;
	redirect('/admin/set_default_dis.php');
}
	
	

 ?>