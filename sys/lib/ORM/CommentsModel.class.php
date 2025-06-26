<?php
/**
* @project    DarsiPro CMS
* @package    Comments Model
* @url        https://darsi.pro
*/


namespace ORM;

class CommentsModel extends \OrmModel
{

    public $Table = 'comments';
    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'user_id',
          ),
        'parent_entity' => array(
            'model' => 'this.module',
            'type' => 'has_one',
            'foreignKey' => 'entity_id',
        ),
    );



    public function getByEntity($entity)
    {
        $this->bindModel('Users');
        $params['entity_id'] = $entity->getId();
        $news = $this->getCollection($params);
        return $news;
    }

}