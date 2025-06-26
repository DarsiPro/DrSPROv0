<?php
/**
* @project    DarsiPro CMS
* @package    UsersWarnings Model
* @url        https://darsi.pro
*/


namespace UsersModule\ORM;

class UsersWarningsModel extends \OrmModel
{
    public $Table  = 'users_warnings';

    protected $RelatedEntities = array(
        'user' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'user_id',
        ),
        'admin' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'admin_id',
        ),
    );


    public function deleteUserWarnings($id)
    {
            $votes = $this->getCollection(array('user_id' => $id));
            if (!empty($votes)) {
                    foreach ($votes as $vote) {
                            $vote->delete();
                    }
            }
    }

}