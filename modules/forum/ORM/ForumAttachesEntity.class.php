<?php
/**
* @project    DarsiPro CMS
* @package    ForumAttaches Entity
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumAttachesEntity extends \OrmEntity
{

    protected $id;
    protected $post_id;
    protected $user_id;
    protected $attach_number;
    protected $filename;
    protected $size;
    protected $date;
    protected $is_image;



    public function save()
    {
        $params = array(
            'post_id' => intval($this->post_id),
            'user_id' => intval($this->user_id),
            'attach_number' => intval($this->attach_number),
            'filename' => $this->filename,
            'size' => intval($this->size),
            'date' => $this->date,
            'is_image' => (!empty($this->is_image)) ? '1' : new \Expr("'0'"),
        );
        if ($this->id) $params['id'] = $this->id;

        return (getDB()->save('forum_attaches', $params));
    }



    public function delete()
    {
        getDB()->delete('forum_attaches', array('id' => $this->id));
    }


    public function __getAPI() {

        if (
            !\ACL::turnUser(array('forum', 'view_forums_list')) ||
            !\ACL::turnUser(array('forum', 'view_forums')) ||
            !\ACL::turnUser(array('forum', 'view_themes')) ||
            !\ACL::turnUser(array('forum', 'download_files'))
        )
            return array();


        return array(
            'id' => $this->id,
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'attach_number' => $this->attach_number,
            'filename' => $this->filename,
            'size' => $this->size,
            'date' => $this->date,
            'is_image' => $this->is_image,
        );
    }

}