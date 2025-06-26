<?php
/**
* @project    DarsiPro CMS
* @package    Chat Model
* @url        https://darsi.pro
*/


namespace ChatModule\ORM;

class ChatModel extends \OrmModel
{
    public $Table = '';

    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'author_id',
          ),
    );

}