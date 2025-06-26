<?php
/**
* @project    DarsiPro CMS
* @package    LoadsAttaches Model
* @url        https://darsi.pro
*/


namespace LoadsModule\ORM;

class LoadsAttachesModel extends \OrmModel
{

    public $Table = 'loads_attaches';



    public function getByEntity($entity)
    {
        $params['entity_id'] = $entity->getId();
        $data = $this->getMapper()->getCollection($params);
        return $data;
    }


}