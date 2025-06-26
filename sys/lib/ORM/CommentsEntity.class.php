<?php
/**
* @project    DarsiPro CMS
* @package    Comments Entity
* @url        https://darsi.pro
*/


namespace ORM;

class CommentsEntity extends \OrmEntity
{
    protected $id;
    protected $parent_id;
    protected $entity_id;
    protected $user_id;
    protected $name;
    protected $message;
    protected $ip;
    protected $mail;
    protected $date;
    protected $editdate;
    protected $module;
    protected $premoder;


    public function save()
    {
        $data = array_merge(array(
            'parent_id' => intval($this->parent_id),
            'entity_id' => intval($this->entity_id),
            'user_id' => intval($this->user_id),
            'name' => $this->name,
            'message' => $this->message,
            'ip' => $this->ip,
            'mail' => $this->mail,
            'date' => $this->date,
            'editdate' => $this->editdate,
            'module' => $this->module,
            'premoder' => (!empty($this->premoder) && in_array($this->premoder, array('nochecked', 'rejected', 'confirmed'))) ? $this->premoder : 'nochecked',
        ), 
            \DrsAddFields::selectFromArray($this->asArray())
        );

        if($this->id) $data['id'] = $this->id;

        return (getDB()->save('comments', $data));
    }
    
    public function setAdd_fields($add_fields) {
        foreach($add_fields as $field_name => $field_value)
            $this->$field_name = $field_value;
    }

    public function delete()
    {
        getDB()->delete('comments', array('id' => $this->id));
    }

    public function __getAPI() {

        if (
            !\ACL::turnUser(array($this->module, 'view_list')) ||
            !\ACL::turnUser(array($this->module, 'view_materials'))
        )
            return array();

        return array_merge(array(
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'entity_id' => $this->entity_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'message' => $this->message,
            'ip' => $this->ip,
            'mail' => $this->mail,
            'date' => $this->date,
            'editdate' => $this->editdate,
            'module' => $this->module,
            'premoder' => $this->premoder,
        ), 
            \DrsAddFields::selectFromArray($this->asArray())
        );
    }
}