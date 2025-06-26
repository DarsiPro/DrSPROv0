<?php
/**
* @project    DarsiPro CMS
* @package    ForumCat Model
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumCatModel extends \OrmModel
{
    public $Table = 'forum_cat';

    protected $RelatedEntities = array(
        'forums' => array(
            'model' => 'Forum',
            'type' => 'has_many',
            'foreignKey' => 'in_cat',
          ),
    );


}