<?php
/**
* @project    DarsiPro CMS
* @package    Messages Entity
* @url        https://darsi.pro
*/


namespace UsersModule\ORM;

class UsersMessagesEntity extends \OrmEntity
{

    protected $id;
    protected $to_user;
    protected $from_user;
    protected $sendtime;
    protected $subject;
    protected $message;
    protected $id_rmv;
    protected $viewed;




    public function save()
    {
        $params = array(
            'to_user' => intval($this->to_user),
            'from_user' => intval($this->from_user),
            'sendtime' => $this->sendtime,
            'subject' => $this->subject,
            'message' => $this->message,
            'id_rmv' => intval($this->id_rmv),
            'viewed' => intval($this->viewed),
        );
        if ($this->id) $params['id'] = $this->id;

        return (getDB()->save('messages', $params));
    }



    public function delete()
    {
        getDB()->delete('messages', array('id' => $this->id));
    }


    public function __getAPI() {

        if (
            !isset($_SESSION['user']['name'])
            ||
            !\ACL::turnUser(array('users', 'view_users'))
            ||
            (
            $this->to_user !== $_SESSION['user']['id']
            &&
            $this->from_user !== $_SESSION['user']['id']
            )
        )
            return array();


        return array(
            'id' => $this->id,
            'to_user' => $this->to_user,
            'from_user' => $this->from_user,
            'sendtime' => $this->sendtime,
            'subject' => $this->subject,
            'message' => $this->message,
            'id_rmv' => $this->id_rmv,
            'viewed' => $this->viewed,
        );
    }

}