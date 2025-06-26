<?php
/**
* @project    DarsiPro CMS
* @package    Stat Model
* @url        https://darsi.pro
*/


namespace StatModule\ORM;

class StatModel extends \OrmModel
{
    public $Table = 'stat';

    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'author_id',
        ),
        'category' => array( // Deprecated, because not supporting multiple categories
            'model' => 'StatCategories',
            'type' => 'has_one',
            'foreignKey' => 'category_id',
        ),
        'categories' => array(
            'model' => 'StatCategories',
            'type' => 'has_many',
            'foreignKey' => 'this.category_id',
        ),
        'comments_' => array(
            'model' => 'StatComments',
            'type' => 'has_many',
            'foreignKey' => 'entity_id',
        ),
        'attaches' => array(
            'model' => 'StatAttaches',
            'type' => 'has_many',
            'foreignKey' => 'entity_id',
        ),
    );

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
                    'module' => 'stat',
                    'text' => __('stat',true,'stat'),
                    'count' => intval($result),
                    'url' => get_url('/stat/user/' . $user_id),
                );

                return array($res);
            }
        }
        return false;
    }
}