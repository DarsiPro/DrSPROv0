<?php
/**
* @project    DarsiPro CMS
* @package    Themes Model
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumThemesModel extends \OrmModel
{
    public $Table = 'themes';

    protected $RelatedEntities = array(
        'forum' => array(
            'model' => 'Forum',
            'type' => 'has_one',
            'foreignKey' => 'id_forum',
          ),
        'poll' => array(
            'model' => 'ForumPolls',
            'type' => 'has_many',
            'foreignKey' => 'theme_id',
          ),
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'id_author',
        ),
        'last_author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'id_last_author',
        ),
        'postslist' => array(
            'model' => 'ForumPosts',
            'type' => 'has_many',
            'foreignKey' => 'id_theme',
        ),
    );


}