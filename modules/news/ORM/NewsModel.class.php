<?php
/**
* @project    DarsiPro CMS
* @package    News Model
* @url        https://darsi.pro
*/


namespace NewsModule\ORM;

class NewsModel extends \OrmModel
{
    public $Table = 'news';

    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'author_id',
        ),
        'category' => array( // Deprecated, because not supporting multiple categories
            'model' => 'NewsCategories',
            'type' => 'has_one',
            'foreignKey' => 'category_id',
        ),
        'categories' => array(
            'model' => 'NewsCategories',
            'type' => 'has_many',
            'foreignKey' => 'this.category_id',
        ),
        'comments_' => array(
            'model' => 'NewsComments',
            'type' => 'has_many',
            'foreignKey' => 'entity_id',
        ),
        'attaches' => array(
            'model' => 'NewsAttaches',
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
                    'module' => 'news',
                    'text' => __('news',true,'news'),
                    'count' => intval($result),
                    'url' => get_url('/news/user/' . $user_id),
                );

                return array($res);
            }
        }
        return false;
    }
}