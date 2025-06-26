<?php
/**
* @project    DarsiPro CMS
* @package    Posts Model
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumPostsModel extends \OrmModel
{
    public $Table = 'posts';

    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'id_author',
          ),
        'editor' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'id_editor',
          ),
        'theme' => array(
            'model' => 'ForumThemes',
            'type' => 'has_one',
            'foreignKey' => 'id_theme',
          ),
        'attacheslist' => array(
            'model' => 'ForumAttaches',
            'type' => 'has_many',
            'foreignKey' => 'post_id',
          ),
    );



    public function deleteByTheme($theme_id)
    {
        getDB()->query("DELETE FROM `" . getDB()->getFullTableName('posts') . "` WHERE `id_theme` = '" . $theme_id . "'");
    }

    public function moveToTheme($theme_id, $posts_id)
    {
        $post = getDB()->select('posts', DB_FIRST, array('cond' => array('`id_theme`' => $theme_id), 'limit' => 1, 'order' => 'time ASC'));
        getDB()->query("UPDATE `" . getDB()->getFullTableName('posts') . "` SET `id_theme` = " . $theme_id . " WHERE `id` IN (" . implode(',', (array)$posts_id) . ")");
        if (!empty($post) && is_array($post) && count($post) > 0) {
            $time = strtotime($post[0]['time']);
            $new_time = $time + 1;
            getDB()->query("UPDATE `" . getDB()->getFullTableName('posts') . "` SET `time` = '" . date("Y-m-d H:i:s", $new_time) . "' WHERE `id` IN (" . implode(',', (array)$posts_id) . ") AND `time` < '" . date("Y-m-d H:i:s", $time) . "'");
        }
        getDB()->query("UPDATE `" . getDB()->getFullTableName('forum_attaches') . "` SET `theme_id` = " . $theme_id . " WHERE `post_id` IN (" . implode(',', (array)$posts_id) . ")");
    }
}