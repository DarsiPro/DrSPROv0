<?php
/**
* @project    DarsiPro CMS
* @package    Snippets Model
* @url        https://darsi.pro
*/


namespace ORM;

class SnippetsModel extends \OrmModel
{
    public $Table = 'snippets';

    protected $RelatedEntities = array(
    );

    public function getByName($name)
    {
        $entity = getDB()->select($this->Table, DB_FIRST, array(
            'cond' => array(
                'name' => $name
            )
        ));

        if (!empty($entity[0])) {
            $entityClassName = \OrmManager::getEntityNameFromModel(get_class($this));
            $entity = new $entityClassName($entity[0]);
            return (!empty($entity)) ? $entity : false;
        }
        return false;
    }
}