<?php
/**
* @project    DarsiPro CMS
* @package    UsersVotes Entity
* @url        https://darsi.pro
*/


namespace UsersModule\ORM;

class UsersVotesEntity extends \OrmEntity
{

    protected $id;
    protected $from_user;
    protected $to_user;
    protected $comment;
    protected $date;
    protected $points;





    public function save()
    {
        $params = array(
            'from_user' => intval($this->from_user),
            'to_user' => intval($this->to_user),
            'comment' => $this->comment,
            'date' => $this->date,
            'points' => intval($this->points),
        );
        if ($this->id) $params['id'] = $this->id;
        
        return (getDB()->save('users_votes', $params));
    }


    public function delete()
    {
        getDB()->delete('users_votes', array('id' => $this->id));
    }

    public function __getAPI() {
        return array(
            'id' => $this->id,
            'from_user' => $this->from_user,
            'to_user' => $this->to_user,
            'comment' => $this->comment,
            'date' => $this->date,
            'points' => $this->points,
        );
    }

}