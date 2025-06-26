<?php
/**
* @project    DarsiPro CMS
* @package    ForumAttaches Model
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumAttachesModel extends \OrmModel
{
    public $Table = 'forum_attaches';

    protected $RelatedEntities = array(
        'post' => array(
            'model' => 'ForumPosts',
            'type' => 'has_one',
            'foreignKey' => 'post_id',
          ),
        'theme' => array(
            'model' => 'ForumThemes',
            'type' => 'has_one',
            'foreignKey' => 'theme_id',
        ),
        'user' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'user_id',
        ),
    );


}