<?php
/**
* @project    DarsiPro CMS
* @package    ForumCat Entity
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumCatEntity extends \OrmEntity
{

    protected $id;
    protected $title;
    protected $previev_id;




    public function save()
    {
        $params = array(
            'title' => $this->title,
            'previev_id ' => intval($this->preview_id),
        );
        if ($this->id) $params['id'] = $this->id;
        
        return (getDB()->save('forum_cat', $params));
    }



    public function delete()
    {
        getDB()->delete('forum_cat', array('id' => $this->id));
    }


    public function __getAPI() {


        if (
            !\ACL::turnUser(array('forum', 'view_forums_list'))
        )
            return array();

        return array(
            'id' => $this->id,
            'title' => $this->title,
            'previev_id ' => $this->preview_id,
        );
    }

}