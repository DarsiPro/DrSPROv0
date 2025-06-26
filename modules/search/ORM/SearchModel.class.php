<?php
/**
* @project    DarsiPro CMS
* @package    News Model
* @url        https://darsi.pro
*/


namespace SearchModule\ORM;

class SearchModel extends \OrmModel
{
    public $Table = 'search_index';

    protected $RelatedEntities = array();


    public function truncateTable()
    {
        getDB()->query("TRUNCATE `" . getDB()->getFullTableName('search_index') . "`");
    }


    public function getSearchResults($search, $limit, $modules)
    {
        $lmsql = '';
        if (is_array($modules)) {
            $lmsql .= '(';
            foreach ($modules as $module) {
                if ($module != $modules[0]) {
                    $lmsql .= ' OR ';
                }
                $lmsql .= '`module` = \''.$module.'\'';
            }
            $lmsql .= ') AND';
        }
        $results = getDB()->query("
            SELECT * FROM `" . getDB()->getFullTableName('search_index') . "`
            WHERE ".$lmsql." MATCH (`index`) AGAINST ('" . $search . "' IN BOOLEAN MODE)
            ORDER BY MATCH (`index`) AGAINST ('" . $search . "' IN BOOLEAN MODE) DESC LIMIT " . $limit);
        if ($results) {
            foreach ($results as $key => $res) {
                $results[$key] = new SearchEntity($res);
            }
        }

        return $results;
    }

    public function getTitleName($module, $id)
    {
        if ($module!='forum') {
            $res = getDB()->query("SELECT `title` FROM `" . getDB()->getFullTableName($module) . "` WHERE `id` LIKE '".$id."'");
        } else {
            $idtheme = getDB()->query("SELECT `id_theme` FROM `" . getDB()->getFullTableName('posts') . "` WHERE `id` LIKE '".$id."'");
            $res = getDB()->query("SELECT `title` FROM `" . getDB()->getFullTableName('themes') . "` WHERE `id` LIKE '".$idtheme[0]['id_theme']."'");
        }
        return (!empty($res[0]) && !empty($res[0]['title'])) ? (string)$res[0]['title'] : 0;
    }
}