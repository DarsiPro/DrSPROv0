<?php
/**
* @project    DarsiPro CMS
* @package    Posts Entity
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumPostsEntity extends \OrmEntity
{

    protected $id;
    protected $message;
    protected $attaches;
    protected $id_author;
    protected $time;
    protected $edittime;
    protected $id_editor;
    protected $id_theme;
    protected $id_forum;
    protected $locked = null;




    public function save()
    {
        $params = array(
            'message' => $this->message,
            'attaches' => (!empty($this->attaches)) ? '1' : new \Expr("'0'"),
            'id_author' => intval($this->id_author),
            'time' => $this->time,
            'edittime' => $this->edittime,
            'id_editor' => intval($this->id_editor),
            'id_theme' => intval($this->id_theme),
            'id_forum' => intval($this->id_forum),
            'locked' => (!empty($this->locked)) ? '1' : new \Expr("'0'"),
        );
        if ($this->id) $params['id'] = $this->id;

        return (getDB()->save('posts', $params));
    }



    public function delete()
    {
        getDB()->delete('posts', array('id' => $this->id));
    }



    /**
     * @param $comments
     */
    public function setAttaches($attaches)
    {
        $this->attaches = $attaches;
    }



    /**
     * @return array
     */
    public function getAttaches()
       {

        $this->checkProperty('attaches');
           return $this->attaches;
       }



    /**
     * @param $author
     */
    public function setAuthor($author)
       {
           $this->author = $author;
       }



    /**
     * @return object
     */
    public function getAuthor()
    {
        if (!$this->checkProperty('author')) {

            if (!$this->getId_author()) {
                $this->author = \OrmManager::getEntityInstance('users');
            } else {
                $usersModel = \OrmManager::getModelInstance('Users');
                $this->author = $usersModel->getById($this->id_author);
            }
        }
        return $this->author;
    }

    public function __getAPI() {

        if (
            !\ACL::turnUser(array('forum', 'view_forums_list')) ||
            (!\ACL::turnUser(array('forum', 'view_forums')) &&
            !\ACL::turnUser(array('forum', 'view_forums', 'forum.'.$this->id_forum))) ||
            (!\ACL::turnUser(array('forum', 'view_themes')) &&
            !\ACL::turnUser(array('forum', 'view_themes', 'forum.'.$this->id_forum)))
        )
            return array();


        return array(
            'id' => $this->id,
            'message' => $this->message,
            'attaches' => $this->attaches,
            'id_author' => $this->id_author,
            'time' => $this->time,
            'edittime' => $this->edittime,
            'id_editor' => $this->id_editor,
            'id_theme' => $this->id_theme,
            'id_forum' => $this->id_forum,
            'locked' => $this->locked,
        );
    }

}
