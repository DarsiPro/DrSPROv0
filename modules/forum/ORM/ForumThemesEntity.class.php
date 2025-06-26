<?php
/**
* @project    DarsiPro CMS
* @package    Themes Entity
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumThemesEntity extends \OrmEntity
{

    protected $id;
    protected $title;
    protected $id_author;
    protected $time;
    protected $id_last_author;
    protected $last_post;
    protected $id_forum;
    protected $locked;
    protected $posts;
    protected $views;
    protected $important;
    protected $description;
    protected $group_access;
    protected $first_top;

    // Optional(not in db) and TODO
    private $first_post;

    public function save()
    {
        $params = array(
            'title'             => $this->title,
            'id_author'         => intval($this->id_author),
            'time'                 => $this->time,
            'id_last_author'     => intval($this->id_last_author),
            'last_post'         => $this->last_post,
            'id_forum'             => intval($this->id_forum),
            'locked'             => intval($this->locked),
            'posts'             => intval($this->posts),
            'views'             => intval($this->views),
            'important'         => (!empty($this->important)) ? '1' : new \Expr("'0'"),
            'description'         => $this->description,
            'group_access'         => (is_array($this->group_access) && count($this->group_access) == 1 && $this->group_access[0] !== '')
                                    ? intval($this->group_access[0])
                                    : implode('.', (array)$this->group_access),
            'first_top'         => (!empty($this->first_top)) ? '1' : new \Expr("'0'"),
        );
        if ($this->id) $params['id'] = $this->id;

        return (getDB()->save('themes', $params));
    }


    public function getGroup_access()
    {
        $out = (is_array($this->group_access)) ? $this->group_access : explode('.', $this->group_access);
        foreach ($out as $k => $v) if ('' === $v) unset($out[$k]);
        return $out;
    }


    public function getId_last_post()
    {
        if ($this->id) {
            $res = getDB()->query("SELECT id FROM `" . getDB()->getFullTableName('posts') . "`
                WHERE id_theme = " . intval($this->id) . " ORDER BY time DESC, id DESC LIMIT 1;");
            if ($res && is_array($res) && count($res) > 0 && isset($res[0]['id'])) return $res[0]['id'];
        }
        return null;
    }
    
    // TODO: сделано так, т.к. нет возможности рекурсивного использования биндов в \ORM
    public function getFirst_post()
    {
        if (!empty($this->first_post))
            return $this->first_post;
        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $postsModel->bindModel('attacheslist');
        $posts = $postsModel->getCollection(array(
            'id_theme' => $this->id,
        ), array(
            'order' => 'time ASC, id ASC',
            'limit' => 1,
        ));
        
        if (!empty($posts)) {
            $this->first_post = $posts[0];
            return $posts[0];
        } else
            return null;
    }


    public function delete()
    {
        getDB()->delete('themes', array('id' => $this->id));
    }

    public function __getAPI() {

        if (
            !\ACL::turnUser(array('forum', 'view_forums_list')) ||
            (!\ACL::turnUser(array('forum', 'view_forums')) &&
            !\ACL::turnUser(array('forum', 'view_forums', 'forum.'.$this->id_forum)))
        )
            return array();

        return array(
            'id' => $this->id,
            'title' => $this->title,
            'id_author' => $this->id_author,
            'time' => $this->time,
            'id_last_author' => $this->id_last_author,
            'last_post' => $this->last_post,
            'id_forum' => $this->id_forum,
            'locked' => $this->locked,
            'posts' => $this->posts,
            'views' => $this->views,
            'important' => $this->important,
            'description' => $this->description,
            'group_access' => $this->group_access,
            'first_top' => $this->first_top,
        );
    }

}