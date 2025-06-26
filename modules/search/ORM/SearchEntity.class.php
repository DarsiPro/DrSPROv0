<?php
/**
* @project    DarsiPro CMS
* @package    Search Entity
* @url        https://darsi.pro
*/


namespace SearchModule\ORM;

class SearchEntity extends \OrmEntity
{

    protected $id;
    protected $index;
    protected $entity_id;
    protected $entity_table;
    protected $entity_view;
    protected $module;
    protected $date = null;




    public function save()
    {
        $params = array(
            'index' => $this->index,
            'entity_id' => intval($this->entity_id),
            'entity_table' => $this->entity_table,
            'date' => $this->date,
            'entity_view' => $this->entity_view,
            'module' => $this->module,
        );
        if ($this->id) $params['id'] = $this->id;

        return (getDB()->save('search_index', $params));
    }



    public function delete()
    {
        getDB()->delete('search_index', array('id' => $this->id));
    }



    public function __getAPI() {
        return array(
            'id' => $this->id,
            'index' => $this->index,
            'entity_id' => $this->entity_id,
            'entity_table' => $this->entity_table,
            'date' => $this->date,
            'entity_view' => $this->entity_view,
            'module' => $this->module,
        );
    }


}