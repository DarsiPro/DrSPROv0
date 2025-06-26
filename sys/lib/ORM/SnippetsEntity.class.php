<?php
/**
* @project    DarsiPro CMS
* @package    Snippets Entity
* @url        https://darsi.pro
*/


namespace ORM;

class SnippetsEntity extends \OrmEntity
{

    protected $id;
    protected $name;
    protected $body;


    public function save()
    {
        $params = array(
            'name' => $this->ips,
            'body' => $this->cookie,
        );

        if ($this->id) $params['id'] = $this->id;
        
        return getDB()->save('snippets', $params);
    }



    public function delete()
    {
        getDB()->delete('snippets', array('id' => $this->id));
    }


}