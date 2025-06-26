<?php
/**
* @project    DarsiPro CMS
* @package    Loads Model
* @url        https://darsi.pro
*/


namespace LoadsModule\ORM;

class LoadsModel extends \OrmModel
{
    public $Table = 'loads';

    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'author_id',
        ),
        'category' => array( // Deprecated, because not supporting multiple categories
            'model' => 'LoadsCategories',
            'type' => 'has_one',
            'foreignKey' => 'category_id',
        ),
        'categories' => array(
            'model' => 'LoadsCategories',
            'type' => 'has_many',
            'foreignKey' => 'this.category_id',
        ),
        'comments_' => array(
            'model' => 'LoadsComments',
            'type' => 'has_many',
            'foreignKey' => 'entity_id',
        ),
        'attaches' => array(
            'model' => 'LoadsAttaches',
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
                    'module' => 'loads',
                    'text' => __('loads',true,'loads'),
                    'count' => intval($result),
                    'url' => get_url('/loads/user/' . $user_id),
                );

                return array($res);
            }
        }
        return false;
    }
}