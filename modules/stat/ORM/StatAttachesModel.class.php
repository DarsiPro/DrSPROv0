<?php
/**
* @project    DarsiPro CMS
* @package    StatAttaches Model
* @url        https://darsi.pro
*/


namespace StatModule\ORM;

class StatAttachesModel extends \OrmModel
{

    public $Table = 'stat_attaches';



    public function getByEntity($entity)
    {
        $params['entity_id'] = $entity->getId();
        $data = $this->getMapper()->getCollection($params);
        return $data;
    }


}