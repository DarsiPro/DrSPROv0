<?php
/**
* @project    DarsiPro CMS
* @package    NewsAttaches Model
* @url        https://darsi.pro
*/


namespace NewsModule\ORM;

class NewsAttachesModel extends \OrmModel
{

    public $Table = 'news_attaches';



    public function getByEntity($entity)
    {
        $params['entity_id'] = $entity->getId();
        $data = $this->getMapper()->getCollection($params);
        return $data;
    }


}