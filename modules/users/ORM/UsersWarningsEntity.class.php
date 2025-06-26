<?php
/**
* @project    DarsiPro CMS
* @package    UsersWarnings Entity
* @url        https://darsi.pro
*/


namespace UsersModule\ORM;

class UsersWarningsEntity extends \OrmEntity
{

    protected $id;
    protected $user_id;
    protected $admin_id;
    protected $cause;
    protected $date;
    protected $points;




    public function save()
    {
        $params = array(
            'user_id' => intval($this->user_id),
            'admin_id' => intval($this->admin_id),
            'cause' => $this->cause,
            'date' => $this->date,
            'points' => intval($this->points),
        );
        if ($this->id) $params['id'] = $this->id;
        
        return (getDB()->save('users_warnings', $params));
    }

    public function delete($id)
    {
        getDB()->delete('users_warnings', array('id' => $id));
    }

    public function __getAPI() {
        return array(
            'id' => $this->id,
            'user_id' => $this->user_id,
            'admin_id' => $this->admin_id,
            'cause' => $this->cause,
            'date' => $this->date,
            'points' => $this->points,
        );
    }

}