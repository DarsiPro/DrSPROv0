<?php
/**
* @project    DarsiPro CMS
* @package    Foto Module
* @url        https://darsi.pro
*/


namespace FotoModule;

Class ActionsHandler extends \Module {

    /**
     * @module_title  title of module
     */
    public $module_title = 'Фото';

    /**
     * @template  layout for module
     */
    public $template = 'foto';

    /**
     * @module module indentifier
     */
    public $module = 'foto';


    function __construct($params) {
        parent::__construct($params);

        $this->setModel();
        
        
    }


    /**
     * default action ( show main page )
     */
    public function index() {
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);


        //формируем блок со списком  разделов
        $this->_getCatsTree();


        if ($this->cached && $this->Cache->check($this->cacheKey)) {
            $source = $this->Cache->read($this->cacheKey);
            return $this->_view($source);
        }

        $where = filter();


        $total = $this->Model->getTotal(array('cond' => $where));
        $perPage = intval(\Config::read('per_page', $this->module));
        if ($perPage < 1)
            $perPage = 10;
        list ($pages, $page) = pagination($total, $perPage, $this->getModuleURL());
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


        $this->Model->bindModel('author');
        $this->Model->bindModel('categories');
        $records = $this->Model->getCollection($where, $params);

        if (count($records) > 0) {
            $records = $this->AddFields->mergeSelect($records);
        }

        if (\Config::read('use_local_preview', $this->module)) {
            $preview_size_x = \Config::read('img_size_x', $this->module);
            $preview_size_y = \Config::read('img_size_y', $this->module);
        } else {
            $preview_size_x = \Config::read('img_size_x');
            $preview_size_y = \Config::read('img_size_y');
        }

        // create markers
        foreach ($records as $entity) {
            $this->Register['current_vars'] = $entity;

            $entity->setModer_panel($this->_getAdminBar($entity));
            $entry_url = get_url(entryUrl($entity, $this->module));
            $entity->setEntry_url($entry_url);
            $entity->setPreview_foto(WWW_ROOT . $this->getImagesPath($entity->getFilename(), null, $preview_size_x, $preview_size_y));
            $entity->setFull_foto(WWW_ROOT . $this->getImagesPath($entity->getFilename()));
            $entity->setFoto_alt(h(preg_replace('#[^\w\d ]+#ui', ' ', $entity->getTitle())));



            // Категории
            $category = $entity->getCategories();
            $entity->setCategory($category[0]);
            $category_name = '';
            foreach($category as $n => $cat) {$category_name .= ($n !== 0 ? ', ' : '').h($cat->getTitle());}
            $entity->setCategory_name($category_name);
            $entity->setCategory_url(get_url($this->getModuleURL('category/' . $entity->getCategory_id())));

            $entity->setProfile_url(getProfileUrl($entity->getAuthor_id()));


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
            return $this->showMessage(__('Can not find category'), $this->getModuleURL());


        $sectionsModel = \OrmManager::getModelInstance($this->module . 'Categories');
        $categories = $sectionsModel->getCatsByIds($id_);
        if (!$categories)
            return $this->showMessage(__('Can not find category'), $this->getModuleURL());
        $cat_title = array();
        foreach($categories as $category) {
            if ($category == false || !is_object($category))
                return $this->showMessage(__('Can not find category'));
            if (!\ACL::checkAccessInList($category->getNo_access()))
                $cat_title[] = $category->getTitle();
        }

        if (empty($cat_title))
            return $this->showMessage(__('Permission denied'), $this->getModuleURL());

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


        $this->Model->bindModel('author');
        $this->Model->bindModel('categories', $categories);
        $records = $this->Model->getCollection($where, $params);

        if (count($records) > 0) {
            $records = $this->AddFields->mergeSelect($records);
        }

        if (\Config::read('use_local_preview', $this->module)) {
            $preview_size_x = \Config::read('img_size_x', $this->module);
            $preview_size_y = \Config::read('img_size_y', $this->module);
        } else {
            $preview_size_x = \Config::read('img_size_x');
            $preview_size_y = \Config::read('img_size_y');
        }

        // create markers
        foreach ($records as $entity) {
            $this->Register['current_vars'] = $entity;

            $entity->setModer_panel($this->_getAdminBar($entity));
            $entry_url = get_url(entryUrl($entity, $this->module));
            $entity->setEntry_url($entry_url);

            $entity->setPreview_foto(WWW_ROOT . $this->getImagesPath($entity->getFilename(), null, $preview_size_x, $preview_size_y));
            $entity->setFoto_alt(h(preg_replace('#[^\w\d ]+#ui', ' ', $entity->getTitle())));

            // Категории
            $category = $entity->getCategories();
            $entity->setCategory($category[0]);
            $category_name = '';
            foreach($category as $n => $cat) {$category_name .= ($n !== 0 ? ', ' : '').h($cat->getTitle());}
            $entity->setCategory_name($category_name);
            $entity->setCategory_url(get_url($this->getModuleURL('category/' . $entity->getCategory_id())));

            $entity->setProfile_url(getProfileUrl($entity->getAuthor_id()));


            //set users_id that are on this page
            $this->setCacheTag(array(
                'user_id_' . $entity->getAuthor_id(),
                'record_id_' . $entity->getId(),
                'category_id_' . $id,
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
            return $this->showMessage(__('Material not found'), $this->getModuleURL());



        $this->Model->bindModel('author');
        $this->Model->bindModel('categories');
        $entity = $this->Model->getById($id);


        if (!$entity)
            return $this->showMessage(__('Material not found'), $this->getModuleURL());

        $categories = $entity->getCategories();
        foreach($categories as $category)
            if (\ACL::checkAccessInList($category->getNo_access()))
                return $this->showMessage(__('Permission denied'));
        $entity->setCategory($categories[0]);

        $entity = $this->AddFields->mergeSelect(array($entity));
        $entity = $entity[0];

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

        $navi = array();
        $navi['add_link'] = (\ACL::turnUser(array($this->module, 'add_materials'))) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
        $navi['module_url'] = get_url($this->getModuleURL());
        $navi['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
        $navi['category_name'] = '';

        foreach($categories as $n => $cat) $navi['category_name'] .= ($n !== 0 ? ', ' : '').h($cat->getTitle());

        $navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
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

        $entity->setMain(WWW_ROOT . $this->getImagesPath($entity->getFilename()));
        $entity->setFoto_alt(h(preg_replace('#[^\w\d ]+#ui', ' ', $entity->getTitle())));
        $entity->setDescription(\PrintText::print_page($entity->getDescription(), $entity->getAuthor() ? $entity->getAuthor()->geteStatus() : 0));

        $next_prev = $this->Model->getNextPrev($id);
        $prev_id = (!empty($next_prev['prev'])) ? $next_prev['prev']->getId() : $id;
        $next_id = (!empty($next_prev['next'])) ? $next_prev['next']->getId() : $id;

        $entity->setPrevious_url(get_url($this->getModuleURL('view/' . $prev_id)));
        $entity->setNext_url(get_url($this->getModuleURL('view/' . $next_id)));



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
            return $this->showMessage(__('Can not find user'), $this->getModuleURL());


        $usersModel = \OrmManager::getModelInstance('Users');
        $user = $usersModel->getById($id);
        if (!$user)
            return $this->showMessage(__('Can not find user'), $this->getModuleURL());
        if (\ACL::checkAccessInList($user->getNo_access()))
            return $this->showMessage(__('Permission denied'), $this->getModuleURL());


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


        $this->Model->bindModel('author');
        $this->Model->bindModel('categories');
        $records = $this->Model->getCollection($where, $params);

        if (count($records) > 0) {
            $records = $this->AddFields->mergeSelect($records);
        }

        if (\Config::read('use_local_preview', $this->module)) {
            $preview_size_x = \Config::read('img_size_x', $this->module);
            $preview_size_y = \Config::read('img_size_y', $this->module);
        } else {
            $preview_size_x = \Config::read('img_size_x');
            $preview_size_y = \Config::read('img_size_y');
        }

        // create markers
        foreach ($records as $entity) {
            $this->Register['current_vars'] = $entity;

            $entity->setModer_panel($this->_getAdminBar($entity));
            $entry_url = get_url(entryUrl($entity, $this->module));
            $entity->setEntry_url($entry_url);

            $entity->setPreview_foto(WWW_ROOT . $this->getImagesPath($entity->getFilename(), null, $preview_size_x, $preview_size_y));
            $entity->setFoto_alt(h(preg_replace('#[^\w\d ]+#ui', ' ', $entity->getTitle())));


            $category = $entity->getCategories();
            $entity->setCategory($category[0]);
            $category_name = '';
            foreach($category as $n => $cat) {$category_name .= ($n !== 0 ? ', ' : '').h($cat->getTitle());}
            $entity->setCategory_name($category_name);
            $entity->setCategory_url(get_url($this->getModuleURL('category/' . $entity->getCategory_id())));

            $entity->setProfile_url(getProfileUrl($entity->getAuthor_id()));


            //set users_id that are on this page
            $this->setCacheTag(array(
                'user_id_' . $entity->getAuthor_id(),
                'record_id_' . $entity->getId(),
                'category_id_' . $id,
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
        if (!\ACL::turnUser(array($this->module, 'record_comments_management')))
            $markers['commented'] = ' disabled="disabled"';


        $markers['action'] = get_url($this->getModuleURL('add/'));

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
            return $this->showMessage(__('Some error occurred'));
        }
        $error = '';


        // Check additional fields if an exists.
        // This must be doing after define $error variable.
        $_addFields = $this->AddFields->checkFields();
        if (is_string($_addFields))
            $error .= $_addFields;


        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $title = trim(mb_substr($_POST['title'], 0, 128));
        $description = trim($_POST['mainText']);
        $commented = (!empty($_POST['commented'])) ? 1 : 0;

        if (is_array($_POST['cats_selector']) and \Config::read('use_multicategories'))
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


        // Проверяем, заполнены ли обязательные поля
          //validation data class
        if (empty($in_cat))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Category')) . '</li>' . "\n";
        if (empty($title))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Foto name')) . '</li>' . "\n";
        elseif (!\Validate::cha_val($title, V_TITLE))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Foto name')) . '</li>' . "\n";
        $max_lenght = \Config::read('description_lenght', $this->module);
        if ($max_lenght <= 0)
            $max_lenght = 1000;
        if (mb_strlen($description) > $max_lenght)
            $error .= '<li>' . sprintf(__('Very big "param"'),__('Description'),$max_lenght) . '</li>' . "\n";



        /* check file */
        if (empty($_FILES['foto']['name'])) {
            $error .= '<li>' . __('No attachment foto') . '</li>' . "\n";
        } else {
            if ($_FILES['foto']['size'] > $this->getMaxSize())
                $error .= '<li>' . sprintf(__('Very big file'),$_FILES['foto']['name'], getSimpleFileSize($this->getMaxSize())) . '</li>' . "\n";
            if (!isImageFile($_FILES['foto']))
                $error .= '<li>' . sprintf(__('Wrong file format'),$_FILES['foto']['name']) . '</li>' . "\n";
        }


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
            return $this->showMessage($error_msg, $this->getModuleURL('add_form/'));
        }


        // Защита от того, чтобы один пользователь не добавил
        // 100 материалов за одну минуту
        if (isset($_SESSION['unix_last_post']) and ( time() - $_SESSION['unix_last_post'] < 10 )) {
            return $this->showMessage(__('Material has been added'),$this->getModuleURL('add_form/'));
        }




        //remove cache
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('module_' . $this->module));
        // Формируем SQL-запрос на добавление темы
        $data = array(
            'title' => $title,
            'description' => mb_substr($description, 0, $max_lenght),
            'date' => new \Expr('NOW()'),
            'author_id' => $_SESSION['user']['id'],
            'category_id' => $in_cat,
            'filename' => '',
            'commented' => $commented,
        );

        // Save additional fields
        $data = $this->AddFields->set($_addFields, $data);

        $className = \OrmManager::getEntityName($this->module);
        $entity = new $className($data);
        if ($entity) {
            $entity->setId($entity->save());


            /* save full and resample images */
            $ext = strtolower(strchr($_FILES['foto']['name'], '.'));
            $files_dir = ROOT . '/data/images/' . $this->module . '/';

            if (!file_exists($files_dir)) mkdir($files_dir,0766);
            $save_path = $files_dir . $entity->getId() . $ext;

            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $save_path))
                $error_flag = true;
            elseif (!chmod($save_path, 0644))
                $error_flag = true;

            /* if an error when coping */
            if (!empty($error_flag) && $error_flag) {
                $entity->delete();
                $error_msg = '<p class="errorMsg">' . __('Some error in form') . '</p>'
                        . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
                return $this->showMessage($error_msg, $this->getModuleURL('add_form/'));
            }

            $entity->setFilename($entity->getId() . $ext);
            $entity->save();


            // Create watermark and resample image
            $watermark_path = ROOT . '/data/img/' . (\Config::read('watermark_type') == '1' ? 'watermark_text.png' : \Config::read('watermark_img'));
            if (\Config::read('use_watermarks') && !empty($watermark_path) && file_exists($watermark_path)) {
                $waterObj = new \DrsImg;
                $waterObj->createWaterMark($save_path, $watermark_path);
            }

            // hook for plugins
            \Events::init('new_entity', array(
                'entity' => $entity,
                'module' => $this->module,
            ));

            //clean cache
            $this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module));
            if ($this->isLogging)
                \Logination::write('adding ' . $this->module, $this->module . ' id(' . $entity->getId() . ')');
            return $this->showMessage(__('Material has been added'), entryUrl($entity, $this->module),'ok');
        } else
            return $this->showMessage(__('Some error occurred'),$this->getModuleURL('add_form/'));
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
        if (!empty($id) && !is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
        if ($id < 1)
            return $this->showMessage(__('Material not found'), $this->getModuleURL());

        $this->page_title = __('Material editing');

        $this->Model->bindModel('author');
        $this->Model->bindModel('categories');
        $entity = $this->Model->getById($id);

        if (!$entity)
            return $this->showMessage(__('Material not found'), $this->getModuleURL());


        \ACL::turnUser(array($this->module, 'view_list'),true);
        if (!\ACL::turnUser(array($this->module, 'edit_materials'))
                && (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
                && \ACL::turnUser(array($this->module, 'edit_mine_materials'))) === false) {
            return $this->showMessage(__('Permission denied'), $this->getModuleURL());
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
        $markers->setMain_text(\PrintText::print_page($markers->getDescription(), $entity->getAuthor() ? $markers->getAuthor()->geteStatus() : 0));
        $markers->setId_cat($markers->getCategory_id());

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
        $markers->setAction(get_url($this->getModuleURL('update/' . $markers->getId())));
        $markers->setCommented($commented);






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
                || !isset($_POST['title'])) {
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
            return $this->showMessage(__('Permission denied'),entryUrl($entity, $this->module));
        }


        // Check additional fields if an exists.
        // This must be doing after define $error variable.
        $_addFields = $this->AddFields->checkFields();
        if (is_string($_addFields))
            $error .= $_addFields;


        

        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $title = trim(mb_substr($_POST['title'], 0, 128));
        $description = trim($_POST['mainText']);
        $commented = (!empty($_POST['commented'])) ? 1 : 0;

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


        // Проверяем, заполнены ли обязательные поля
        if (empty($title))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Foto name')) . '</li>' . "\n";
        elseif (!\Validate::cha_val($title, V_TITLE))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Foto name')) . '</li>' . "\n";
        $max_lenght = \Config::read('description_lenght', $this->module);
        if ($max_lenght <= 0)
            $max_lenght = 1000;
        if (mb_strlen($description) > $max_lenght)
            $error .= '<li>' . sprintf(__('Very big "param"'), __('Description'), $max_lenght) . '</li>' . "\n";



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
            return $this->showMessage($error_msg, $this->getModuleURL('edit_form/'.$id));
        }


        //remove cache
        $this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module, 'record_id_' . $id));


        $data = array(
            'title' => $title,
            'category_id' => $in_cat,
            'description' => mb_substr($description, 0, $max_lenght),
            'commented' => $commented,
        );

        // Save additional fields
        $data = $this->AddFields->set($_addFields, $data);

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
        \ACL::turnUser(array($this->module, 'view_list'),true);
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

        if (\ACL::turnUser(array($this->module, 'edit_materials'))
                || (!empty($_SESSION['user']['id']) && $uid == $_SESSION['user']['id']
                && \ACL::turnUser(array($this->module, 'edit_mine_materials')))) {
            $moder_panel .= get_link('', $this->getModuleURL('edit_form/' . $id), array('class' => 'drs-edit')) . '&nbsp;';
        }

        if (\ACL::turnUser(array($this->module, 'up_materials'))) {
            $moder_panel .= get_link('', $this->getModuleURL('upper/' . $id), array('id' => 'fum'.$record->getId(), 'class' => 'drs-up', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fum".$record->getId()."')}; return false")) . '&nbsp;';
        }

        if (\ACL::turnUser(array($this->module, 'delete_materials'))
                || (!empty($_SESSION['user']['id']) && $uid == $_SESSION['user']['id']
                && \ACL::turnUser(array($this->module, 'delete_mine_materials')))) {
            $moder_panel .= get_link('', $this->getModuleURL('delete/' . $id), array('id' => 'fdm'.$record->getId(), 'class' => 'drs-delete', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fdm".$record->getId()."')}; return false")) . '&nbsp;';
        }
        return $moder_panel;
    }

    /**
     * RSS
     *
     */
    public function rss() {
        \ACL::turnUser(array($this->module, 'view_list'),true);
        
        $options = array(
            'query_params' => array(
                'cond' => array('available' => '1')
            ),
            "fields_item" => array(
                "enclosure" => function($record, $sitename) {
                    $images = array();
                    $images[] = array(
                                    "url" => $sitename . $this->getImagesPath($entity->getFilename()),
                                    "type" => 'image/'.substr(strrchr($record->getFilename(), "."), 1)
                                );
                    return $images;
                }
            )
        );
        
        include_once ROOT . '/sys/inc/rss.php';
    }

}
