<?php
/**
* @project    DarsiPro CMS
* @package    Pages Entity
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumPollsEntity extends \OrmEntity
{

    protected $id;
    protected $theme_id;
    protected $variants;
    protected $voted_users;
    protected $question;



    public function save()
    {
        $params = array(
            'theme_id' => intval($this->theme_id),
            'variants' => $this->variants,
            'voted_users' => $this->voted_users,
            'question' => $this->question,
        );
        if ($this->id) $params['id'] = $this->id;

        $id = getDB()->save('polls', $params);
        if($id) $this->setId($id);
        return ($id);
    }



    public function delete()
    {
        getDB()->delete('polls', array('id' => $this->id));
    }

    public function __getAPI() {

        if (
            !\ACL::turnUser(array('forum', 'view_forums_list')) ||
            !\ACL::turnUser(array('forum', 'view_forums')) ||
            !\ACL::turnUser(array('forum', 'view_themes'))
        )
            return array();

        return array(
            'id' => $this->id,
            'theme_id' => $this->theme_id,
            'variants' => $this->variants,
            'voted_users' => $this->voted_users,
            'question' => $this->question,
        );
    }

}