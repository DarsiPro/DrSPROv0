<?php
/**
* @project    DarsiPro CMS
* @package    Pages Model
* @url        https://darsi.pro
*/


namespace PagesModule\ORM;

class PagesModel extends \OrmModel
{
    public $Table = 'pages';

    private static $pages = null;

    protected $RelatedEntities;


    public function __construct()
    {
        parent::__construct();
        if (!isset(self::$pages)) $this->getPages();

    }


    private function getPages()
    {
        self::$pages = $this->getAllTree(array('id', 'url', 'path'));
    }


    public function buildUrl($page_id, $pages = null, $prefix = '')
    {
        $url = '';
        if ($pages === null) $pages = self::$pages;

        $targ_page = $this->getById($page_id);
        if (empty($targ_page) || !$targ_page->getPublish()) return $page_id;



        $page_path = $targ_page->getPath();
        $ids = explode('.', $page_path);


        foreach ($ids as $k => $v) {
            if ($v == 1 || empty($v)) unset($ids[$k]);
        }



        if (!empty($ids)) {
            foreach ($ids as $id) {
                $need_page = $this->getPageById($id, $pages);
                if ($need_page) $url .= '/' . $need_page->getUrl();
            }
        } else {
            $url = '/';
        }


        $url = (!empty($url)) ? trim($url, '/') . '/' . $targ_page->getUrl() : $page_id;

        return trim($url, '/');
    }


    /**
     * Recursive
     */
    private function getPageById($id, $pages) {

        if (!empty($pages)) {
            foreach ($pages as $page) {

                if ($id == $page->getId()) {
                    return $page;
                }


                $sub = $page->getSub();
                if (empty($sub)) continue;
                $need_page = $this->getPageById($id, $sub);
                if ($need_page) return $need_page;
            }
        }
        return false;
    }


    /**
     * @param $id
     * @return bool
     */
    public function getByUrl($url)
    {
        $page_id = $this->searchInTreeByUrl($url, self::$pages);

        $page = $this->getCollection(array(
            'id' => $page_id,
            'publish' => 1,
        ));

        $page = (!empty($page)) ? $page[0] : false;

        return $page;
    }


    private function searchInTreeByUrl($url, $pages)
    {
        $url = explode('/', $url);

        if (!empty($pages)) {
            foreach ($pages as $page) {

                if ($url[0] == $page->getUrl() || $url[0] == $page->getId()) {

                    if (is_array($url) && count($url) > 1) {
                        unset($url[0]);
                        $url = implode('/', $url);

                        return $this->searchInTreeByUrl($url, $page->getSub());

                    } else {
                        return $page->getId();
                    }
                }
            }
        }
        return false;
    }


    public function getTree($id, $fields = array('`a`.*'))
    {
        if (empty($fields)) $fields = array('`a`.*');
        $params = array(
            'joins' => array(
                array(
                    'table' => 'pages',
                    'alias' => 'b',
                    'type' => 'LEFT',
                    'cond' => array("`b`.`id` = '" . $id . "'"),
                ),
            ),
            'cond' => array("`a`.`path` LIKE CONCAT(`b`.`path`, `b`.`id`,'.%')"),
            'alias' => 'a',
            'order' => '`a`.`path`',
            'fields' => $fields,
        );
        $tree = getDB()->select($this->Table, DB_ALL, $params);

        //if (!empty($tree)) {
        //    foreach($tree as $k => $v) {
        //        $tree[$k] = new PagesEntity($v);
        //    }
        //}

        return $tree;
    }


    public function getAllTree($fields = "*")
    {
        $tree = $this->getCollection(array(
            "`id` != 1",
            "publish" => 1,
        ), $fields);

        if (!empty($tree)) {
            $tree = $this->buildTree($tree);
        }

        return $tree;
    }


    /**
     * Get array with tree ierarhy
     */
    private function buildTree($pages, $tree = array())
    {
        if (!empty($tree)) {
            foreach ($tree as $tk => $tv) {


                $sub = array();
                foreach ($pages as $pk => $pv) {


                    $path = $tv->getPath();
                    if ('.' === $path) $path = '';
                    if ($pv->getPath() === $path . $tv->getId() . '.') {
                        unset($pages [$pk]);
                        $sub[] = $pv;
                    }
                }
                if (!empty($sub)) $sub = $this->buildTree($pages, $sub);
                $tv->setSub($sub);
            }


        } else {
            $lowest = false;
            foreach ($pages as $pk => $pv) {
                $path = $pv->getPath();
                if (false === $lowest || substr_count($path, '.') < $lowest) {
                    $lowest = $path;
                }
            }


            if (false !== $lowest) {
                foreach ($pages as $k => $page) {
                    if ($lowest === $page->getPath()) {
                        unset($pages[$k]);
                        $tree[] = $page;
                    }
                }

                $tree = $this->buildTree($pages, $tree);
            }
        }

        return $tree;
    }



    public function getOtherTrees($id)
    {
        $tree = $this->getById($id);
        if (!empty($tree)) {
            $path = ('.' === $tree->getPath()) ? $tree->getId() : $tree->getPath() . $tree->getId();
            $path .= '.';
            $other = $this->getCollection(array(
                "`path` NOT LIKE '" . $path . "%' AND `id` != '" . $id . "'"
            ));
            return $other;
        }
        return false;
    }


    public function add($params)
    {
        if (!empty($params['parent_id'])) {
            $parent = $this->getById($params['parent_id']);
        }

        if (!empty($parent)) {
            if ('.' === $parent->getPath()) {
                $params['path'] = $parent->getId() . '.';
            } else {
                $params['path'] = $parent->getPath() . $parent->getId() . '.';
            }
        } else {
            $params['path'] = '1.';
        }


        if (isset($params['id'])) unset($params['id']);
        $id = getDB()->save($this->Table, $params);
        return !empty($id) ? $id : false;
    }


    public function delete($id)
    {
        $entity = $this->getById($id);
        if (!empty($entity)) {
            getDB()->delete($this->Table, array(
                "`path` LIKE '" . $entity->getPath() . $entity->getPath() . ".%' OR `id` = '" . $id . "'",
            ));
            return true;
        }
        throw new \Exception('Entity not found');
    }


    public function replace($id, $new_parent_id)
    {
        $new_parent = $this->getById($new_parent_id);
        $replaced = $this->getById($id);
        if (!empty($replaced) && !empty($new_parent)) {


            $old_path_mask = $replaced->getPath() . $replaced->getId() . '.';
            $new_path = ('.' === $new_parent->getPath()) ? null : $new_parent->getPath();
            $new_path_mask = $new_path . $new_parent->getId() . '.';

            $query = "UPDATE `" . getDB()->getFullTableName($this->Table) . "`
                SET `path` = REPLACE(`path`, '" . $old_path_mask . "', '" . $new_path_mask . "')
                WHERE `path` LIKE '" . $old_path_mask . "%'";
            $query2 = "UPDATE `" . getDB()->getFullTableName($this->Table) . "`
                SET `parent_id` = '" . $new_parent_id . "'
                , `path` = '" . $new_path_mask . "'
                WHERE `id` = '" . $id . "'";
            getDB()->query($query);
            getDB()->query($query2);
            return true;
        }
        return false;
    }


    public function getEntitiesByHomePage($latest_on_home)
    {
        $Register = \Register::getInstance();
        $materials = array();
        $sql = '';

        if (in_array('news', $latest_on_home))
        $sql .= "(SELECT `title`, `main`, `date`, `on_home_top`, `id`, `views`, `author_id`, `category_id`, `comments`, `view_on_home`, (SELECT \"news\") AS skey  FROM `"
             . getDB()->getFullTableName('news') . "` "
             . "WHERE `view_on_home` = '1' AND `available` = '1' AND `premoder` = 'confirmed') ";
        if (in_array('loads', $latest_on_home)) {
            if (!empty($sql)) $sql .= 'UNION ';
            $sql .= "(SELECT `title`, `main`, `date`, `on_home_top`, `id`, `views`, `author_id`, `category_id`, `comments`, `view_on_home`, (SELECT \"loads\") AS skey   FROM `"
                 . getDB()->getFullTableName('loads') . "` "
                 . "WHERE `view_on_home` = '1' AND `available` = '1' AND `premoder` = 'confirmed') ";
        }
        if (in_array('stat', $latest_on_home)) {
            if (!empty($sql)) $sql .= 'UNION ';
            $sql .= "(SELECT `title`, `main`, `date`, `on_home_top`, `id`, `views`, `author_id`, `category_id`, `comments`, `view_on_home`, (SELECT \"stat\") AS skey  FROM `"
                 . getDB()->getFullTableName('stat') . "` "
                 . "WHERE `view_on_home` = '1' AND `available` = '1' AND `premoder` = 'confirmed') ";
        }


        if (!empty($sql)) {
            $sql .= 'ORDER BY `on_home_top` DESC, `date` DESC LIMIT ' . $Register['Config']->read('cnt_latest_on_home');
            $materials = getDB()->query($sql);
            $materials_ = array('news' => array(), 'loads' => array(), 'stat' => array());
            if ($materials) {
                foreach ($materials as $key => $mat) {

                    switch ($mat['skey']) {
                        case 'news':
                            $materials_['news'][$key] = $mat;
                            break;
                        case 'stat':
                            $materials_['stat'][$key] = $mat;
                            break;
                        case 'loads':
                            $materials_['loads'][$key] = $mat;
                            break;
                    }
                }
            }

        }
        $materials = $materials_;

        foreach ($materials as $module => &$module_materials) {
            $module_model = \OrmManager::getModelInstance($module);
            $module_model->bindModel('categories');
            $module_materials = $module_model->getAllAssigned($module_materials);
            if (is_array($module_materials) && count($module_materials)) {
                $entity = \OrmManager::getEntityName($module);
                foreach ($module_materials as &$mat) {
                    $mat = new $entity($mat);
                }
            } else {
                $module_materials = array();
            }
        }
        $materials = $materials['news'] + $materials['loads'] + $materials['stat'];

        return $materials;
    }

}