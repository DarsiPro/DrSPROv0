<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/


/**
* repair forums, themes, messages count
*
*/

include_once R.'admin/inc/adm_boot.php';

$forums = $DB->select('forums', DB_ALL, array());
if (!empty($forums)) {
    foreach ($forums as $forum) {
        $themes = $DB->select('themes', DB_ALL, array('cond' => array('id_forum' => $forum['id'])));
        if (!empty($themes)) {
            foreach ($themes as $theme) {
                $DB->query("UPDATE `" . $DB->getFullTableName('themes') . "` SET
                                `posts` = (SELECT COUNT(*) FROM `" . $DB->getFullTableName('posts') . "` WHERE `id_theme` = '" . $theme['id'] . "')-1
                                WHERE `id` = '" . $theme['id'] . "'");
            }
        }
        $DB->query("UPDATE `" . $DB->getFullTableName('forums') . "` SET
                        `themes` = (SELECT COUNT(*) FROM `" . $DB->getFullTableName('themes') . "` WHERE `id_forum` = '" . $forum['id'] . "')
                        , `posts` = (SELECT SUM(posts) FROM `" . $DB->getFullTableName('themes') . "` WHERE `id_forum` = '" . $forum['id'] . "')
                        WHERE `id` = '" . $forum['id'] . "'");
    }
}

$_SESSION['message'][] = __('All done');

redirect('/admin/');