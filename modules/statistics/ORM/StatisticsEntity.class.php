<?php
/**
* @project    DarsiPro CMS
* @package    Stat Entity
* @url        https://darsi.pro
*/


namespace StatisticsModule\ORM;

class StatisticsEntity extends \OrmEntity
{

    protected $id;
    protected $visits;
    protected $date;
    protected $views;
    protected $bot_views;
    protected $other_site_visits;


    public function save()
    {
        $params = array(
            'date' => $this->date,
            'views' => $this->views,
            'visits' => $this->visits,
            'bot_views' => serialize($this->bot_views),
            'other_site_visits' => intval($this->other_site_visits)
        );
        if ($this->id) $params['id'] = $this->id;
        
        return (getDB()->save('statistics', $params));
    }
    
    
    public function getBot_views() {
        if (is_string($this->bot_views))
            $this->bot_views = unserialize($this->bot_views);
        return $this->bot_views;
    }


    public function delete()
    {
        getDB()->delete('statistics', array('id' => $this->id));
    }


    public function __getAPI() {
        return array(
            'id' => $this->id,
            'date' => $this->date,
            'views' => $this->views,
            'visits' => $this->visits,
            'bot_views' => $this->getBot_views(),
            'other_site_visits' => intval($this->other_site_visits)
        );
    }
}