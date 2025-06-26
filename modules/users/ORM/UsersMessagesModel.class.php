<?php
/**
* @project    DarsiPro CMS
* @package    Messages Model
* @url        https://darsi.pro
*/


namespace UsersModule\ORM;

class UsersMessagesModel extends \OrmModel
{
    public $Table = 'messages';

    protected $RelatedEntities = array(
        'touser' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'to_user',
          ),
        'fromuser' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'from_user',
        ),
    );




}