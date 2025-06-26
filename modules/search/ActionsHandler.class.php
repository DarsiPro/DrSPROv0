<?php
/**
* @project    DarsiPro CMS
* @package    Search Module
* @url        https://darsi.pro
*/



namespace SearchModule;

class ActionsHandler extends \Module {

    /**
     * @module_title  title of module
     */
    public $module_title = 'Поиск';

    /**
     * @module module indentifier
     */
    public $module = 'search';

    /**
     * @var int
     */
    private $minInputStr = 5;

    /**
     * @var array
     */
    public $tables = array('forumPosts', 'stat', 'news', 'loads');

    /**
     * @var boolean
     */
    private $returnForm = true;

    function __construct($params) {
        parent::__construct($params);

        $this->setModel();
    }

    /**
     * @return string - $this->_view
     *
     * Doing search and build page with results
     */
    public function index() {
        //check index
        $this->__checkIndex();

        $minInput = \Config::read('min_lenght', $this->module);
        if (!empty($minInput))
            $this->minInputStr = (int) $minInput;

        $html = null;
        $error = null;
        $results = null;

        if (isset($_POST['m'])) {
            $modules = array();
            foreach ($_POST['m'] as $m) {
                if ($m == 'forum' or $m == 'news'
                        or $m == 'stat' or $m == 'loads')
                    Array_push($modules, $m);
            }
        } else {
            $modules = array('forum', 'news', 'stat', 'loads');
        }
        $_SESSION['m'] = $modules;

        if (isset($_POST['search']) || isset($_GET['search'])) {
            $str = (isset($_POST['search'])) ? h($_POST['search']) : '';
            if (empty($str))
                $str = (isset($_GET['search'])) ? h($_GET['search']) : '';
            if (!is_string($str))
                $str = (string) $str;
            $str = trim($str);


            if (empty($str) || mb_strlen($str) < $this->minInputStr)
                $error = $error . sprintf(__('Very small query'), $this->minInputStr);


            if ($this->cached) {
                $this->cacheKey .= '_' . md5($str);
                if ($this->Cache->check($this->cacheKey)) {
                    $html = $this->Cache->read($this->cacheKey);
                    return $this->_view($html);
                }
            }

            $_SESSION['search_query'] = $str;
            if (!empty($error)) {
                $_SESSION['errorForm'] = array();
                $_SESSION['errorForm']['errors'] = $error;
            } else {

                $results = $this->__search($str, $modules);
                if (count($results) && is_array($results)) {
                    
                    foreach ($results as $result) {

                        $className = \OrmManager::getModelNameFromModule($result->getModule());
                        $Model = new $className;
                        $Model->bindModel('attaches');
                        $entity = $Model->getById($result->getEntity_id());

                        if ($result->getModule() != 'forum') {
                            $entry_url = get_url(entryUrl($entity, $result->getModule()));
                            $result->setEntry_url($entry_url);

                            $sectionsModel = \OrmManager::getModelInstance($result->getModule() . 'Categories');
                            $category = $sectionsModel->getById($entity->getCategory_id());

                            $result->setCategory_url(get_url('/' . $result->getModule() . '/category/' . $entity->getCategory_id()));
                            $result->setCategory_title(h($category->getTitle()));

                            // Cut announce
                            $announce = \PrintText::getAnnounce($entity->getMain(), '', \Config::read('announce_lenght', $result->getModule()), $entity);
                            $announce = str_replace($str, '<strong>' . $str . '</strong>', $announce);

                            // replace image tags in text
                            $attaches = $entity->getAttaches();
                            if (!empty($attaches) && count($attaches) > 0) {
                                foreach ($attaches as $attach) {
                                    if ($attach->getIs_image() == '1') {
                                        $announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number(), $result->getModule());
                                    }
                                }
                            }

                            if ($entity->getTags())
                                $result->setTags(atrim(explode(',', $entity->getTags())));

                            $result->setTitle($entity->getTitle());
                            $result->setAnnounce($announce);
                            $result->setViews($entity->getViews());
                            $result->setComments($entity->getComments());
                            $result->setDate($entity->getDate());
                        } else {
                            $postsModel = \OrmManager::getModelInstance('ForumPosts');
                            $post = $postsModel->getById($result->getEntity_id());
                            if (!$post) break;
                            $id_theme = $post->getId_theme();
                            $themesModel = \OrmManager::getModelInstance('ForumThemes');
                            $theme = $themesModel->getById($id_theme);
                            if (!$theme) break;
                            $announce = \PrintText::print_page($result->getIndex());
                            $announce = str_replace($str, '<strong>' . $str . '</strong>', $announce);
                            $result->setTitle($theme->getTitle());
                            $result->setAnnounce($announce);
                            $result->setDate($post->getTime());

                            // Узнаем каким по счету является редактируемый пост в своей теме
                            $post_num = $postsModel->getTotal(
                                array(
                                    'order' => 'id ASC',
                                    'cond' => array(
                                        'id_theme' => $id_theme,
                                        '((time = \'' . $post->getTime() . '\' AND id < ' . $post->getId() . ') OR time < \'' . $post->getTime() . '\')',
                                    ),
                                )
                            );
                            // Вычисляем на какой странице находится пост
                            $near_pages = \Config::read('posts_per_page', $result->getModule());
                            $page = 0;
                            if (($post_num + 1) > $near_pages)
                                $page = ceil(($post_num + 1) / $near_pages);

                            $entry_url = get_url('/'.$result->getModule().'/view_theme/' . $id_theme . ($page ? '/?page=' . $page : '') . '#post' . ($post_num + 1));
                            $result->setEntry_url($entry_url);
                        }

                        $result->setModule(__($result->getModule()));
                    }
                } else {
                    $error = __('No results'); // TODO
                }
            }
        } else {
            $_SESSION['search_query'] = '';
        }



        // Nav block
        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator') . $this->module_title;
        $this->_globalize($nav);


        $this->page_title = $this->module_title;
        if (!empty($_POST['search']))
            $this->page_title .= ' - ' . h($_POST['search']);


        $this->returnForm = false;
        $form = $this->form();
        $source = $this->render('search_list.html', array('context' => array(
                'results' => $results,
                'form' => $form,
                'error' => $error,
                )));


        //write into cache
        if ($this->cached && !empty($str)) {
            //set users_id that are on this page
            $this->setCacheTag(array(
                'search_str_' . $str,
            ));
            $this->cacheKey .= '_' . md5($str);
            $this->Cache->write($source, $this->cacheKey, $this->cacheTags);
        }

        return $this->_view($source);
    }

    /**
     * @return string search form
     */
    public function form() {
        $markers = array(
            'action' => get_url($this->getModuleURL()),
            'search' => '',
            'forum' => '0',
            'news' => '0',
            'stat' => '0',
            'loads' => '0',
        );


        //if an errors
        if (isset($_SESSION['errorForm'])) {
            $markers['info'] = $this->render('infomessage.html', array('context' => array(
                    'message' => $_SESSION['errorForm']['errors'],
                    )));
            unset($_SESSION['errorForm']);
        }

        $markers['search'] = $_SESSION['search_query'];

        foreach ($_SESSION['m'] as $m) {
            $markers[$m] = 'checked';
        }

        $source = $this->render('search_form.html', array('context' => $markers));
        return ($this->returnForm) ? $this->_view($source) : $source;
    }

    /**
     * @return boolean
     */
    private function __checkIndex() {
        $meta_file = ROOT . $this->getTmpPath('meta.dat');
        if (file_exists($meta_file) && is_readable($meta_file)) {
            $meta = unserialize(file_get_contents($meta_file));
            if (!empty($meta['expire']) && $meta['expire'] > time()) {
                return true;
            } else {
                $this->__createIndex();
            }
        } else {
            touchDir(ROOT . $this->getTmpPath());
            $this->__createIndex();
        }

        $index_interval = intval(\Config::read('index_interval', $this->module));
        if ($index_interval < 1)
            $index_interval = 1;
        $meta['expire'] = (time() + ($index_interval * 84000));
        file_put_contents($meta_file, serialize($meta));
        return true;
    }

    /**
     * @param string $str
     * @return array
     *
     * Send request and return search results
     */
    private function __search($str, $modules) {
        $words = explode(' ', $str);
        $_words = array();
        foreach ($words as $key => $word) {
            $word = $this->__filterText($word);
            if (mb_strlen($word) < $this->minInputStr)
                continue;
            $_words[] = $word;
        }
        if (count($_words) < 1)
            return array();
        $string = resc(implode('* ', $_words) . '*');

        //query
        $limit = intval(\Config::read('per_page', $this->module));
        if ($limit < 1)
            $limit = 10;
        $results = $this->Model->getSearchResults($string, $limit, $modules);
        return $results;
    }

    /**
     *
     *
     * Create index for search engine
     */
    private function __createIndex() {
        if (function_exists('ignore_user_abort'))
            ignore_user_abort();
        if (function_exists('set_time_limit'))
            set_time_limit(180);


        $this->Model->truncateTable();
        
        foreach ($this->tables as $table) {
            $className = \OrmManager::getModelNameFromModule($table);
            $Model = new $className;
            $records = $Model->getCollection();


            if (count($records) && is_array($records)) {
                foreach ($records as $rec) {

                    switch ($table) {
                        case 'news':
                        case 'stat':
                        case 'loads':
                            $text = $rec->getTitle() . ' ' . $rec->getMain() . ' ' . $rec->getTags();
                            if (mb_strlen($text) < $this->minInputStr || !is_string($text))
                                continue;
                            $entity_view = '/view/';
                            $module = $table;
                            $entity_id = $rec->getId();
                            break;

                        case 'forumPosts':
                            $text = $rec->getMessage();
                            $entity_view = '/view_theme/';
                            $module = 'forum';
                            $entity_id = $rec->getId();
                            break;

                        case 'forumThemes':
                            break;

                        default:
                            $text = $rec->gettitle() . ' ' . $rec->getMain() . ' ' . $rec->getTags();
                            if (mb_strlen($text) < $this->minInputStr || !is_string($text))
                                continue;
                            $entity_view = '/view/';
                            $module = $table;
                            break;
                    }


                    //we must update record if an exists
                    $data = array(
                        'index' => $text,
                        'entity_id' => $entity_id,
                        'entity_table' => $table,
                        'entity_view' => $entity_view,
                        'module' => $module,
                        'date' => new \Expr('NOW()'),
                    );
                    $entity = new \SearchModule\ORM\SearchEntity($data);
                    $entity->save();
                }
            }
        }
    }

    /**
     * @param string $str
     * @return string
     *
     * Cut HTML and BB tags. Also another chars
     */
    private function __filterText($str) {
        $str = preg_replace('#<[^>]*>|\[[^\]]*\]|[,\.=\'"\|\{\}/\\_\+\?\#<>:;\)\(`\-0-9]#iu', '', $str);
        //$str = preg_replace('#(^| )[^ ]{1,2}( |$)#iu', ' ', $str);
        //$str_to_array = explode(' ', mb_strtolower($str));
        //$str_to_array = array_unique($str_to_array);
        //$str = implode(' ', $str_to_array);
        return (!empty($str)) ? $str : false;
    }

}
