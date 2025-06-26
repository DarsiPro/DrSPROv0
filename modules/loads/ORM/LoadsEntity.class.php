<?php
/**
* @project    DarsiPro CMS
* @package    News Entity
* @url        https://darsi.pro
*/


namespace LoadsModule\ORM;

class LoadsEntity extends \OrmEntity
{
    protected $id;
    protected $title;
    protected $main;
    protected $views;
    protected $downloads;
    protected $rate;
    protected $download;
    protected $filename;
    protected $download_url;
    protected $download_url_size;
    protected $date;
    protected $category_id;
    protected $category = null;
    protected $author_id;
    protected $author = null;
    protected $comments;
    protected $comments_ = null;
    protected $attaches = null;
    protected $tags;
    protected $description;
    protected $sourse;
    protected $sourse_email;
    protected $sourse_site;
    protected $commented;
    protected $available;
    protected $view_on_home;
    protected $on_home_top;
    protected $premoder;




    public function save()
    {
        $params = array_merge(array(
            'title' => $this->title,
            'main' => $this->main,
            'views' => intval($this->views),
            'downloads' => intval($this->downloads),
            'rate' => intval($this->rate),
            'download' => strval($this->download),
            'filename' => strval($this->filename),
            'download_url' => $this->download_url,
            'download_url_size' => intval($this->download_url_size),
            'date' => $this->date,
            'category_id' => $this->category_id,
            'author_id' => intval($this->author_id),
            'comments' => (!empty($this->comments)) ? intval($this->comments) : 0,
            'tags' => (is_array($this->tags)) ? implode(',', $this->tags) : $this->tags,
            'description' => $this->description,
            'sourse' => $this->sourse,
            'sourse_email' => $this->sourse_email,
            'sourse_site' => $this->sourse_site,
            'commented' => (!empty($this->commented)) ? '1' : new \Expr("'0'"),
            'available' => (!empty($this->available)) ? '1' : new \Expr("'0'"),
            'view_on_home' => (!empty($this->view_on_home)) ? '1' : new \Expr("'0'"),
            'on_home_top' => (!empty($this->on_home_top)) ? '1' : new \Expr("'0'"),
            'premoder' => (!empty($this->premoder)) ? $this->premoder : 'nochecked',
        ), 
            \DrsAddFields::selectFromArray($this->asArray())
        );

        if ($this->id) $params['id'] = $this->id;

        return (getDB()->save('loads', $params));
    }



    public function delete()
    {
        $attachesModel = \OrmManager::getModelInstance('LoadsAttaches');
        $commentsModel = \OrmManager::getModelInstance('Comments');

        $attachesModel->deleteByParentId($this->id);
        $commentsModel->deleteByParentId($this->id, 'loads');

        if (!empty($this->download) and file_exists(ROOT . '/data/files/loads/' . $this->download)) {
            _unlink(ROOT . '/data/files/loads/' . $this->download);
        }

        getDB()->delete('loads', array('id' => $this->id));
    }



    /**
     * @param $comments
     */
    public function setComments_($comments)
    {
        $this->comments_ = $comments;
    }



    /**
     * @return array
     */
    public function getComments_()
    {

        $this->checkProperty('comments_');
        return $this->comments_;
    }



    /**
     * @param $comments
     */
    public function setAttaches($attaches)
    {
        $this->attaches = $attaches;
    }



    /**
     * @return array
     */
    public function getAttaches()
    {
        $this->checkProperty('attaches');
        return $this->attaches;
    }



    /**
     * @param $author
     */
    public function setAuthor($author)
   {
        $this->author = $author;
   }



    /**
     * @return object
     */
    public function getAuthor()
    {
        if (!$this->checkProperty('author')) {
            if (!$this->getAuthor_id()) {
                $this->author = \OrmManager::getEntityInstance('users');
            } else {
                $this->author = \OrmManager::getModelInstance('Users')->getById($this->author_id);
            }
        }
        return $this->author;
    }



    /**
     * @param $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }


    /**
     * @return array
     */
    public function getCategories_singly()
    {
        // if using category bind
        if (isset($this->categories) && count($this->categories) > 0)
            return $this->categories;
        
        $Register = \Register::getInstance();
        $catsModel = \OrmManager::getModelInstance('LoadsCategories');
        $cats = explode(',', $this->category_id);
        
        $this->categories = array();

        // Получаем категории, которые уже были загружены.
        $categories = array();
        if (isset($Register['loads_categories'])) $categories = $Register['loads_categories'];

        // Оценка необходимости в дополнительных запросах
        foreach ($cats as $n => $cat) {
            // Если категория еще не была загружена, то в массив кладем её id вместо ORM обьекта.
            if (!isset($categories[$cat])) {
                $need_cats[] = $cat;
                $this->categories[] = $cat;
                continue;
            }
            // Если уже загружена, то добавляем её обьект в массив
            $this->categories[] = $categories[$cat];
        }

        // Если есть, что еще нужно загрузить
        if (!empty($need_cats))
            // Загружаем недостающие категории
            if (($need_cats = $catsModel->getCollection(array('`id` IN ('.implode(',',$need_cats).')'))) && !empty($need_cats))
                // Укладываем их в возвращаемый массив, заместо поставленных там ID
                foreach($this->categories as $n => $cat) {
                    // Находим "подставленное ID"
                    if (!is_object($cat) && is_numeric($cat))
                        // Ищем соответствующую по ID категорию
                        foreach($need_cats as $ncat)
                            if ($ncat->getId() == $cat) {
                                // Меняем "подставленное ID" на полноценный объект категории
                                $this->categories[$n] = $ncat;
                                // Полученную категорию запоминаем в регистре, чтобы не проделывать тоже самое при следующей необходимости.
                                $categories[$cat] = $ncat;
                            }

                    // Выявляем категории, которые не удалось получить. (их, как правило, не существует)
                    if (!is_object($this->categories[$n]))
                        $this->categories[$n] = false;
                }

        // Помещаем загруженные категории в общеиспользуемый регистр.
        $Register['loads_categories'] = $categories;
        return $this->categories;
    }


    public function __getAPI() {

        if (
            !$this->available ||
            !\ACL::turnUser(array('loads', 'view_list')) ||
            !\ACL::turnUser(array('loads', 'view_materials'))
        )
            return array();

        $categories = $this->getCategories_singly();
        foreach($categories as $category)
            if (\ACL::checkAccessInList($category->getNo_access()))
                return array();

        return array_merge(array(
            'id' => $this->id,
            'title' => $this->title,
            'main' => $this->main,
            'views' => $this->views,
            'downloads' => $this->downloads,
            'rate' => $this->rate,
            'filename' => $this->filename,
            'download_url' => $this->download_url,
            'demo_url' => $this->demo_url,
            'download_url_size' => $this->download_url_size,
            'date' => $this->date,
            'category_id' => $this->category_id,
            'author_id' => $this->author_id,
            'comments' => $this->comments,
            'tags' => $this->tags,
            'description' => $this->description,
            'sourse' => $this->sourse,
            'sourse_email' => $this->sourse_email,
            'sourse_site' => $this->sourse_site,
            'commented' => $this->commented,
            'available' => $this->available,
            'view_on_home' => $this->view_on_home,
            'on_home_top' => $this->on_home_top,
            'premoder' => $this->premoder,
        ), 
            \DrsAddFields::selectFromArray($this->asArray())
        );
    }
}