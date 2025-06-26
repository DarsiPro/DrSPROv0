<?php
/**
* @project    DarsiPro CMS
* @package    News Entity
* @url        https://darsi.pro
*/


namespace FotoModule\ORM;

class FotoEntity extends \OrmEntity
{
    protected $id;
    protected $title;
    protected $description;
    protected $views;
    protected $date;
    protected $category_id;
    protected $category = null;
    protected $author_id;
    protected $author = null;
    protected $comments;
    protected $commented;
    protected $filename = null;



    public function save()
    {
        $params = array_merge(array(
            'title' => $this->title,
            'description' => $this->description,
            'views' => intval($this->views),
            'date' => $this->date,
            'category_id' => $this->category_id,
            'author_id' => intval($this->author_id),
            'comments' => (!empty($this->comments)) ? intval($this->comments) : 0,
            'commented' => (!empty($this->commented)) ? 1 : 0,
            'filename' => $this->filename,
        ), 
            \DrsAddFields::selectFromArray($this->asArray())
        );

        if ($this->id) $params['id'] = $this->id;

        return (getDB()->save('foto', $params));
    }



    public function delete()
    {
        $commentsModel = \OrmManager::getModelInstance('Comments');
        $commentsModel->deleteByParentId($this->id, 'foto');

        $path_files = ROOT . '/data/files/foto/' . $this->filename;
        $path_images = ROOT . '/data/images/foto/' . $this->filename;
        if (file_exists($path_files)) {
            unlink($path_files);
        } elseif (file_exists($path_images)) {
            unlink($path_images);
        }

        if (\Config::read('use_local_preview', 'foto')) {
            $preview = \Config::read('use_preview', 'foto');
            $size_x = \Config::read('img_size_x', 'foto');
            $size_y = \Config::read('img_size_y', 'foto');
        } else {
            $preview = \Config::read('use_preview');
            $size_x = \Config::read('img_size_x');
            $size_y = \Config::read('img_size_y');
        }
        $path = ROOT.'/data/images/foto/'.$size_x.'x'.$size_y.'/'.$this->filename;
        if (file_exists($path)) unlink($path);

        getDB()->delete('foto', array('id' => $this->id));
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
        $catsModel = \OrmManager::getModelInstance('FotoCategories');
        $cats = explode(',', $this->category_id);
        
        $this->categories = array();

        // Получаем категории, которые уже были загружены.
        $categories = array();
        if (isset($Register['foto_categories'])) $categories = $Register['foto_categories'];

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
        $Register['foto_categories'] = $categories;
        return $this->categories;
    }


    public function __getAPI() {

        if (
            !$this->available ||
            !\ACL::turnUser(array('foto', 'view_list')) ||
            !\ACL::turnUser(array('foto', 'view_materials'))
        )
            return array();

        $categories = $this->getCategories_singly();
        foreach($categories as $category)
            if (\ACL::checkAccessInList($category->getNo_access()))
                return array();

        return array_merge(array(
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'views' => $this->views,
            'date' => $this->date,
            'category_id' => $this->category_id,
            'author_id' => $this->author_id,
            'comments' => $this->comments,
            'commented' => $this->commented,
            'filename' => $this->filename,
        ), 
            \DrsAddFields::selectFromArray($this->asArray())
        );
    }
}