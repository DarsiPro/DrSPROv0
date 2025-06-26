<?php
/**
* @project    DarsiPro CMS
* @package    Chat Entity
* @url        https://darsi.pro
*/


namespace ChatModule\ORM;

class ChatEntity extends \OrmEntity
{

    protected $id;
    protected $login;
    protected $message;
    protected $data;
    protected $ip;




    public function save()
    {
        $params = array(
            'title' => $this->title,
            'login' => $this->login,
            'message' => $this->message,
            'date' => $this->date,
            'ip' => $this->ip,
        );
        if ($this->id) $params['id'] = $this->id;
    }



    public function delete()
    {
    }


    /**
     * @param $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

}