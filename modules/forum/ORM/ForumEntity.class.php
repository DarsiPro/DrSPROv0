<?php
/**
* @project    DarsiPro CMS
* @package    Forum Entity
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumEntity extends \OrmEntity
{

    protected $id;
    protected $title;
    protected $description;
    protected $pos;
    protected $in_cat;
    protected $last_theme_id;
    protected $themes;
    protected $posts;
    protected $parent_forum_id;
    protected $lock_posts;
    protected $lock_passwd;




    public function save()
    {
        $params = array(
            'title' => $this->title,
            'description' => (string)$this->description ,
            'pos' => intval($this->pos),
            'in_cat' => intval($this->in_cat),
            'last_theme_id' => intval($this->last_theme_id),
            'themes' => intval($this->themes),
            'posts' => intval($this->posts),
            'parent_forum_id' => intval($this->parent_forum_id),
            'lock_posts' => intval($this->lock_posts),
            'lock_passwd' => $this->lock_passwd,
        );
        if ($this->id) $params['id'] = $this->id;
        return (getDB()->save('forums', $params));
    }



    public function delete()
    {
        getDB()->delete('forums', array('id' => $this->id));
    }


    public function __getAPI() {


        if (
            !\ACL::turnUser(array('forum', 'view_forums_list'))
        )
            return array();

        return array(
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'pos' => $this->pos,
            'in_cat' => $this->in_cat,
            'last_theme_id' => $this->last_theme_id,
            'themes' => $this->themes,
            'posts' => $this->posts,
            'parent_forum_id' => $this->parent_forum_id,
            'lock_posts' => $this->lock_posts,
        );
    }

}