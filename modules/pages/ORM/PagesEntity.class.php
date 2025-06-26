<?php
/**
* @project    DarsiPro CMS
* @package    Pages Entity
* @url        https://darsi.pro
*/


namespace PagesModule\ORM;

class PagesEntity extends \OrmEntity
{

    protected $id;
    protected $name;
    protected $title;
    protected $template;
    protected $content;
    protected $url;
    protected $meta_title;
    protected $meta_keywords;
    protected $meta_description;
    protected $parent_id;
    protected $path;
    protected $position;
    protected $publish;




    public function save()
    {
        $params = array(
            'name' => $this->name,
            'title' => $this->title,
            'template' => $this->template,
            'content' => $this->content,
            'url' => $this->url,
            'meta_title' => $this->meta_title,
            'meta_keywords' => $this->meta_keywords,
            'meta_description' => $this->meta_description,
            'parent_id' => intval($this->parent_id),
            'path' => $this->path,
            'position' => $this->position,
            'publish' => (!empty($this->publish)) ? '1' : new \Expr("'0'"),
        );
        if ($this->id) $params['id'] = $this->id;
        
        $id = getDB()->save('pages', $params);
        if($id) $this->setId($id);
        return ($id);
    }



    public function delete()
    {
        getDB()->delete('pages', array('id' => $this->id));
    }


    public function __getAPI() {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'template' => $this->template,
            'content' => $this->content,
            'url' => $this->url,
            'meta_title' => $this->meta_title,
            'meta_keywords' => $this->meta_keywords,
            'meta_description' => $this->meta_description,
            'parent_id' => $this->parent_id,
            'path' => $this->path,
            'position' => $this->position,
            'publish' => $this->publish,
        );
    }

}