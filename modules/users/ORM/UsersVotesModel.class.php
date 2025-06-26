<?php
/**
* @project    DarsiPro CMS
* @package    UsersVotes Model
* @url        https://darsi.pro
*/


namespace UsersModule\ORM;

class UsersVotesModel extends \OrmModel
{
    public $Table  = 'users_votes';

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