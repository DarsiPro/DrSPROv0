<?php
/**
* @project    DarsiPro CMS
* @package    Module Class
* @url        https://darsi.pro
*/


class Module {

    /**
    * @page_title title of the page
    */
    public $page_title = '';
    /**
    * @var string
    */
    public $page_meta_keywords;
    /**
    * @var string
    */
    public $page_meta_description;
    /**
    * @template layout for module
    */
    public $template = 'default';
    /**
    * @categories list of categories
    */
    public $categories = '';
    /**
    * @module_title title for module
    */
    public $module_title = '';
    /**
    * @module current module
    */
    public $module = '';
    /**
    * @cacheTags Cache tags
    */
    public $cacheTags = array();
    /**
    * @cached   use the cache engine
    */
    protected $cached = true;

    /**
    * @var (str)   comments block
    */
    protected $comments = '';

    /**
    * @var (str)   add comments form
    */
    protected $comments_form = '';

    /**
    * @var    database object
    */
    protected $Database;
    /**
    * uses for work with actions log
    *
    * @var    logination object
    */
    protected $Log;
    /**
    * uses for work with parser (chuncks, snippets, global markers ...)
    *
    * @var    parser object
    */
    protected $Parser;
    /**
    * contains system settings
    *
    * @var (array)   system settings
    */
    public $set;

    /**
     * @var object
     */
    protected $AddFields = false;

    /**
     * if true - counter not worck
     *
     * @var boolean
     */
    public $counter = true;

    /**
     * Use wrapper?
     *
     * @var boolean
     */
    protected $wrap = true;

    /**
     * @var object
     */
    public $Register;

    /**
     * @var object
     */
    public $Model;



    /**
     * @var array
     */
    protected $globalMarkers = array(
        'module' => '',
        'navigation' => '',
        'pagination' => '',
        'meta' => '',
        'add_link' => '',
        'comments_pagination' => '',
        'comments' => '',
        'comments_form' => '',
		'page_num' => 1,
        'pages_cnt' => 1,
    );




    /**
     * @param array $params - array with modul, action and params
     *
     * Initialize needed objects adn set needed variables
     */
    function __construct($params)
    {
        $this->Register = Register::getInstance();
        $this->Register['module'] = $params[0];
        $this->Register['action'] = $params[1];
        $this->Register['params'] = $params;

        // Use for templater (layout)
        $this->template = $this->module;


        $this->View = new Viewer_Manager(array('layout' => $this->template));
        $this->DB = getDB();
        $this->isLogging = false;
        if (Config::read('__secure__.system_log'))
            $this->isLogging = true;

        // init aditional fields
        $this->AddFields = new DrsAddFields;
        $this->AddFields->module = $this->module;

        $this->beforeRender();

        $this->page_title = (Config::read('title', $this->module)) ? h(Config::read('title', $this->module)) : h($this->module);
        $this->params = $params;

        //cache
        $this->Cache = new Cache;
        if (Config::read('cache') == 1) {
            $this->cached = true;
            $this->cacheKey = $this->getKeyForCache($params);
            $this->setCacheTag(array('module_' . $params[0]));
            if (!empty($params[1])) $this->setCacheTag(array('action_' . $params[1]));
        } else {
            $this->cached = false;
        }

        //meta tags
        $this->page_meta_keywords = h(Config::read('keywords', $this->module));
        if (empty($this->page_meta_keywords)) $this->page_meta_keywords = h(Config::read('meta_keywords'));
        $this->page_meta_description = h(Config::read('description', $this->module));
        if (empty($this->page_meta_description)) $this->page_meta_description = h(Config::read('meta_description'));
    }



    protected function setModel()
    {
        $this->Model = OrmManager::getModelInstance(ucfirst($this->module));
    }


    /**
     * Uses for before render
     * All code in this function will be worked before
     * begin render page and launch controller(module)
     *
     * @return none
     */
    protected function beforeRender()
    {
        if (isset($_SESSION['page'])) unset($_SESSION['page']);
        if (isset($_SESSION['pagecnt'])) unset($_SESSION['pagecnt']);
    }


    /**
     * Uses for after render
     * All code in this function will be worked after
     * render page.
     *
     * @return none
     */
    protected function afterRender()
    {
        // Cron
        if (Config::read('auto_sitemap'))
            drsCron('createSitemap', 86400);
        drsCron('createRobots', 86400);


        /*
        * counter ( if active )
        * and if we not in admin panel
        */
        if ($this->counter === false) return;
            
        Events::init('user_pageviewed',$this);
    }


    /**
    * @param string $content  data for parse and view
    * @access   protected
    */
    protected function _view($content)
    {

        if (!empty($this->template) && $this->wrap == true) {
            Events::init('before_parse_layout', $this);

            $this->View->setLayout($this->template);
            $markers = $this->getGlobalMarkers();
            $markers['content'] = $content;

            // Cache global markers
            if ($this->cached) {
                if ($this->Cache->check($this->cacheKey . '_global_markers')) {
                    $gdata = $this->Cache->read($this->cacheKey . '_global_markers');
                    $this->globalMarkers = array_merge($this->globalMarkers, unserialize($gdata));
                } else {
                    $gdata = serialize($this->globalMarkers);
                    $this->Cache->write($gdata, $this->cacheKey . '_global_markers', $this->cacheTags);
                }
            }


            $Register = Register::getInstance();
            $boot_time = round(getMicroTime() - $Register['boot_start_time'], 4);
            $markers = array_merge($markers, array('boot_time' => $boot_time));

            $output = $this->render('main.html', $markers);
        } else {
            $output = $content;
        }


        $this->afterRender();

        echo $output;



        if (Config::read('debug_mode') == 1) {
            echo DrsDebug::getBody();
        }

    }


    protected function render($fileName, array $markers = array())
    {
        $additionalMarkers = $this->getGlobalMarkers();
        $this->_globalize($additionalMarkers);
        $source = $this->View->view($fileName, array_merge($markers, $this->globalMarkers));
        return $source;
    }


    protected function renderString($string, array $markers = array())
    {
        $additionalMarkers = $this->getGlobalMarkers();
        $this->_globalize($additionalMarkers);
        $source = $this->View->parseTemplate($string, array_merge($markers, $this->globalMarkers));
        return $source;
    }


    protected function getGlobalMarkers($html = '')
    {
        $markers = array();
        $obj = $this;// Для анонимных функций

        $markers['module'] = $this->module;
        $markers['title'] = $this->page_title;
        $markers['meta_description'] = $this->page_meta_description;
        $markers['meta_keywords'] = $this->page_meta_keywords;
        $markers['module_title'] = $this->module_title;
        $markers['params'] = $this->params;
        $markers['categories'] = $this->categories;
        $markers['comments'] = $this->comments;
        $markers['comments_form'] = $this->comments_form;
        $markers['page_num'] = (!empty($this->Register['page'])) ? intval($this->Register['page']) : 1;
        $markers['pages_cnt'] = (!empty($this->Register['pagescnt'])) ? intval($this->Register['pagescnt']) : 1;
        $markers['page_prev'] = (!empty($this->Register['prev_page_link'])) ? $this->Register['prev_page_link'] : '';
        $markers['page_next'] = (!empty($this->Register['next_page_link'])) ? $this->Register['next_page_link'] : '';

        if (isset($this->params) && is_array($this->params)) {
            $markers['action'] = count($this->params) > 1 ? $this->params[1] : 'index';
            $markers['current_id'] = count($this->params) > 2 ? $this->params[2] : null;
        }

        $markers['used_https'] = used_https();
		$site_url = (used_https() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].urldecode($_SERVER['REQUEST_URI']);
        $markers['site_url'] = array_merge( parse_url($site_url), array('full' => $site_url) );
        $markers['server_name'] = $_SERVER['SERVER_NAME'];
        $markers['request_url'] = urldecode($_SERVER['REQUEST_URI']);
        $markers['wday'] = date("D");
        $markers['wday_n'] = date("w");
        $markers['date'] = date("d-m-Y");
        $markers['time'] = date("H:i");
        $markers['hour'] = date("G");
        $markers['minute'] = date("i");
        $markers['day'] = date("j");
        $markers['month'] = date("n");
        $markers['year'] = date("Y");

        $path = Config::read('smiles_set');
        $path = (!empty($path) ? $path : 'drspro');
        $markers['smiles_set'] = $path;

        $markers['smiles_list'] = function() use($path) {
            $path = ROOT . '/data/img/smiles/' . $path . '/info.php';
            include $path;
            if (isset($smilesList) && is_array($smilesList)) {
                return (isset($smilesInfo) && isset($smilesInfo['show_count'])) ? array_slice($smilesList, 0, $smilesInfo['show_count']) : $smilesList;
            } else {
                return array();
            }
        };

        $markers['powered_by'] = 'DarsiPro CMS';
        $markers['site_title'] = Config::read('site_title');

        $markers['template_path'] = get_url('/template/' . getTemplate());
        $markers['www_root'] = WWW_ROOT;
        $markers['lang'] = getLang();
        $markers['langs'] = (count(getPermittedLangs()) >= 1) ? getPermittedLangs() : '';

        $markers['mainmenu'] = function() use($obj) {

            $menu_conf_file = ROOT . '/sys/settings/menu.dat';
            if (!file_exists($menu_conf_file)) return false;
            $menudata = unserialize(file_get_contents($menu_conf_file));


            if (!empty($menudata) && count($menudata) > 0) {
                $out = $obj->buildMenuNode($menudata, 'class="ds_MainMenu"');
            } else {
                return false;
            }
            return $out;
        };

        // Для получения некоторых значений регистра, так можно сэкономить на fetch и запросах к бд, если нужные данные уже получались на этойже странице.
        $markers['register'] = array();
        $markers['register']['current_vars'] = $obj->Register['current_vars'];
        $markers['register']['categories'] = $obj->Register['categories'];
        $markers['register']['shared'] = $obj->Register['shared'];


        /** Метки, значение которых вычисляется только если они были вызваны в шаблонизаторе */
        $user = array();
        $markers['user'] = function() use(&$user) {

            if (isset($_SESSION['user']) && isset($_SESSION['user']['name'])) {
                $user = $_SESSION['user'];
                unset($user['passw']);
                $user['profile'] = getProfileUrl($_SESSION['user']['id']);
                $user['id'] = $_SESSION['user']['id'];
                $user['name'] = $_SESSION['user']['name'];
                $user['group_id'] = $_SESSION['user']['status'];
                $user['group'] = function() {
                    $userGroup = ACL::get_group($_SESSION['user']['status']);
                    return $userGroup['title'];
                };

                $get_difference_time = (time() - strtotime($_SESSION['user']['puttime'])) / 86400;
                if ($get_difference_time < 0) $get_difference_time = 0;

                $user['reg_days'] = round($get_difference_time);

                $user['avatar_url'] = getAvatar($_SESSION['user']['id']);

                $user['unread_pm'] = function() {
                    $Cache = new Cache;
                    $Cache->prefix = 'messages';
                    $Cache->cacheDir = 'sys/cache/users/new_pm/';
                    if ($Cache->check('user_' . $_SESSION['user']['id']))
                        $res = $Cache->read('user_' . $_SESSION['user']['id']);
                    else {
                        $usersModel = OrmManager::getModelInstance('Users');
                        $res = $usersModel->getNewPmMessages($_SESSION['user']['id']);
                        $Cache->write($res, 'user_' . $_SESSION['user']['id'],array());
                    }
                    if ($res)
                        return $res;
                    else
                        return 0;
                };


            } else {
                $user['profile'] = get_url('/users/add_form/');
                $user['id'] = 0;
                $user['name'] = 'Гость'; //TODO
                $user['group'] = 'Гости';
                $user['reg_days'] = '';
                $user['unread_pm'] = '';
                $user['avatar_url'] = getAvatar();
            }

            $user['admin_access'] = (ACL::turnUser(array('__panel__', 'entry'))) ? '1' : '0';

            return $user;
        };


        // today borned users
        $markers['today_born_users'] = function() {
            $today_born = getBornTodayUsers();
            $tbout = '';
            if (count($today_born) > 0) {
                $names = array();
                foreach ($today_born as $user) {
                    $names[] = get_link($user['name'], getProfileUrl($user['id'], true));
                }
                $tbout = implode(', ', $names);
            }
            return (!empty($tbout)) ? $tbout : __('No birthdays today',true,'users');
        };
        
        $markers['count_users'] = function() {
                return getAllUsersCount();
        };
        $markers['users_edit'] = function() {return (ACL::turnUser(array('users', 'edit_users'))) ? '1' : '0';};
        
        
        
        $markers['admin_access'] = (ACL::turnUser(array('__panel__', 'entry'))) ? '1' : '0';
        $markers['rss'] = function() use($obj) {return $obj->getRss();};
        $markers['users_groups'] = function() {return ACL::getGroups();};
        
        
        
        $markers = Events::init('after_parse_global_markers', $markers);

        return $markers;
    }

    /**
     * Save markers. Get list of markers
     * and content wthat will be instead markers
     * Before view this markers will be replaced in
     * all content
     *
     * @param array $markers - marker->value
     * @return none
     */
    protected function _globalize($markers = array())
    {
        $this->globalMarkers = array_merge($this->globalMarkers, $markers);
    }

    /**
    * @return     list with RSS links
    */
    protected function getRss()
    {
        $rss = '';
        $modules = glob(ROOT.'/modules/*');
        foreach ($modules as $module):
            if (is_dir($module)
                and preg_match('#/(\w+)$#i', $module, $module_name)
                and Config::read('active', $module_name[1])
                and Config::read('rss_'.$module_name[1], 'rss')):

                $rss .= get_img('/template/' . getTemplate() . '/img/rss_icon_mini.png') .
                    get_link(__(ucfirst($module_name[1]) . ' RSS'), '/'.$module_name[1].'/rss/') .
                    '<br />';

            endif;
        endforeach;
        return $rss;
    }



    /**
     * @return string
     *
     * Build menu which creating in Admin Panel
     */
    protected function builMainMenu()
    {
        $menu_conf_file = ROOT . '/sys/settings/menu.dat';
        if (!file_exists($menu_conf_file)) return false;
        $menudata = unserialize(file_get_contents($menu_conf_file));


        if (!empty($menudata) && count($menudata) > 0) {
            $out = $this->buildMenuNode($menudata, 'class="ds_MainMenu"');
        } else {
            return false;
        }
        return $out;
    }


    /**
     * @param  $node
     * @param string $class
     * @return string
     */
    protected function buildMenuNode($node, $class = 'class="ds_MainMenu"')
    {
        $out = '<ul ' . $class . '>';
        foreach ($node as $point) {
            if (empty($point['title']) || empty($point['url'])) continue;
            if (!empty($point['sub']) && count($point['sub']) > 0) {
               $subClass = 'class="ds_SubMenu"';
            } else {
               $subClass = '';
            }
            $out .= '<li ' . $subClass . '>';


            $out .= $point['prefix'];
            $target = (!empty($point['newwin'])) ? ' target="_blank"' : '';
            $out .= '<a href="' . get_url($point['url']) . '"' . $target . '>' . $point['title'] . '</a>';
            $out .= $point['sufix'];

            if (!empty($point['sub']) && count($point['sub']) > 0) {
                $out .= $this->buildMenuNode($point['sub']);
            }

            $out .= '</li>';
        }
        $out .= '</ul>';
        return $out;
    }



    /**
    * @param  mixed $tag
    * @return boolean
    */
    protected function setCacheTag($tag)
    {
        if ((Config::read('cache') == true || Config::read('cache') == 1) && $this->cached === true) {
            if (is_array($tag)) {
                foreach ($tag as $_tag) {
                    $this->setCacheTag($_tag);
                }
            } else {
                $this->cacheTags[] = $tag;
                return true;
            }
        }
        return false;
    }


    /**
    * create unique id for cache file
     *
    * @param array $params <module>[ action [ param1 [ param2 ]]]
    * @return string
    */
    private function getKeyForCache($params)
    {
        $cacheId = '';
        foreach ($params as $value) {
            if (is_array($value)) {
                foreach ($value as $_value) {
                    $cacheId = $cacheId . $_value . '_';
                }
                continue;
            }
            $cacheId = $cacheId . $value . '_';
        }

        if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
                $cacheId = $cacheId . intval($_GET['page']) . '_';
        }
        if (!empty($_GET['order'])) {
            $order = (string)$_GET['order'];
            if (!empty($order)) {
                $order = substr($order, 0, 10);
                $cacheId .= $order . '_';
            }
        }
        if (!empty($_GET['asc'])) {
            $cacheId .= 'asc_';
        }
        $cacheId = (!empty($_SESSION['user']['status'])) ? $cacheId . $_SESSION['user']['status'] : $cacheId . 'guest';
        return $cacheId;
    }

    private function getCatChildren($cat_ids) {
        if (!$cat_ids || (!is_array($cat_ids) && $cat_ids < 1)) return array();

        $conditions = array('parent_id IN (' . implode(',', (array)$cat_ids) . ')');
        $cats = $this->DB->select($this->module . '_categories', DB_ALL, array('cond' => $conditions, 'fields' => array('id')));

        if ($cats && is_array($cats) && count($cats)) {
            $new_ids = array();
            foreach ($cats as $cat) {
                if (isset($cat['id'])) $new_ids[] = intval($cat['id']);
            }
            $children_ids = $this->getCatChildren(array_unique($new_ids));
            $cat_ids = array_unique(array_merge((array)$cat_ids, $children_ids));
        }
        return (array)$cat_ids;
    }

    private function getEntriesCount($cat_id) {
        $cat_id = intval($cat_id);
        if ($cat_id < 1)  return 0;

        $cat_ids = $this->getCatChildren($cat_id);
        if ($cat_ids && is_array($cat_ids) && count($cat_ids)) {
            $entriesModel = OrmManager::getModelInstance($this->module);
            $total = $entriesModel->getTotal(array('cond' => array('category_id IN (' . implode(',', $cat_ids) . ')')));
            return ($total ? $total : 0);
        } else {
            return 0;
        }
    }

    /**
     * Build categories list ({{ categories }})
     *
     * @param mixed $cat_id
     * @return string.
     */
    protected function _getCatsTree($cat_id = false)
    {
        // Check cache
        $this->Cache = new Cache;
        if ($this->cached && $this->Cache->check('category_' . $this->cacheKey)) {
            $this->categories = $this->Cache->read('category_' . $this->cacheKey);
            return;
        }


        // get mat id
        $id = (!empty($cat_id)) ? intval($cat_id) : false;
        if ($id < 1) $id = false;


        // Get current action
        if (empty($this->params[1])) $action = 'index';
        else $action = trim($this->params[1]);
        $output = '';


        // type o tree
        if (!empty($id)) {
            switch ($action) {
                case 'category':
                case 'view':
                    $conditions = array('parent_id' => intval($id));
                    $cats = $this->DB->select($this->module . '_categories', DB_ALL,
                    array('cond' => $conditions));
                    break;
                default:
                    break;
            }
        }
        if (empty($cats)) {
            $cats = $this->DB->select($this->module . '_categories', DB_ALL, array(
                'cond' => array(
                    '`parent_id` = 0 OR `parent_id` IS NULL ',
                ),
            ));
        }


        // Build list
        if (count($cats) > 0) {
            $calc_count = Config::read('calc_count', $this->module);
            foreach ($cats as $cat) {
                $output .= '<li>' . get_link(h($cat['title']), '/' . $this->module . '/category/' . $cat['id']) . ($calc_count ? ' [' . $this->getEntriesCount($cat['id']) . ']' : '') . '</li>';
            }
        }


        $this->categories = '<ul class="ds_categories">' . $output . '</ul>';

        if ($this->cached)
            $this->Cache->write($this->categories, 'category_' . $this->cacheKey
            , array('module_' . $this->module, 'category_block'));
    }


    protected function _buildBreadCrumbs($cat_id = false)
    {
        $tree = array();
        $output = '<ul>';

        // Check cache
        if ($this->cached && $this->Cache->check('category_tree_' . $this->cacheKey)) {
            $tree = $this->Cache->read('category_tree_' . $this->cacheKey);
            $tree = unserialize($tree);
            return $tree;
        } else {
            $tree = $this->DB->select($this->module . '_categories', DB_ALL);
        }


        if (!empty($tree) && count($tree) > 0) {
            if ($this->cached)
                $this->Cache->write($this->categories, 'category_tree_' . $this->cacheKey
                , array('module_' . $this->module, 'category_block'));

            $output = $this->_buildBreadCrumbsNode($tree, $cat_id);
            return $output;
        }
        return '';
    }


    /**
     * Build bread crumbs
     * Use separator for separate links
     *
     * @param array $tree
     * @param mixed $cat_id
     * @param mixed $parent_id
     * @return string
     */
    protected function _buildBreadCrumbsNode($tree, $cat_id = false, $parent_id = false)
    {
        $output = '';

        if (empty($cat_id)) {
            $output = h($this->module_title);


        } else {
            if (is_string($cat_id)) {
                $_cat_id = explode(',', $cat_id);
                foreach ($tree as $key => $node) {
                    // используется для вывода, когда требуется вывести сразу несколько категорий
                    if (((!$parent_id and $parent_id === null) or count($_cat_id) > 1) and $node['id'] == $_cat_id[0]) {
                        $output = h($node['title']);

                        if (count($_cat_id) > 1) {
                            unset($_cat_id[0]);
                            $cat_id = implode(',', $_cat_id);
                            $output = $output.', '.$this->_buildBreadCrumbsNode($tree, $cat_id, null);
                        }

                        break;
                    // выводит конечную категорию
                    } else if ($node['id'] == $cat_id && !$parent_id) {
                        $output = h($node['title']);
                        if (!empty($node['parent_id']) && $node['parent_id'] != 0) {
                            $output = $this->_buildBreadCrumbsNode($tree, $cat_id, $node['parent_id']) . $output;
                        }
                        break;
                    // выводит родительские категории
                    } else if ($parent_id && $parent_id == $node['id']) {
                        $output = get_link(h($node['title']), '/' . $this->module . '/category/' . $node['id']) . __('Separator');
                        if (!empty($node['parent_id']) && $node['parent_id'] != 0) {
                            $output = $this->_buildBreadCrumbsNode($tree, $cat_id, $node['parent_id']) . $output;
                        }
                        break;
                    }
                }
            }

            if (!$parent_id and $parent_id !== null)
                $output = get_link(h($this->module_title), '/' . $this->module . '/') . __('Separator') . $output;
        }

        if (!$parent_id and $parent_id !== null) $output = get_link(__('Home'), '/') . __('Separator') . $output;
        return $output;
    }


    /**
     * Build categories list for select input
     *
     * @param array $cats
     * @param mixed $curr_category
     * @param mixed $id
     * @param string $sep
     * @return string
     */
    protected function _buildSelector($cats, $curr_category = false, $id = false, $sep = '- ')
    {
        $_curr_category = false;
        if ($curr_category !== false)
            $_curr_category = explode(',', $curr_category);
        $out = '';
        foreach ($cats as $key => $cat) {
            $parent_id = $cat->getParent_id();
            if (($id === false && empty($parent_id)) || (!empty($id) && $parent_id == $id)) {
                $out .= '<option value="' . $cat->getId() . '" '
                . (($_curr_category !== false && array_search($cat->getId(), $_curr_category) !== False) ? "selected=\"selected\"" : "")
                . '>' . $sep . h($cat->getTitle()) . '</option>';

                unset($cats[$key]);

                $out .= $this->_buildSelector($cats, $curr_category, $cat->getId(), $sep . '- ');
            }
        }

        return $out;
    }
    /*
     * Отфильтровывает недоступные пользователю категории из списка категорий $cats
    */
    protected function check_categories($cats, $id = false, $fieldname = 'no_access') {
        $out = array();
        $method = 'get'.ucfirst($fieldname);
        foreach ($cats as $key => $cat) {
            if (!ACL::checkAccessInList($cat->$method(),$id))
                $out[] = $cat;
        }
        return $out;
    }
    /*
     *  Вспомогательная функция - после выполнения пользователем каких-либо действий
     *  выдает информационное сообщение и/или делает редирект на нужную страницу с задержкой
     *
     * $message - сообщение для информирования пользователя
     * $url - ссылка для редиректа или кнопки "продолжить"
     * $type - тип окошка:
     *  'ok' - делать редирект по url(успешное завершение запроса)
     *  'grand' - не делать редиректа, но выводить ссылку.(успешное завершение запроса)
     *  'error' - не делать редиректа, но выводить ссылку и ошибки
     *  'alert' или любой другой - делать редирект.(считается успешным завершением запроса)
     *
    */
    function showMessage($message, $url = false, $type = 'error', $notRoot=false)
    {
        // Запрещаем индексацию информационных страниц.
        header('X-Robots-Tag: noindex,nofollow');

        // Преобразуем URL
        $url = ($url == false) ? $this->getModuleURL() : $url;
        $url = (empty($url)) ? '/' : $url;
        $url = (used_https() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . get_url($url, $notRoot);

        // Если нужен сокращенный вид для ajax окошек
        if (isset($_GET['ajax']) && $_GET['ajax'] == true) {
            header('ResponseAjax: ' . ($type == 'alert' ? 'ok' : $type));
            $output = $this->render('infomessage.html', array(
                'data' => array(
                    'message' => $message,
                    'url' => $url,
                    'status' => $type
                )
            ));

        // Если за информацией обратилось не ajax окошко, то возвращаем полноценную информационную страницу
        } else {
            $output = $this->render('infomessagegrand.html', array(
                'data' => array(
                    'message' => $message,
                    'url' => $url,
                    'status' => $type
                )
            ));
        }

        // Решаем делать ли редирект
        switch($type) {
            case 'grand': break;
            case 'error': break;
            case 'alert': break;
            default: // 'ok' или любое другое
                header('Refresh: ' . Config::read('redirect_delay') . '; url='.$url);
                break;
        }
        echo $output;
        die();
    }


    // Функция возвращает путь к модулю
    protected function getModuleURL($page = null)
    {
        $url = '/' . $this->module . '/' . (!empty($page) ? $page : '');
        $url = DrsUrl::parseRoutes($url);
        return $url;
    }


    // Функция возвращает путь к файлам модуля
    protected function getFilesPath($file = null, $module = null)
    {
        if (!isset($module)) $module = $this->module;
        $path = '/data/files/' . $module . '/' . (!empty($file) ? $file : '');
        return $path;
    }


    /* Функция возвращает путь к изображениям модуля или папке с ними
     *
     * @param boolean $type - True (full) or False (small)
     * @param mixed $file
     * @param mixed $module
     * @return string.
     */
    protected function getImagesPath($file = null, $module = null, $size_x = null, $size_y = null)
    {
        if (!isset($module)) $module = $this->module;
        $path = '/data/images/' . $module . '/' . (($size_x !== null and $size_y !== null) ? $size_x . 'x' . $size_y . '/' : '') . (!empty($file) ? $file : '');
        return $path;
    }


    // Функция возвращает путь к временным файлам модуля
    protected function getTmpPath($file = null)
    {
        $path = '/sys/tmp/' . $this->module . '/' . (!empty($file) ? $file : '');
        return $path;
    }


    // Функция возвращает максимально допустимый размер файла
    protected function getMaxSize($param = 'max_file_size')
    {
        $max_size = Config::read($param, $this->module);
        if (empty($max_size) || !is_numeric($max_size)) $max_size = Config::read('max_file_size');
        if (empty($max_size) || !is_numeric($max_size)) $max_size = 1048576;
        return $max_size;
    }


    // Функция обрабатывает метку изображения
    protected function insertImageAttach($message, $filename, $number, $module = null)
    {
        if (!isset($module)) $module = $this->module;

        if (Config::read('use_local_preview', $module)) {
            $preview = Config::read('use_preview', $module);
            $size_x = Config::read('img_size_x', $module);
            $size_y = Config::read('img_size_y', $module);
        } else {
            $preview = Config::read('use_preview');
            $size_x = Config::read('img_size_x');
            $size_y = Config::read('img_size_y');
        }

        $image_link = get_url($this->getImagesPath($filename, $module));
        $preview_link = $image_link;
        $sizes = ' style="max-width:' . ($size_x > 150 ? $size_x : 150) . 'px; max-height:' . ($size_y > 150 ? $size_y : 150) . 'px;"';

        if ($preview) {
            if (file_exists(ROOT.$this->getImagesPath($filename, $module, $size_x, $size_y))) {
                $preview_link = get_url($this->getImagesPath($filename, $module, $size_x, $size_y));
            } else {
                // Узнаем, а нужно ли превью для изображения
                list($width, $height, $type, $attr) = @getimagesize(R . $image_link);
                if ((empty($width) and empty($height)) or ($width > $size_x or $height > $size_y)) {
                    $preview_link = get_url($this->getImagesPath($filename, $module, $size_x, $size_y));
                } else {
                    $preview = false;
                    $sizes = '';
                }
            }
        }
        $str =
            ($preview ? '<a class="gallery" rel="example_group" href="' . $image_link . '">' : '') .
            '<img %s alt=""' . $sizes . ' src="' . $preview_link . '" />' .
            ($preview ? '</a>' : '');
        $from = array(
            '{IMAGE' . $number . '}',
            '{LIMAGE' . $number . '}',
            '{RIMAGE' . $number . '}',
        );
        $to = array(
            sprintf($str, 'class="textIMG"'),
            sprintf($str, 'align="left" class="LtextIMG"'),
            sprintf($str, 'align="right" class="RtextIMG"'),
        );
        return str_replace($from, $to, $message);
    }


    // Функция обрабатывает метку изображения в шаблоне
    protected function markerImageAttach($filename, $number, $module = null)
    {
        if (!isset($module)) $module = $this->module;
        $image_link = get_url($this->getImagesPath($filename, $module));
        return $image_link;
    }


    // Функция обрабатывает метку превью на изображение в шаблоне
    protected function markerSmallImageAttach($filename, $number, $module = null)
    {
        if (!isset($module)) $module = $this->module;
        if (isset($module) and Config::read('use_local_preview', $module)) {
            $preview = Config::read('use_preview', $module);
            $size_x = Config::read('img_size_x', $module);
            $size_y = Config::read('img_size_y', $module);
        } else {
            $preview = Config::read('use_preview');
            $size_x = Config::read('img_size_x');
            $size_y = Config::read('img_size_y');
        }
        $image_link = get_url($this->getImagesPath($filename, $module));
        $preview_link = $image_link;

        if ($preview) {
            if (file_exists(R.'/'.$this->getImagesPath($filename, $module, $size_x, $size_y))) {
                $preview_link = get_url($this->getImagesPath($filename, $module, $size_x, $size_y));
            } else {
                // Узнаем, а нужно ли превью для изображения
                list($width, $height, $type, $attr) = @getimagesize(R . $image_link);
                if ((empty($width) and empty($height)) or ($width > $size_x or $height > $size_y)) {
                    $preview_link = get_url($this->getImagesPath($filename, $module, $size_x, $size_y));
                }
            }
        }

        return $preview_link;
    }


    /**
     * Если материал был просмотрен, то возврат true.
     * Если не был просмотрен, то запись в сессию и возврат false
     *
     * @param int $id
     * @param string $module
     * @return boolean
     */
    protected function material_are_viewed($id = null, $module = null)
    {
        $id = (int)$id;
        if ( $id < 1 ) return false;
        if ( !isset($module) ) $module = $this->module;

        if ( !isset($_SESSION[$module]) or
             !isset($_SESSION[$module]["materials_are_viewed"]) or
             !array_key_exists($id, $_SESSION[$module]["materials_are_viewed"]) )
        {
            $_SESSION[$module]["materials_are_viewed"][$id] = null;
            return false;
        }

        return true;
    }

}