<?php
/**
* @project    DarsiPro CMS
* @package    Loads Module
* @url        https://darsi.pro
*/

namespace LoadsModule;

Class ActionsHandler extends \Module {

    /**
     * @module_title  title of module
     */
    public $module_title = 'Каталог файлов';

    /**
     * @template  layout for module
     */
    public $template = 'loads';

    /**
     * @module module indentifier
     */
    public $module = 'loads';

    public $premoder_types = array('rejected', 'confirmed');

    /**
     * @module module indentifier
     */
    public $attached_files_path = 'loads';

    public function __construct($params) {
        parent::__construct($params);

        $this->attached_files_path = ROOT . $this->getFilesPath();
        $this->setModel();
        
        
    }

    /**
     * default action ( show main page )
     */
    public function index($tag = null) {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);

        //формируем блок со списком  разделов
        $this->_getCatsTree();


        if ($this->cached && $this->Cache->check($this->cacheKey)) {
            $source = $this->Cache->read($this->cacheKey);
            return $this->_view($source);
        }

        $where = filter();
        // we need to know whether to show hidden
        if (!\ACL::turnUser(array('__other__', 'can_see_hidden')))
            $where['available'] = 1;
        if (!empty($tag)) {
            $tag = rawurldecode($tag);
            $tag = getDB()->escape($tag);
            $where[] = "`tags` LIKE '%{$tag}%'";
        }
        if (!\ACL::turnUser(array('__other__', 'can_premoder')))
            $where['premoder'] = 'confirmed';

        $total = $this->Model->getTotal(array('cond' => $where));
        $perPage = intval(\Config::read('per_page', $this->module));
        if ($perPage < 1)
            $perPage = 10;
        list ($pages, $page) = pagination($total, $perPage, $this->getModuleURL('index/'.$tag));
        $this->Register['pages'] = $pages;
        $this->Register['page'] = $page;
        //$this->page_title .= ' (' . $page . ')';



        $navi = array();
        $navi['add_link'] = (\ACL::turnUser(array($this->module, 'add_materials'))) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
        $navi['navigation'] = $this->_buildBreadCrumbs();
        $navi['pagination'] = $pages;

        $cntPages = ceil($total / $perPage);
        $recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
        $firstOnPage = ($page - 1) * $perPage + 1;
        $lastOnPage = $firstOnPage + $recOnPage - 1;

        $navi['meta'] = __('Count all material') . ' ' . $total . '. ' . ($total > 1 ? __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage : '');
        $this->_globalize($navi);


        if ($total <= 0) {
            $html = $this->render('list.html');
            return $this->_view($html);
        }


        $params = array(
            'page' => $page,
            'limit' => $perPage,
            'order' => getOrderParam(__CLASS__),
        );


        $this->Model->bindModel('attaches');
        $this->Model->bindModel('author');
        $this->Model->bindModel('categories');
        $records = $this->Model->getCollection($where, $params);

        if (count($records) > 0) {
            $records = $this->AddFields->mergeSelect($records);
        }

        // create markers
        foreach ($records as $entity) {
            $this->Register['current_vars'] = $entity;

            $entity->setModer_panel($this->_getAdminBar($entity));
            $entry_url = get_url(entryUrl($entity, $this->module));
            $entity->setEntry_url($entry_url);


            // Cut announce
            $announce = \PrintText::getAnnounce($entity->getMain(), '', \Config::read('announce_lenght', $this->module), $entity);


            // replace image tags in text
            $attaches = $entity->getAttaches();
            $img_attaches = array();
            $markers = array();
            $i = 0;
            if (!empty($attaches) && count($attaches) > 0) {
                foreach ($attaches as $attach) {
                    if ($attach->getIs_image() == '1') {
                        //Что бы не вызывать функции лишний раз.
                        $numder = $attach->getAttach_number();
                        $filename = $attach->getFilename();
                        $fullimg = $this->markerImageAttach($filename, $numder);
                        $smallimg = $this->markerSmallImageAttach($filename, $numder);

                        $announce = $this->insertImageAttach($announce, $filename, $numder);
                        $markers['url_'.$numder] = $fullimg;
                        $markers['small_url_'.$numder] = $smallimg;
                        $img_attaches[$i]['full'] = $fullimg;
                        $img_attaches[$i]['small'] = $smallimg;
                        $i++;
                    }
                }
            }

            $markers['attach_all'] = array_slice($img_attaches, 0);

            $max_attaches = \Config::read('max_attaches', $this->module);

            $len = count($img_attaches);
            for ($i = 1; $i <= $max_attaches; $i++) {
                for ($j = 1; $j <= $max_attaches; $j++) {
                    if ($i > $len) $markers['attach_' . $i . '_' . $j] = '';
                    if ($j - $i > $len) $markers['attach_' . $i . '_' . $j] = array_slice($img_attaches, $i, $len);
                    if ($i < $j) $markers['attach_' . $i . '_' . $j] = array_slice($img_attaches, $i, $j - $i);
                }
            }
            $entity->setImg($markers);

            $entity->setAnnounce($announce);

            $category = $entity->getCategories();
            $entity->setCategory($category[0]);
            $category_name = '';
            foreach($category as $n => $cat) {$category_name .= ($n !== 0 ? ', ' : '').h($cat->getTitle());}
            $entity->setCategory_name($category_name);
            $entity->setCategory_url(get_url($this->getModuleURL('category/' . $entity->getCategory_id())));

            $entity->setProfile_url(getProfileUrl($entity->getAuthor_id()));
            if ($entity->getTags())
                $entity->setTags(atrim(explode(',', $entity->getTags())));


            //set users_id that are on this page
            $this->setCacheTag(array(
                'user_id_' . $entity->getAuthor_id(),
                'record_id_' . $entity->getId(),
            ));

        }


        $source = $this->render('list.html', array('entities' => $records));


        //write int cache
        if ($this->cached)
            $this->Cache->write($source, $this->cacheKey, $this->cacheTags);


        return $this->_view($source);
    }

    /**
     * Show materials in category. Category ID must be integer and not null.
     */
    public function category($id = null) {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);

        // \Validate
        if (!empty($id)) {
            $id_ = explode(',',$id);
            foreach ($id_ as $v)
                if (!is_numeric($v))
                    return $this->showMessage(__('Value must be numeric'));
            if (($c = count($id_)) > 1 && count(array_unique($id_)) !== $c)
                return $this->showMessage(__('Some error occurred'));

        }

        if (empty($id) || $id < 1)
            return $this->showMessage(__('Can not find category'));


        $sectionsModel = \OrmManager::getModelInstance($this->module . 'Categories');
        $categories = $sectionsModel->getCatsByIds($id_);
        if (!$categories)
            return $this->showMessage(__('Can not find category'));
        $cat_title = array();
        foreach($categories as $category) {
            if ($category == false || !is_object($category))
                return $this->showMessage(__('Can not find category'));
            if (!\ACL::checkAccessInList($category->getNo_access()))
                $cat_title[] = $category->getTitle();
        }

        if (empty($cat_title))
            return $this->showMessage(__('Permission denied'));

        $cat_title = implode(', ',$cat_title);

        $this->page_title = h($cat_title);


        //формируем блок со списком  разделов
        $this->_getCatsTree($id);


        if ($this->cached && $this->Cache->check($this->cacheKey)) {
            $source = $this->Cache->read($this->cacheKey);
            return $this->_view($source);
        }

        // Выборка материалов в указанных категориях
        $query = '';
        if (count($id_) > 0) {
            foreach($id_ as $n => $catid) {
                if ($n > 0) $query .= " OR ";
                $query .= "LOCATE(',".$catid.",',CONCAT(',',`category_id`,',')) > 0";
            }
            // Увеличиваем множество дочерними категориями.
            $childCats = $sectionsModel->getOneField('id', array('`parent_id` IN('.$id.')'));
            if ($childCats && is_array($childCats) && count($childCats) > 0)
                foreach($childCats as $pcatid)
                    $query .= " OR LOCATE(',".$pcatid.",',CONCAT(',',`category_id`,',')) > 0";
        }
        $where = filter(array($query));

        if (!\ACL::turnUser(array('__other__', 'can_see_hidden'))) {
            $where['available'] = 1;
        }
        if (!\ACL::turnUser(array('__other__', 'can_premoder')))
            $where['premoder'] = 'confirmed';


        $total = $this->Model->getTotal(array('cond' => $where));
        $perPage = intval(\Config::read('per_page', $this->module));
        if ($perPage < 1)
            $perPage = 10;
        list ($pages, $page) = pagination($total, $perPage, $this->getModuleURL('category/' . $id));
        $this->Register['pages'] = $pages;
        $this->Register['page'] = $page;
        //$this->page_title .= ' (' . $page . ')';



        $navi = array();
        $navi['add_link'] = (\ACL::turnUser(array($this->module, 'add_materials'))) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';

        if ($c > 1)
            $navi['navigation'] =  get_link(__('Home'), '/') . __('Separator') . get_link($this->module_title, $this->getModuleURL()) . __('Separator') . $cat_title;
        else
            $navi['navigation'] = $this->_buildBreadCrumbs($id);

        $navi['pagination'] = $pages;

        $cntPages = ceil($total / $perPage);
        $recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
        $firstOnPage = ($page - 1) * $perPage + 1;
        $lastOnPage = $firstOnPage + $recOnPage - 1;

        $navi['meta'] = __('Count material in cat') . ' ' . $total . '. ' . ($total > 1 ? __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage : '');
        $navi['category_name'] = h($cat_title);
        $this->_globalize($navi);


        if ($total <= 0) {
            $html = $this->render('list.html');
            return $this->_view($html);
        }


        $params = array(
            'page' => $page,
            'limit' => $perPage,
            'order' => getOrderParam(__CLASS__),
        );


        $this->Model->bindModel('attaches');
        $this->Model->bindModel('author');
        $this->Model->bindModel('categories', $categories);
        $records = $this->Model->getCollection($where, $params);


        if (count($records) > 0) {
            $records = $this->AddFields->mergeSelect($records);
        }

        // create markers
        foreach ($records as $entity) {
            $this->Register['current_vars'] = $entity;

            $entity->setModer_panel($this->_getAdminBar($entity));
            $entry_url = get_url(entryUrl($entity, $this->module));
            $entity->setEntry_url($entry_url);


            $announce = \PrintText::getAnnounce($entity->getMain(), '', \Config::read('announce_lenght', $this->module), $entity);


            // replace image tags in text
            $attaches = $entity->getAttaches();
            $img_attaches = array();
            $markers = array();
            $i = 0;
            if (!empty($attaches) && count($attaches) > 0) {
                foreach ($attaches as $attach) {
                    if ($attach->getIs_image() == '1') {
                        $announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
                        $markers['url_'.$attach->getAttach_number()] = $this->markerImageAttach($attach->getFilename(), $attach->getAttach_number());
                        $markers['small_url_'.$attach->getAttach_number()] = $this->markerSmallImageAttach($attach->getFilename(), $attach->getAttach_number());
                        $img_attaches[$i]['full'] = $this->markerImageAttach($attach->getFilename(), $attach->getAttach_number());
                        $img_attaches[$i]['small'] = $this->markerSmallImageAttach($attach->getFilename(), $attach->getAttach_number());
                        $i++;
                    }
                }
            }

            $markers['attach_all'] = array_slice($img_attaches, 0);

            $max_attaches = \Config::read('max_attaches', $this->module);

            $len = count($img_attaches);
            for ($i = 1; $i <= $max_attaches; $i++) {
                for ($j = 1; $j <= $max_attaches; $j++) {
                    if ($i > $len) $markers['attach_' . $i . '_' . $j] = '';
                    if ($j - $i > $len) $markers['attach_' . $i . '_' . $j] = array_slice($img_attaches, $i, $len);
                    if ($i < $j) $markers['attach_' . $i . '_' . $j] = array_slice($img_attaches, $i, $j - $i);
                }
            }
            $entity->setImg($markers);

            $entity->setAnnounce($announce);

            $category = $entity->getCategories();
            $entity->setCategory($category[0]);
            $entity->setCategory_url(get_url($this->getModuleURL('category/' . $entity->getCategory_id())));
            $category_name = '';
            foreach($category as $n => $cat) $category_name .= ($n !== 0 ? ', ' : '').h($cat->getTitle());
            $entity->setCategory_name($category_name);

            $entity->setProfile_url(getProfileUrl($entity->getAuthor_id()));
            if ($entity->getTags())
                $entity->setTags(atrim(explode(',', $entity->getTags())));


            //set users_id that are on this page
            $this->setCacheTag(array(
                'user_id_' . $entity->getAuthor_id(),
                'record_id_' . $entity->getId(),
            ));

        }


        $source = $this->render('list.html', array('entities' => $records));


        //write int cache
        if ($this->cached)
            $this->Cache->write($source, $this->cacheKey, $this->cacheTags);


        return $this->_view($source);
    }

    /**
     * View entity. Entity ID must be integer and not null.
     */
    public function view($id = null) {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        \ACL::turnUser(array($this->module, 'view_materials'),true);
        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        if ($id < 1)
            return $this->showMessage(__('Material not found'));


        $this->Model->bindModel('attaches');
        $this->Model->bindModel('author');
        $this->Model->bindModel('categories');
        $entity = $this->Model->getById($id);


        if (!$entity)
            return $this->showMessage(__('Material not found'));
        if ($entity->getAvailable() == 0 && !\ACL::turnUser(array('__other__', 'can_see_hidden')))
            return $this->showMessage(__('Permission denied'));
        if (!\ACL::turnUser(array('__other__', 'can_premoder')) && in_array($entity->getPremoder(), array('rejected', 'nochecked')))
            return $this->showMessage(__('Permission denied'));

        $categories = $entity->getCategories();
        foreach($categories as $category)
            if (\ACL::checkAccessInList($category->getNo_access()))
                return $this->showMessage(__('Permission denied'));
        $entity->setCategory($categories[0]);

        $entity = $this->AddFields->mergeSelect(array($entity));
        $entity = $entity[0];

        $max_attaches = \Config::read('max_attaches', $this->module);
        if (empty($max_attaches) || !is_numeric($max_attaches))
            $max_attaches = 5;


        //category block
        $category_id = explode(',', $entity->getCategory_id());
        $this->_getCatsTree($category_id[0]);
        /* COMMENT BLOCK */
        if (\Config::read('comment_active', $this->module) == 1
                && \ACL::turnUser(array($this->module, 'view_comments'))
                && $entity->getCommented() == 1) {
            if (\ACL::turnUser(array($this->module, 'add_comments')))
                $this->comments_form = $this->_add_comment_form($id);
            $this->comments = $this->_get_comments($entity);
        }
        $this->Register['current_vars'] = $entity;


        //производим замену соответствующих участков в html шаблоне нужной информацией
        $this->page_title = h($entity->getTitle());
        $tags = $entity->getTags();
        $description = $entity->getDescription();
        if (!empty($tags))
            $this->page_meta_keywords = h($tags);
        if (!empty($description))
            $this->page_meta_description = h($description);

        $navi = array();
        $navi['add_link'] = (\ACL::turnUser(array($this->module, 'add_materials'))) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
        $navi['module_url'] = get_url($this->getModuleURL());

        $navi['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
        $navi['category_name'] = '';
        foreach($categories as $n => $cat) $navi['category_name'] .= ($n !== 0 ? ', ' : '').h($cat->getTitle());

        $navi['navigation'] =  get_link(__('Home'), '/') . __('Separator')
                            . get_link($this->module_title, $this->getModuleURL()) . __('Separator')
                            . get_link($navi['category_name'], $navi['category_url'], array(), true) .  __('Separator')
                            . $entity->getTitle();

        $this->_globalize($navi);


        $entity->setCategory_url($navi['category_url']);
        $entity->setCategory_name($navi['category_name']);

        $entity->setModer_panel($this->_getAdminBar($entity));
        $entity->setProfile_url(getProfileUrl($entity->getAuthor_id()));
        $entry_url = get_url(entryUrl($entity, $this->module));
        $entity->setEntry_url($entry_url);


        $fattach = array();
        if ($entity->getDownload() && is_file(ROOT . $this->getFilesPath($entity->getDownload()))) {
            $fattach['local_url'] = get_url($this->getModuleURL('download_file/' . $entity->getId()));
            $fattach['local_size'] = getSimpleFileSize(filesize(ROOT . $this->getFilesPath($entity->getDownload())));
            $attach_serv = '<a href="'.$fattach['local_url'].'">'.__('Download from server').' ('.$fattach['local_size'].')</a>';
        } else {
            $attach_serv = '';
        }

        if ($entity->getDownload_url_size()) {
            $fattach['remote_size'] = getSimpleFileSize($entity->getDownload_url_size());
            $attach_rem_size = ' (' . $fattach['remote_size'] . ')';
        } else {
            $attach_rem_size = '';
        }

        if ($entity->getDownload_url()) {
            $fattach['remote_url'] = get_url($this->getModuleURL('download_file_url/' . $entity->getId()));
            $attach_rem_url = '<a target="_blank" href="' . $fattach['remote_url']
            . '">' . __('Download remotely') . $attach_rem_size . '</a>';
        } else {
            $attach_rem_url = '';
        }
        $entity->setAttachment($attach_serv . (!empty($attach_serv) && !empty($attach_rem_url) ? ' | ' : '') . $attach_rem_url);
        $entity->setAttach($fattach);

        $announce = \PrintText::print_page($entity->getMain(), $entity->getAuthor() ? $entity->getAuthor()->getStatus() : 0, $entity->getTitle());

        // replace image tags in text
        $markers = array();
        $attaches = $entity->getAttaches();
        $img_attaches = array();
        $i = 0;
        if (!empty($attaches) && count($attaches) > 0) {
            foreach ($attaches as $attach) {
                if ($attach->getIs_image() == '1') {
                        //Что бы не вызывать функции лишний раз.
                        $numder = $attach->getAttach_number();
                        $filename = $attach->getFilename();
                        $fullimg = $this->markerImageAttach($filename, $numder);
                        $smallimg = $this->markerSmallImageAttach($filename, $numder);

                        $announce = $this->insertImageAttach($announce, $filename, $numder);
                        $markers['url_'.$numder] = $fullimg;
                        $markers['small_url_'.$numder] = $smallimg;
                        $img_attaches[$i]['full'] = $fullimg;
                        $img_attaches[$i]['small'] = $smallimg;
                        $i++;
                }
            }
        }

        $markers['attach_all'] = array_slice($img_attaches, 0);

        $max_attaches = \Config::read('max_attaches', $this->module);

        $len = count($img_attaches);
        for ($i = 1; $i <= $max_attaches; $i++) {
            for ($j = 1; $j <= $max_attaches; $j++) {
                if ($i > $len) $markers['attach_' . $i . '_' . $j] = '';
                if ($j - $i > $len) $markers['attach_' . $i . '_' . $j] = array_slice($img_attaches, $i, $len);
                if ($i < $j) $markers['attach_' . $i . '_' . $j] = array_slice($img_attaches, $i, $j - $i);
            }
        }
        $entity->setImg($markers);

        $entity->setMain_text($announce);

        if ($entity->getTags())
            $entity->setTags(atrim(explode(',', $entity->getTags())));


        $this->setCacheTag(array(
            'user_id_' . $entity->getAuthor_id(),
            'record_id_' . $entity->getId(),
            (!empty($_SESSION['user']['status'])) ? 'user_group_' . $_SESSION['user']['status'] : 'user_group_' . 'guest',
        ));


        $source = $this->render('material.html', array('entity' => $entity));

        // Добавляем просмотр
        if ( !$this->material_are_viewed($id) )
        {
            getDB()->save($this->module, array(
                'id' => $id,
                'views' => ($entity->getViews() + 1)
            ));
        }

        return $this->_view($source);
    }

    /**
     * Show materials by user. User ID must be integer and not null.
     */
    public function user($id = null) {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);

        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        if ($id < 1)
            return $this->showMessage(__('Can not find user'));


        $usersModel = \OrmManager::getModelInstance('Users');
        $user = $usersModel->getById($id);
        if (!$user)
            return $this->showMessage(__('Can not find user'));
        if (\ACL::checkAccessInList($user->getNo_access()))
            return $this->showMessage(__('Permission denied'));


        $this->page_title = sprintf(__('User materials'), ' "' . h($user->getName()) . '"');


        //формируем блок со списком  разделов
        $this->_getCatsTree();


        if ($this->cached && $this->Cache->check($this->cacheKey)) {
            $source = $this->Cache->read($this->cacheKey);
            return $this->_view($source);
        }

        // we need to know whether to show hidden
        $where = array('author_id' => $id);
        $where = filter($where);
        if (!\ACL::turnUser(array('__other__', 'can_see_hidden'))) {
            $where['available'] = 1;
        }


        $total = $this->Model->getTotal(array('cond' => $where));
        $perPage = intval(\Config::read('per_page', $this->module));
        if ($perPage < 1)
            $perPage = 10;
        list ($pages, $page) = pagination($total, $perPage, $this->getModuleURL('user/' . $id));
        $this->Register['pages'] = $pages;
        $this->Register['page'] = $page;
        //$this->page_title .= ' (' . $page . ')';



        $navi = array();
        $navi['add_link'] = (\ACL::turnUser(array($this->module, 'add_materials'))) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
        $navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . sprintf(__('User materials'), ' "' . h($user->getName()) . '"');
        $navi['pagination'] = $pages;

        $cntPages = ceil($total / $perPage);
        $recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
        $firstOnPage = ($page - 1) * $perPage + 1;
        $lastOnPage = $firstOnPage + $recOnPage - 1;

        $navi['meta'] = __('Count all material') . ' ' . $total . '. ' . ($total > 1 ? __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage : '');
        $navi['category_name'] = sprintf(__('User materials'), ' "' . h($user->getName()) . '"');
        $this->_globalize($navi);


        if ($total <= 0) {
            $html = $this->render('list.html');
            return $this->_view($html);
        }


        $params = array(
            'page' => $page,
            'limit' => $perPage,
            'order' => getOrderParam(__CLASS__),
        );


        $this->Model->bindModel('attaches');
        $this->Model->bindModel('author');
        $this->Model->bindModel('categories');
        $records = $this->Model->getCollection($where, $params);


        if (count($records) > 0) {
            $records = $this->AddFields->mergeSelect($records);
        }


        // create markers
        foreach ($records as $entity) {
            $this->Register['current_vars'] = $entity;

            $entity->setModer_panel($this->_getAdminBar($entity));
            $entry_url = get_url(entryUrl($entity, $this->module));
            $entity->setEntry_url($entry_url);


            $announce = \PrintText::getAnnounce($entity->getMain(), '', \Config::read('announce_lenght', $this->module), $entity);


            // replace image tags in text
            $attaches = $entity->getAttaches();
            $img_attaches = array();
            $markers = array();
            $i = 0;
            if (!empty($attaches) && count($attaches) > 0) {
                foreach ($attaches as $attach) {
                    if ($attach->getIs_image() == '1') {
                        $announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
                        $markers['url_'.$attach->getAttach_number()] = $this->markerImageAttach($attach->getFilename(), $attach->getAttach_number());
                        $markers['small_url_'.$attach->getAttach_number()] = $this->markerSmallImageAttach($attach->getFilename(), $attach->getAttach_number());
                        $img_attaches[$i]['full'] = $this->markerImageAttach($attach->getFilename(), $attach->getAttach_number());
                        $img_attaches[$i]['small'] = $this->markerSmallImageAttach($attach->getFilename(), $attach->getAttach_number());
                        $i++;
                    }
                }
            }

            $markers['attach_all'] = array_slice($img_attaches, 0);

            $max_attaches = \Config::read('max_attaches', $this->module);

            $len = count($img_attaches);
            for ($i = 1; $i <= $max_attaches; $i++) {
                for ($j = 1; $j <= $max_attaches; $j++) {
                    if ($i > $len) $markers['attach_' . $i . '_' . $j] = '';
                    if ($j - $i > $len) $markers['attach_' . $i . '_' . $j] = array_slice($img_attaches, $i, $len);
                    if ($i < $j) $markers['attach_' . $i . '_' . $j] = array_slice($img_attaches, $i, $j - $i);
                }
            }
            $entity->setImg($markers);

            $entity->setAnnounce($announce);

            $category = $entity->getCategories();
            $entity->setCategory($category[0]);
            $category_name = '';
            foreach($category as $n => $cat) {$category_name .= ($n !== 0 ? ', ' : '').h($cat->getTitle());}
            $entity->setCategory_name($category_name);
            $entity->setCategory_url(get_url($this->getModuleURL('category/' . $entity->getCategory_id())));

            $entity->setProfile_url(getProfileUrl($entity->getAuthor_id()));
            if ($entity->getTags())
                $entity->setTags(atrim(explode(',', $entity->getTags())));


            //set users_id that are on this page
            $this->setCacheTag(array(
                'user_id_' . $entity->getAuthor_id(),
                'record_id_' . $entity->getId(),
            ));

        }


        $source = $this->render('list.html', array('entities' => $records));


        //write int cache
        if ($this->cached)
            $this->Cache->write($source, $this->cacheKey, $this->cacheTags);


        return $this->_view($source);
    }

    /**
     * return form to add
     */
    public function add_form() {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        \ACL::turnUser(array($this->module, 'add_materials'),true);

        // categories block
        $this->_getCatsTree();

        // Navigation panel
        $navi = array();
        $navi['add_link'] = (\ACL::turnUser(array($this->module, 'add_materials'))) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
        $navi['navigation'] = get_link(__('Home'), '/') . __('Separator') . get_link($this->page_title, $this->getModuleURL()) . __('Separator') . __('Add material');

        $this->page_title = __('Adding material');

        $this->_globalize($navi);

        // Additional fields
        $markers = array();

        $_addFields = $this->AddFields->getInputs(array());
        foreach ($_addFields as $k => $field) {
            $markers[strtolower($k)] = $field;
        }

        $sectionsModel = \OrmManager::getModelInstance($this->module . 'Categories');
        $categories = $this->check_categories($sectionsModel->getCollection());
        $markers['cats_selector'] = $this->_buildSelector($categories, false);
        $markers['cats_list'] = $categories;


        //comments and hide
        $markers['commented'] = ' checked="checked"';
        $markers['available'] = ' checked="checked"';
        if (!\ACL::turnUser(array($this->module, 'record_comments_management')))
            $markers['commented'] = ' disabled="disabled"';
        if (!\ACL::turnUser(array($this->module, 'hide_material')))
            $markers['available'] = ' disabled="disabled"';


        $markers['action'] = get_url($this->getModuleURL('add/'));
        $markers['max_attaches'] = \Config::read('max_attaches', $this->module);
        if (empty($markers['max_attaches']) || !is_numeric($markers['max_attaches']))
            $markers['max_attaches'] = 5;

        $markers['locked_attaches'] = intval(\Config::read('locked_attaches', $this->module));

        $source = $this->render('addform.html', array('context' => $markers));
        return $this->_view($source);
    }

    /**
     *
     * \Validate data and create a new record into
     * Data Base. If an errors, redirect user to add form
     * and show error message where speaks as not to admit
     * errors in the future
     *
     */
    public function add() {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        \ACL::turnUser(array($this->module, 'add_materials'),true);
        // Если не переданы данные формы - функция вызвана по ошибке
        if (!isset($_POST['mainText'])
                || !isset($_POST['title'])
                || !isset($_POST['cats_selector'])) {
            return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);
        }
        $error = '';


        // Check additional fields if an exists.
        // This must be doing after define $error variable.
        $_addFields = $this->AddFields->checkFields();
        if (is_string($_addFields))
            $error .= $_addFields;


        $fields = array('description', 'tags', 'sourse', 'sourse_email', 'sourse_site', 'download_url', 'download_url_size');
        $fields_settings = \Config::read('fields', $this->module);
        foreach ($fields as $field) {
            if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
                $error .= '<li>' . sprintf(__('Empty field "param"'), __($field)) . '</li>' . "\n";
                $$field = null;
            } else
                $$field = isset($_POST[$field]) ? h(trim($_POST[$field])) : '';
        }

        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $title = trim(mb_substr($_POST['title'], 0, 128));
        $main_text = trim($_POST['mainText']);
        $commented = (!empty($_POST['commented'])) ? 1 : 0;
        $available = (!empty($_POST['available'])) ? 1 : 0;

        if (is_array($_POST['cats_selector']) and \Config::read('use_multicategories'))
            // передан массив и разрешены мультикатегории
            $in_cat = implode(',', $_POST['cats_selector']);
        elseif (is_array($_POST['cats_selector']))
            // передан массив, но запрещены мультикатегории
            $in_cat = $_POST['cats_selector'][0];
        else
            // передана одна категория
            $in_cat = intval($_POST['cats_selector']);

        // Проверяем, заполнены ли обязательные поля
          //validation data class
        if (empty($in_cat))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Category')) . '</li>' . "\n";
        if (empty($title))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Load name')) . '</li>' . "\n";
        elseif (!\Validate::cha_val($title, V_TITLE))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Load name')) . '</li>' . "\n";
        $max_lenght = \Config::read('max_lenght', $this->module);
        if ($max_lenght <= 0)
            $max_lenght = 10000;
        $min_lenght = \Config::read('min_lenght', $this->module);
        if ($min_lenght <= 0 || $min_lenght > $max_lenght)
            $min_lenght = 10;
        if (empty($main_text))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('description')) . '</li>' . "\n";
        elseif (mb_strlen($main_text) > $max_lenght)
            $error .= '<li>' . sprintf(__('Very big "param"'), __('description'), $max_lenght) . '</li>' . "\n";
        elseif (mb_strlen($main_text) < $min_lenght)
            $error .= '<li>' . sprintf(__('Very small "param"'), __('description'), $min_lenght) . '</li>' . "\n";
        if ((\Config::read('require_file', $this->module) == 1 || empty($download_url)) && empty($_FILES['attach']['name']))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('File')) . '</li>' . "\n";
        if (isset($_FILES['attach']['name']) && $_FILES['attach']['size'] > $this->getMaxSize())
            $error .= '<li>' . sprintf(__('Very big file'),$_FILES['attach']['name'], getSimpleFileSize($this->getMaxSize())) . '</li>' . "\n";
        if (!empty($tags) && !\Validate::cha_val($tags, V_TITLE))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('tags')) . '</li>' . "\n";
        if (!empty($sourse) && !\Validate::cha_val($sourse, V_TITLE))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('sourse')) . '</li>' . "\n";
        if (!empty($sourse_email) && !\Validate::cha_val($sourse_email, V_MAIL))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('sourse_email')) . '</li>' . "\n";
        if (!empty($sourse_site) && !\Validate::cha_val($sourse_site, V_URL))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('sourse_site')) . '</li>' . "\n";
        if (!empty($download_url) && !\Validate::cha_val($download_url, V_URL))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('download_url')) . '</li>' . "\n";
        if (!empty($download_url_size) && !\Validate::cha_val($download_url_size, V_INT))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('download_url_size')) . '</li>' . "\n";


        // Check screenshots size and format
        $out = checkAttaches($this->module, true);
        if ($out != null)
            $error .= $out;


        $sectionsModel = \OrmManager::getModelInstance($this->module . 'Categories');

        $in_cat_array = explode(',', $in_cat);
        foreach ($in_cat_array as $cat) {
            $category = $sectionsModel->getById($cat);
            if (!$category || \ACL::checkAccessInList($category->getNo_access())) {
                $error .= '<li>' . __('Can not find category') . $cat . '</li>' . "\n";
                break;
            }
        }


        // Errors
        if (!empty($error)) {
            $error_msg = '<p class="errorMsg">' . __('Some error in form') . '</p>'
                    . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            return $this->showMessage($error_msg,$this->getModuleURL("add_form/"));
        }

        if (!\ACL::turnUser(array($this->module, 'record_comments_management'))) $commented = '1';
        if (!\ACL::turnUser(array($this->module, 'hide_material'))) $available = '1';

        //Проверяем прикрепленный файл...
        $file = '';
        $filename = '';
        if (!empty($_FILES['attach']['name'])) {
            $file = $this->__saveFile($_FILES['attach']);
            if (!empty($file))
                $filename = $_FILES['attach']['name'];
        }


        // Защита от того, чтобы один пользователь не добавил
        // 100 материалов за одну минуту
        if (isset($_SESSION['unix_last_post']) and ( time() - $_SESSION['unix_last_post'] < 10 )) {
            return $this->showMessage(__('Material has been added'),$this->getModuleURL("add_form/"));
        }

        //remove cache
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('module_' . $this->module));
        // Формируем SQL-запрос на добавление темы
        $data = array(
            'title' => $title,
            'main' => mb_substr($main_text, 0, $max_lenght),
            'date' => new \Expr('NOW()'),
            'author_id' => $_SESSION['user']['id'],
            'category_id' => $in_cat,
            'description' => $description,
            'tags' => $tags,
            'sourse' => $sourse,
            'sourse_email' => $sourse_email,
            'sourse_site' => $sourse_site,
            'download_url' => $download_url,
            'download_url_size' => (int) $download_url_size,
            'commented' => $commented,
            'available' => $available,
            'view_on_home' => $category->getView_on_home(),
            'premoder'      => 'confirmed',
        );
        $data = $this->AddFields->set($_addFields, $data);
        if (\ACL::turnUser(array($this->module, 'materials_require_premoder')))
            $data['premoder'] = 'nochecked';
        if (!empty($file)) {
            $data['download'] = $file;
            $data['filename'] = $filename;
        }
        $className = \OrmManager::getEntityName($this->module);
        $entity = new $className($data);
        if ($entity) {
            $entity->setId($entity->save());

            downloadAttaches($this->module, $entity->getId());


            // hook for plugins
            \Events::init('new_entity', array(
                'entity' => $entity,
                'module' => $this->module,
            ));

            //clean cache
            $this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module));
            if ($this->isLogging)
                \Logination::write('adding ' . $this->module, $this->module . ' id(' . $entity->getId() . ')');

            if (\ACL::turnUser(array($this->module, 'materials_require_premoder'))) {
                return $this->showMessage(__('Material will be available after validation'),false,'grand');
            } else {
                return $this->showMessage(__('Material has been added'), entryUrl($entity, $this->module),'ok');
            }
        } else {
            return $this->showMessage(__('Some error occurred'),$this->getModuleURL("add_form/"));
        }
    }

    /**
     *
     * Create form and fill his data from record which ID
     * transfered into function. Show errors if an exists
     * after unsuccessful attempt. Also can get data for filling
     * from SESSION if user try preview message or create error.
     *
     * @param int $id material then to be edit
     */
    public function edit_form($id = null) {
        \ACL::turnUser(array($this->module, 'view_list'),true);

        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        if ($id < 1)
            return $this->showMessage(__('Material not found'));

        $this->page_title = __('Material editing');

        $this->Model->bindModel('attaches');
        // $this->Model->bindModel('author');
        $this->Model->bindModel('categories');
        $entity = $this->Model->getById($id);

        if (!$entity)
            return $this->showMessage(__('Material not found'));

        //turn access
        if (!\ACL::turnUser(array($this->module, 'edit_materials'))
                && (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
                && \ACL::turnUser(array($this->module, 'edit_mine_materials'))) === false) {
            return $this->showMessage(__('Permission denied'), entryUrl($entity, $this->module));
        }
        
        foreach ($entity->getCategories() as $cat) {
            if (!$cat || \ACL::checkAccessInList($cat->getNo_access())) {
                return $this->showMessage(__('Permission denied'), getReferer(),'error', true);
            }
        }

        if (count($entity) > 0) {
            $entity = $this->AddFields->mergeSelect(array($entity));
            $entity = $entity[0];
        }


        $this->Register['current_vars'] = $entity;

        //forming categories list
        $this->_getCatsTree($entity->getCategory_id());

        $markers = $entity;
        $markers->setMain_text($markers->getMain());
        $markers->setIn_cat($markers->getCategory_id());

        $sectionsModel = \OrmManager::getModelInstance($this->module . 'Categories');
        $categories = $this->check_categories($sectionsModel->getCollection());
        $selectedCatId = ($markers->getIn_cat()) ? $markers->getIn_cat() : $markers->getCategory_id();
        $cats_change = $this->_buildSelector($categories, $selectedCatId);
        $markers->setCats_selector($cats_change);
        $markers->setCats_list($categories);


        //comments and hide
        $commented = ($markers->getCommented()) ? 'checked="checked"' : '';
        if (!\ACL::turnUser(array($this->module, 'record_comments_management')))
            $commented .= ' disabled="disabled"';
        $available = ($markers->getAvailable()) ? 'checked="checked"' : '';
        if (!\ACL::turnUser(array($this->module, 'hide_material')))
            $available .= ' disabled="disabled"';
        $markers->setAction(get_url($this->getModuleURL('update/' . $markers->getId())));
        $markers->setCommented($commented);
        $markers->setAvailable($available);

        $markers->setFilename($entity->getFilename());
        $markers->setFiledate($entity->getDate());

        $locked_attaches = intval(\Config::read('locked_attaches', $this->module));
        $unlinkfiles = array();
        $attDelButtons = '';
        if (!$locked_attaches) {
            $attaches = $markers->getAttaches();
            if (count($attaches)) {
                foreach ($attaches as $key => $attach) {
                    $name = substr($attach->getFilename(), strpos($attach->getFilename(), '_', 0)+1);
                    $attDelButtons .= '<input type="checkbox" name="unlink' . $attach->getAttach_number() . '"> ' . $attach->getAttach_number() . ' . (' . $name . ')' . "<br />\n";
                    $unlinkfiles[] = array(
                                    'id' => $attach->getAttach_number(),
                                    'name' => $name,
                                    'url' => get_url($this->getFilesPath($attach->getFilename())),
                                    'url_small' => ($attach->getIs_image()) ? $this->markerSmallImageAttach($attach->getFilename(), $attach->getAttach_number()) : get_url($this->getFilesPath($attach->getFilename())),
                                    'date' => $attach->getDate(),
                                    'size' => getSimpleFileSize($attach->getSize()),
                                    'is_img' => $attach->getIs_image(),
                    );
                }
            }
        }


        $markers->setAttaches_delete($attDelButtons);
        $markers->setMax_attaches(\Config::read('max_attaches', $this->module));

        $markers->setLocked_attaches($locked_attaches);
        $markers->setAttaches_list(array_slice($unlinkfiles,0));

        // Navigation panel
        $navi = array();
        $navi['add_link'] = (\ACL::turnUser(array($this->module, 'add_materials'))) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
        $category = $entity->getCategories();
        $entity->setCategory($category[0]);
        $navi['category_name'] = '';
        foreach($category as $n => $cat) {$navi['category_name'] .= ($n !== 0 ? ', ' : '').h($cat->getTitle());}
        $navi['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
        if (count($category) === 0) {
            $navi['navigation'] = $this->_buildBreadCrumbs($category[0]) .  __('Separator')
                                . get_link($entity->getTitle(), get_url(entryUrl($entity, $this->module)), array(), true) .  __('Separator')
                                . __('Material editing');
        } else {
            $navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
                                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator')
                                . get_link($navi['category_name'], $navi['category_url'], array(), true) .  __('Separator')
                                . get_link($entity->getTitle(), get_url(entryUrl($entity, $this->module)), array(), true) .  __('Separator')
                                . __('Material editing');
        }
        $this->_globalize($navi);

        $source = $this->render('editform.html', array('context' => $markers));
        return $this->_view($source);
    }

    /**
     *
     * \Validate data and update record into
     * Data Base. If an errors, redirect user to add form
     * and show error message where speaks as not to admit
     * errors in the future
     *
     */
    public function update($id = null) {
        // Если не переданы данные формы - функция вызвана по ошибке
        if (!isset($id)
                || !isset($_POST['title'])
                || !isset($_POST['mainText'])) {
            return $this->showMessage(__('Some error occurred'));
        }
        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        if ($id < 1)
            return $this->showMessage(__('Material not found'));
        $error = '';


        $entity = $this->Model->getById($id);
        if (!$entity)
            return $this->showMessage(__('Material not found'));


        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        if (!\ACL::turnUser(array($this->module, 'edit_materials'))
                && (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
                && \ACL::turnUser(array($this->module, 'edit_mine_materials'))) === false) {
            return $this->showMessage(__('Permission denied'),getReferer(),'error', true);
        }


        // Check additional fields if an exists.
        // This must be doing after define $error variable.
        $_addFields = $this->AddFields->checkFields();
        if (is_string($_addFields))
            $error .= $_addFields;


        $fields = array('description', 'tags', 'sourse', 'sourse_email', 'sourse_site', 'download_url', 'download_url_size');
        
        $fields_settings = \Config::read('fields', $this->module);
        foreach ($fields as $field) {
            if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
                $error .= '<li>' . sprintf(__('Empty field "param"'), __($field)) . '</li>' . "\n";
                $$field = null;
            } else
                $$field = isset($_POST[$field]) ? h(trim($_POST[$field])) : '';
        }

        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $title = trim(mb_substr($_POST['title'], 0, 128));
        $main_text = trim($_POST['mainText']);
        $commented = (!empty($_POST['commented'])) ? 1 : 0;
        $available = (!empty($_POST['available'])) ? 1 : 0;

        if (empty($_POST['cats_selector']))
            $in_cat = $entity->getCategory_id();
        elseif (is_array($_POST['cats_selector']) and \Config::read('use_multicategories'))
            // передан массив и разрешены мультикатегории
            $in_cat = implode(',', $_POST['cats_selector']);
        elseif (is_array($_POST['cats_selector']))
            // передан массив, но запрещены мультикатегории
            $in_cat = $_POST['cats_selector'][0];
        else
            // передана одна категория
            $in_cat = intval($_POST['cats_selector']);

        if (!\ACL::turnUser(array($this->module, 'record_comments_management')))
            $commented = 1;
        if (!\ACL::turnUser(array($this->module, 'hide_material')))
            $available = (\ACL::turnUser(array($this->module, 'need_check_materials')) ? 0 : 1);



        // Проверяем, заполнены ли обязательные поля
        if (empty($title))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Load name')) . '</li>' . "\n";
        elseif (!\Validate::cha_val($title, V_TITLE))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Load name')) . '</li>' . "\n";
        $max_lenght = \Config::read('max_lenght', $this->module);
        if ($max_lenght <= 0)
            $max_lenght = 10000;
        $min_lenght = \Config::read('min_lenght', $this->module);
        if ($min_lenght <= 0 || $min_lenght > $max_lenght)
            $min_lenght = 10;
        if (empty($main_text))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('description')) . '</li>' . "\n";
        elseif (mb_strlen($main_text) > $max_lenght)
            $error .= '<li>' . sprintf(__('Very big "param"'), __('description'), $max_lenght) . '</li>' . "\n";
        elseif (mb_strlen($main_text) < $min_lenght)
            $error .= '<li>' . sprintf(__('Very small "param"'), __('description'), $min_length) . '</li>' . "\n";
        if ((\Config::read('require_file', $this->module) == 1 || empty($download_url)) && empty($_FILES['attach']['name']) && !empty($_POST['delete_file']))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('File')) . '</li>' . "\n";
        if (isset($_FILES['attach']['name']) && $_FILES['attach']['size'] > $this->getMaxSize())
            $error .= '<li>' . sprintf(__('Very big file'),__('File'),getSimpleFileSize($this->getMaxSize())) . '</li>' . "\n";
        if (!empty($tags) && !\Validate::cha_val($tags, V_TITLE))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('tags')) . '</li>' . "\n";
        if (!empty($sourse) && !\Validate::cha_val($sourse, V_TITLE))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('sourse')) . '</li>' . "\n";
        if (!empty($sourse_email) && !\Validate::cha_val($sourse_email, V_MAIL))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('sourse_email')) . '</li>' . "\n";
        if (!empty($sourse_site) && !\Validate::cha_val($sourse_site, V_URL))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('sourse_site')) . '</li>' . "\n";
        if (!empty($download_url) && !\Validate::cha_val($download_url, V_URL))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('download_url')) . '</li>' . "\n";
        if (!empty($download_url_size) && !\Validate::cha_val($download_url_size, V_INT))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('download_url_size')) . '</li>' . "\n";



        $sectionsModel = \OrmManager::getModelInstance($this->module . 'Categories');

        $in_cat_array = explode(',', $in_cat);
        foreach ($in_cat_array as $cat) {
            $category = $sectionsModel->getById($cat);
            if (!$category || \ACL::checkAccessInList($category->getNo_access())) {
                $error .= '<li>' . __('Can not find category') . $cat . '</li>' . "\n";
                break;
            }
        }


        // Delete attached file if an exists and we get flag from editor
        if (!empty($_POST['delete_file']) || !empty($_FILES['attach']['name'])) {
            if ($entity->getDownload() && file_exists($this->attached_files_path . $entity->getDownload()))
                _unlink($this->attached_files_path . $entity->getDownload());
        }

        //Проверяем прикрепленный файл...
        $file = '';
        $filename = '';
        if (!empty($_FILES['attach']['name']))
            $file = $this->__saveFile($_FILES['attach']);

        if ($file != '')
            $filename = $_FILES['attach']['name'];

        // Check screenshots size and format
        $out = checkAttaches($this->module, true);
        if ($out != null)
            $error .= $out;

        // Errors
        if (!empty($error)) {
            $error_msg = '<p class="errorMsg">' . __('Some error in form') . '</p>'
                    . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            return $this->showMessage($error_msg, $this->getModuleURL("edit_form/$id"));
        }

        downloadAttaches($this->module, $id, true);

        //remove cache
        $this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module, 'record_id_' . $id));


        $data = array(
            'title' => $title,
            'main' => mb_substr($main_text, 0, $max_lenght),
            'category_id' => $in_cat,
            'description' => $description,
            'tags' => $tags,
            'sourse' => $sourse,
            'sourse_email' => $sourse_email,
            'sourse_site' => $sourse_site,
            'download_url' => $download_url,
            'download_url_size' => $download_url_size,
            'commented' => $commented,
            'available' => $available,
        );
        $data = $this->AddFields->set($_addFields, $data);
        if (!empty($file)) {
            $data['download'] = $file;
            $data['filename'] = $filename;
        }
        $entity->set($data);
        $entity->save();

        if ($this->isLogging)
            \Logination::write('editing ' . $this->module, $this->module . ' id(' . $id . ')');
        return $this->showMessage(__('Material is saved'), entryUrl($entity, $this->module), 'ok');
    }

    /**
     * Check user access and if all right
     * delete record with geting ID.
     *
     * @param int $id
     */
    public function delete($id = null) {
        $this->cached = false;
        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        if ($id < 1)
            return $this->showMessage(__('Material not found'));


        $entity = $this->Model->getById($id);
        if (!$entity)
            return $this->showMessage(__('Material not found'));


        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        if (!\ACL::turnUser(array($this->module, 'delete_materials'))
                && (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
                && \ACL::turnUser(array($this->module, 'delete_mine_materials'))) === false) {
            return $this->showMessage(__('Permission denied'),getReferer(),'error', true);
        }


        //remove cache
        $this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module, 'record_id_' . $id));

        $entity->delete();

        $user_id = (!empty($_SESSION['user']['id'])) ? intval($_SESSION['user']['id']) : 0;
        if ($this->isLogging)
            \Logination::write('delete ' . $this->module, $this->module . ' id(' . $id . ') user id(' . $user_id . ')');

        $referer_params = explode("/", trim(parse_url(getReferer(), PHP_URL_PATH), '/'));

        return $this->showMessage(
            __('Material has been delete'),
            ((!isset($referer_params[1]) or in_array($referer_params[1], array("category", "index")))
                ? getReferer()
                : false),
            'ok'
        );
    }

    /**
     * add comment
     *
     * @id (int)    entity ID
     * @return      info message
     */
    public function add_comment($id = null) {
        \ACL::turnUser(array($this->module, 'view_list'),true);
        include_once(ROOT . '/sys/inc/add_comment.php');
    }

    /**
     * add comment form
     *
     * @id (int)    entity ID
     * @return      html form
     */
    private function _add_comment_form($id = null) {
        include_once(ROOT . '/sys/inc/_add_comment_form.php');
        return $html;
    }

    /**
     * edit comment form
     *
     * @id (int)    comment ID
     * @return      html form
     */
    public function edit_comment_form($id = null) {
        \ACL::turnUser(array($this->module, 'view_list'),true);
        include_once(ROOT . '/sys/inc/edit_comment_form.php');
    }

    /**
     * update comment
     *
     * @id (int)    comment ID
     * @return      info message
     */
    public function update_comment($id = null) {
        \ACL::turnUser(array($this->module, 'view_list'),true);
        include_once(ROOT . '/sys/inc/update_comment.php');
    }

    /**
     * get comments
     *
     * @id (int)    entity ID
     * @return      html comments list
     */
    private function _get_comments($entity = null) {
        include_once(ROOT . '/sys/inc/_get_comments.php');
        return $html;
    }

    /**
     * delete comment
     *
     * @id (int)    comment ID
     * @return      info message
     */
    public function delete_comment($id = null) {
        \ACL::turnUser(array($this->module, 'view_list'),true);
        include_once(ROOT . '/sys/inc/delete_comment.php');
    }

    /**
     * premoder comment
     *
     * @param int $id - comment ID
     * @param string $type - moder type
     * @return      info message
     */
    public function premoder_comment($id, $type) {
        \ACL::turnUser(array($this->module, 'view_list'),true);
        include_once(ROOT . '/sys/inc/premoder_comment.php');
    }

    /**
     * @param int $id - record ID
     *
     * update date by record also up record in recods list
     */
    public function upper($id) {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        \ACL::turnUser(array($this->module, 'up_materials'),true);
        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        if ($id < 1)
            return $this->showMessage(__('Material not found'));


        $entity = $this->Model->getById($id);
        if ($entity) {
            $entity->setDate(date("Y-m-d H:i:s"));
            $entity->save();
            return $this->showMessage(__('Operation is successful'), false,'alert');
        }
        return $this->showMessage(__('Some error occurred'));
    }

    /**
     * @param int $id - record ID
     *
     * allow record be on home page
     */
    public function on_home($id) {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        \ACL::turnUser(array($this->module, 'on_home'),true);
        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        if ($id < 1)
            return $this->showMessage(__('Material not found'));


        $entity = $this->Model->getById($id);
        if (!$entity)
            return $this->showMessage(__('Material not found'));

        $entity->setView_on_home('1');
        $entity->save();
        return $this->showMessage(__('Operation is successful'), false,'alert');
    }

    /**
     * @param int $id - record ID
     *
     * denied record be on home page
     */
    public function off_home($id) {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        \ACL::turnUser(array($this->module, 'on_home'),true);
        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        if ($id < 1)
            return $this->showMessage(__('Material not found'));


        $entity = $this->Model->getById($id);
        if (!$entity)
            return $this->showMessage(__('Material not found'));

        $entity->setView_on_home('0');
        $entity->save();
        return $this->showMessage(__('Operation is successful'), false,'alert');
    }

    /**
     * @param int $id - record ID
     *
     * fix or unfix record on top on home page
     */
    public function fix_on_top($id) {
        \ACL::turnUser(array($this->module, 'view_list'),true);
        \ACL::turnUser(array($this->module, 'on_home'),true);
        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        if ($id < 1)
            return $this->showMessage(__('Material not found'));

        $entity = $this->Model->getById($id);
        if (!$entity)
            return $this->showMessage(__('Material not found'));

        $curr_state = $entity->getOn_home_top();
        $dest = ($curr_state) ? '0' : '1';
        $entity->setOn_home_top($dest);
        $entity->save();
        return $this->showMessage(__('Operation is successful'), false,'alert');
    }

    function download_file($id = null, $mimetype = 'application/octet-stream') {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        \ACL::turnUser(array($this->module, 'download_files'),true);

        if (empty($id))
            return $this->showMessage(__('File not found'));
        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        //clear cache
        $this->Cache->clean(CACHE_MATCHING_TAG, array('record_id_' . $id, 'module_load'));


        $entity = $this->Model->getById($id);
        if (!$entity)
            return $this->showMessage(__('File not found'));

        $entity->setDownloads($entity->getDownloads() + 1);
        $entity->save();

        if (\Config::read('filename_from_title', $this->module)) {
            $ext = strrchr($entity->getDownload(), ".");
            $name = $entity->getTitle() . \Config::read('filename_postfix', $this->module) . (empty($ext) ? '' : $ext);
        } else {
            $name = $entity->getFilename();
            if (!empty($name)) {
                if (\Config::read('filename_postfix', $this->module)) {
                    $pos = strrpos($entity->getFilename(), ".");
                    if ($pos === false) {
                        $name = $name . \Config::read('filename_postfix', $this->module);
                    } else {
                        $ext = strrchr($name, ".");
                        $name = substr($name, 0, $pos) . \Config::read('filename_postfix', $this->module) . $ext;
                    }
                }
            } else {
                $name = $entity->getDownload();
                if (\Config::read('filename_postfix', $this->module)) {
                    $pos = strrpos($name, ".");
                    if ($pos === false) {
                        $name = $name . \Config::read('filename_postfix', $this->module);
                    } else {
                        $ext = strrchr($name, ".");
                        $name = substr($name, 0, $pos) . \Config::read('filename_postfix', $this->module) . $ext;
                    }
                }
            }
        }
        $filename = ROOT . $this->getFilesPath($entity->getDownload());


        if (!file_exists($filename))
            return $this->showMessage(__('File not found'));
        $from = 0;
        $size = filesize($filename);
        $to = $size;


        if (isset($_SERVER['HTTP_RANGE'])) {
            if (preg_match('#bytes=-([0-9]*)#', $_SERVER['HTTP_RANGE'], $range)) {// если указан отрезок от конца файла
                $from = $size - $range[1];
                $to = $size;
            } elseif (preg_match('#bytes=([0-9]*)-#', $_SERVER['HTTP_RANGE'], $range)) {// если указана только начальная метка
                $from = $range[1];
                $to = $size;
            } elseif (preg_match('#bytes=([0-9]*)-([0-9]*)#', $_SERVER['HTTP_RANGE'], $range)) {// если указан отрезок файла
                $from = $range[1];
                $to = $range[2];
            }
            header('HTTP/1.1 206 Partial Content');

            $cr = 'Content-Range: bytes ' . $from . '-' . $to . '/' . $size;
        } else
            header('HTTP/1.1 200 Ok');

        $etag = md5($filename);
        $etag = substr($etag, 0, 8) . '-' . substr($etag, 8, 7) . '-' . substr($etag, 15, 8);
        header('ETag: "' . $etag . '"');
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . ($to - $from));
        if (isset($cr))
            header($cr);
        header('Connection: close');

        header('Content-Type: ' . $mimetype);
        header('Last-Modified: ' . gmdate('r', filemtime($filename)));
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($filename)) . " GMT");
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 3600) . " GMT");
        $f = fopen($filename, 'rb');


        if (preg_match('#^image/#', $mimetype))
            header('Content-Disposition: filename="' . $name . '";');
        else
            header('Content-Disposition: attachment; filename="' . $name . '";');

        fseek($f, $from, SEEK_SET);
        $size = $to;
        $downloaded = 0;
        while (!feof($f) and ($downloaded < $size)) {
            $block = min(1024 * 8, $size - $downloaded);
            echo fread($f, $block);
            $downloaded += $block;
            flush();
        }
        fclose($f);
    }

    function download_file_url($id = null, $mimetype = 'application/octet-stream') {


        $this->counter = false;
        $this->cached = false;

        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));

        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        \ACL::turnUser(array($this->module, 'download_files'),true);

        $entity = $this->Model->getById($id);
        if (!$entity)
            return $this->showMessage(__('File not found'));

        header('X-Robots-Tag: noindex,nofollow');
        $entity->setDownloads($entity->getDownloads() + 1);
        $entity->save();

        header('Location: ' . $entity->getDownload_url());
    }

    /**
     * @param int $id - record ID
     *
     * fix or unfix record on top on home page
     */
    public function premoder($id, $type)
        {
        \ACL::turnUser(array($this->module, 'view_list'),true);
        \ACL::turnUser(array('__other__', 'can_premoder'),true);
        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        $id = (int)$id;
        if ($id < 1) redirect('/' . $this->module . '/');

        if (!in_array((string)$type, $this->premoder_types))
          return $this->showMessage(__('Some error occurred'));

        $target = $this->Model->getById($id);
        if (!$target) redirect('/');

        $target->setPremoder((string)$type);
        $target->save();
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL(),'ok');
    }

    /**
     * @param array $record - assoc record array
     * @return string - admin buttons
     *
     * create and return admin bar
     * Is deprecated. It will be removed in DarsiPro 7
     */
    protected function _getAdminBar($record) {
        $moder_panel = '';
        $id = $record->getId();
        $uid = $record->getAuthor_id();
        if (!$uid)
            $uid = 0;

        if (\ACL::turnUser(array('__other__', 'can_premoder')) && 'nochecked' == $record->getPremoder()) {
            $moder_panel .= get_link('', '/' . $this->module . '/premoder/' . $id . '/confirmed',
                array(
                    'class' => 'drs-premoder-confirm',
                    'title' => __('Confirm'),
                    'onClick' => "return confirm('" . __('Are you sure?') . "')",
                )) . '&nbsp;';
            $moder_panel .= get_link('', '/' . $this->module . '/premoder/' . $id . '/rejected',
                array(
                    'class' => 'drs-premoder-reject',
                    'title' => __('Reject'),
                    'onClick' => "return confirm('" . __('Are you sure?') . "')",
                )) . '&nbsp;';
        } else if (\ACL::turnUser(array('__other__', 'can_premoder')) && 'rejected' == $record->getPremoder()) {
            $moder_panel .= get_link('', '/' . $this->module . '/premoder/' . $id . '/confirmed',
                array(
                    'class' => 'drs-premoder-confirm',
                    'title' => __('Confirm'),
                    'onClick' => "return confirm('" . __('Are you sure?') . "')",
                )) . '&nbsp;';
        }

        if (\ACL::turnUser(array($this->module, 'edit_materials'))
                || (!empty($_SESSION['user']['id']) && $uid == $_SESSION['user']['id']
                && \ACL::turnUser(array($this->module, 'edit_mine_materials')))) {
            $moder_panel .= get_link('', $this->getModuleURL('edit_form/' . $id), array('class' => 'drs-edit'));
        }

        if (\ACL::turnUser(array($this->module, 'up_materials'))) {
            $moder_panel .= get_link('', $this->getModuleURL('fix_on_top/' . $id), array('id' => 'ffm'.$record->getId(), 'class' => 'drs-star', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('ffm".$record->getId()."')}; return false"));
            $moder_panel .= get_link('', $this->getModuleURL('upper/' . $id), array('id' => 'fum'.$record->getId(),'class' => 'drs-up', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fum".$record->getId()."')}; return false"));
        }
        if (\ACL::turnUser(array($this->module, 'on_home'))) {
            if ($record->getView_on_home() == 1) {
                $moder_panel .= get_link('', $this->getModuleURL('off_home/' . $id), array('id' => 'fofm'.$record->getId(), 'class' => 'drs-on', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fofm".$record->getId()."')}; return false"));
            } else {
                $moder_panel .= get_link('', $this->getModuleURL('on_home/' . $id), array('id' => 'fonm'.$record->getId(), 'class' => 'drs-off', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fonm".$record->getId()."')}; return false"));
            }
        }

        if (\ACL::turnUser(array($this->module, 'delete_materials'))
                || (!empty($_SESSION['user']['name']) && $uid == $_SESSION['user']['id']
                && \ACL::turnUser(array($this->module, 'delete_mine_materials')))) {
            $moder_panel .= get_link('', $this->getModuleURL('delete/' . $id), array('id' => 'fdm'.$record->getId(), 'class' => 'drs-delete', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fdm".$record->getId()."')}; return false"));
        }
        return $moder_panel;
    }

    /**
     * Try Save file
     *
     * @param array $file (From POST request)
     */
    private function __saveFile($file) {
        // Извлекаем из имени файла расширение
        $ext = strrchr($file['name'], ".");

        // Формируем путь к файлу
        if (!isPermittedFile($ext))
            $path = md5(uniqid(rand(), true)) . '-' . date("YmdHis", time()) . '.txt';
        else
            $path = md5(uniqid(rand(), true)) . '-' . date("YmdHis", time()) . $ext;
        // Перемещаем файл из временной директории сервера в директорию files
        if (!file_exists(ROOT . $this->getFilesPath())) mkdir(ROOT . $this->getFilesPath(),0766);
        if (move_uploaded_file($file['tmp_name'], ROOT . $this->getFilesPath($path))) {
            chmod(ROOT . $this->getFilesPath($path), 0644);
        }

        return $path;
    }

    /**
     * RSS
     *
     */
    public function rss() {
        \ACL::turnUser(array($this->module, 'view_list'),true);
        include_once ROOT . '/sys/inc/rss.php';
    }

}
