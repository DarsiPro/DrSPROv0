<?php
/**
* @project    DarsiPro CMS
* @package    Users Entity
* @url        https://darsi.pro
*/


namespace UsersModule\ORM;

class UsersEntity extends \OrmEntity
{

    protected $id;
    protected $name;
    protected $full_name;
    protected $passw;
    protected $email;
    protected $color;
    protected $state;
    protected $rating;
    protected $url;
    protected $about;
    protected $signature;
    protected $pol;
    protected $byear;
    protected $bmonth;
    protected $bday;
    protected $photo;
    protected $puttime;
    protected $themes;
    protected $posts;
    protected $status;
    protected $locked;
    protected $activation;
    protected $warnings;
    protected $ban_expire;
    protected $template;




    public function save()
    {
        $params = array_merge(array(
            'name' => $this->name,
            'full_name' => $this->full_name,
            'passw' => $this->passw,
            'email' => $this->email,
            'color' => $this->color ? $this->color : '',
            'state' => $this->state ? $this->state : '',
            'rating' => intval($this->rating),
            'url' => (string)$this->url,
            'about' => (string)$this->about,
            'signature' => (string)$this->signature,
            'pol' => (string)$this->pol,
            'byear' => intval($this->byear),
            'bmonth' => intval($this->bmonth),
            'bday' => intval($this->bday),
            'photo' => (string)$this->photo,
            'puttime' => $this->puttime,
            'themes' => intval($this->themes),
            'posts' => intval($this->posts),
            'status' => intval($this->status),
            'locked' => intval($this->locked),
            'activation' => $this->activation,
            'warnings' => intval($this->warnings),
            'ban_expire' => $this->ban_expire ? $this->ban_expire : '0000-00-00 00:00:00',
            'template' => $this->template ? $this->template : '',
        ), 
            \DrsAddFields::selectFromArray($this->asArray())
        );

        if ($this->id) $params['id'] = $this->id;

        return (getDB()->save('users', $params));
    }
    
    public function setAdd_fields($add_fields) {
        foreach($add_fields as $field_name => $field_value)
            $this->$field_name = $field_value;
    }

    public function __getAPI() {

        if (
            !\ACL::turnUser(array('users', 'view_list')) ||
            !\ACL::turnUser(array('users', 'view_users'))
        )
            return array();

        return array_merge(array(
            'id' => $this->id,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'color' => $this->color,
            'state' => $this->state,
            'rating' => $this->rating,
            'url' => $this->url,
            'about' => $this->about,
            'signature' => $this->signature,
            'pol' => $this->pol,
            'byear' => $this->byear,
            'bmonth' => $this->bmonth,
            'bday' => $this->bday,
            'photo' => $this->photo,
            'puttime' => $this->puttime,
            'themes' => $this->themes,
            'posts' => $this->posts,
            'status' => $this->status,
            'locked' => $this->locked,
            'activation' => $this->activation,
            'warnings' => $this->warnings,
            'ban_expire' => $this->ban_expire,
            'template' => $this->template,
        ), 
            \DrsAddFields::selectFromArray($this->asArray())
        );
    }

}