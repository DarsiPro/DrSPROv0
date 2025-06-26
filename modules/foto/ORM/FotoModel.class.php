<?php
/**
* @project    DarsiPro CMS
* @package    Foto Model
* @url        https://darsi.pro
*/


namespace FotoModule\ORM;

class FotoModel extends \OrmModel
{
    public $Table = 'foto';

    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'author_id',
        ),
        'category' => array( // Deprecated, because not supporting multiple categories
            'model' => 'FotoCategories',
            'type' => 'has_one',
            'foreignKey' => 'category_id',
        ),
        'categories' => array(
            'model' => 'FotoCategories',
            'type' => 'has_many',
            'foreignKey' => 'this.category_id',
        ),
    );



    public function getNextPrev($id)
    {
        $records = array('prev' => array(), 'next' => array());
        $prev = getDB()->select($this->Table, DB_FIRST, array('cond' => array('`id` < ' . $id), 'limit' => 1, 'order' => '`id` DESC'));
        if (!empty($prev[0])) $records['prev'] = new FotoEntity($prev[0]);
        $next = getDB()->select($this->Table, DB_FIRST, array('cond' => array('`id` > ' . $id), 'limit' => 1, 'order' => '`id`'));
        if (!empty($next[0])) $records['next'] = new FotoEntity($next[0]);


        return $records;
    }

    /**
     * @param $user_id
     * @return array|bool
     */
    function getUserStatistic($user_id) {
        $user_id = intval($user_id);
        if ($user_id > 0) {
            $result = $this->getTotal(array('cond' => array('author_id' => $user_id)));
            if ($result) {
                $res = array(
                    'module' => 'foto',
                    'text' => __('foto',true,'foto'),
                    'count' => intval($result),
                    'url' => get_url('/foto/user/' . $user_id),
                );

                return array($res);
            }
        }
        return false;
    }
}