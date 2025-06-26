<?php
/**
* @project    DarsiPro CMS
* @package    NewsSections Entity
* @url        https://darsi.pro
*/


namespace NewsModule\ORM;

class NewsCategoriesEntity extends \OrmEntity
{

    protected $id;
    protected $parent_id;
    protected $announce;
    protected $title;
    protected $view_on_home;
    protected $no_access;


    public function __getAPI() {
        return array(
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'announce' => $this->announce,
            'title' => $this->title,
            'view_on_home' => $this->view_on_home,
            'no_access' => $this->no_access,
        );
    }

}