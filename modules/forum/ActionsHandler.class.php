<?php
/**
* @project    DarsiPro CMS
* @package    Forum Module
* @url        https://darsi.pro
*/

namespace ForumModule;

Class ActionsHandler extends \Module {

    /**
     * @module_title  title of module
     */
    public $module_title = 'Форум';

    /**
     * @template  layout for module
     */
    public $template = 'forum';

    /**
     * @module module indentifier
     */
    public $module = 'forum';

    function __construct($params) {
        parent::__construct($params);

        $this->setModel();
        
        
    }

    /**
     * @return main forum page content
     */
    public function index($n = null) {
        if (isset($n)) {
            http_response_code(404);
            include_once R.'sys/inc/error.php';
            die();
        }
        //turn access
        \ACL::turnUser(array($this->module, 'view_forums_list'),true);


        // navigation block
        $markers = array();
        $markers['navigation'] = get_link(__('Home'), '/') . __('Separator') . $this->page_title;
        $markers['pagination'] = '';
        $markers['add_link'] = '';
        $markers['meta'] = '';
        $this->_globalize($markers);


        if ($this->cached && $this->Cache->check($this->cacheKey)) {
            $html = $this->Cache->read($this->cacheKey) . $this->_get_stat();
            return $this->_view($html);
        }


        //get forums categories records
        $catsModel = \OrmManager::getModelInstance('ForumCat');
        $cats = $catsModel->getCollection(array(), array('order' => 'previev_id'));
        if (empty($cats)) {
            $html = __('No categories') . "\n" . $this->_get_stat();
            return $this->_view($html);
        }
        //pr($cats); die();





        $this->Model->bindModel('last_theme');
        $this->Model->bindModel('subforums');
        $_forums = $this->Model->getCollection(array("`parent_forum_id` IS NULL OR `parent_forum_id` = '0'"), array(
            'order' => 'pos',
        ));
        $_forums = $this->Model->addLastAuthors($_forums);



        //pr($_forums); die();
        //sort forums and subforums
        //after this we will be have $categories array with all cats, forum and subforums
        $forums = array();
        $categories = array();
        if (is_array($_forums) and count($_forums) > 0) {
            foreach ($_forums as $forum) {
                $forums[$forum->getIn_cat()][] = $forum;
            }
        }


        foreach ($cats as $category) {
            $categories[$category->getId()] = $category;
            $categories[$category->getId()]->setForums(array());
            if (array_key_exists($category->getId(), $forums)) {
                $categories[$category->getId()]->setForums($forums[$category->getId()]);
                unset($forums[$category->getId()]); //clean memory
            } else {
                unset($categories[$category->getId()]); //we needen't empty categories
            }
        }



        foreach ($categories as $cat) {
            $forums = $cat->getForums();
            if ($forums && !empty($forums)) {
                foreach ($forums as $forum) {
                    if ($forum) {
                        if (!$this->forumACL('view_forums',$forum->getId())) continue;
                        $forum = $this->_parseForumTable($forum);
                    }
                }
            }
        }


        //write to cache ( only if records detected )
        if ($this->cached)
            $this->Cache->write($html, $this->cacheKey, $this->cacheTags);



        $source = $this->render('catlist.html', array('forum_cats' => $categories));
        $source .= $this->_get_stat();
        return $this->_view($source);
    }

    /**
     * @param array $forum
     * @retrun string HTML forum table wiht replaced markers
     */
    private function _parseForumTable($forum) {
        if ($forum->getParent_forum_id()) {
            $this->Model->bindModel('last_theme');
            $this->Model->bindModel('subforums');
            $forum = $this->Model->getById($forum->getId());
        }
        // Summ posts and themes
        $subforums = $forum->getSubforums();
        if (count($subforums) > 0 && $this->forumACL('view_forums',$forum->getId())) {
            foreach ($subforums as $index=>$subforum) {
                if (!$this->forumACL('view_forums',$subforum->getId())) continue;
                $subforum = $this->_parseForumTable($subforum);
                $forum->setPosts($forum->getPosts() + $subforum->getPosts());
                $forum->setThemes($forum->getThemes() + $subforum->getThemes());
                $subforums[$index] = $subforum;
            }
            $forum->setSubforums($subforums);
        }

        $forum->setView_themes_user($this->forumACL('view_themes',$forum->getId()));
        $forum->setForum_url(get_url($this->getModuleURL('view_forum/' . $forum->getId())));



        //выводим название темы в которой было добавлено последнее сообщение и дату его добавления
        if ($forum->getLast_theme_id() < 1) {
            $last_post = __('No posts');
        } else {
            if (!$forum->getLast_theme() || !$forum->getLast_author()) {
                $themesClass = \OrmManager::getModelInstance('ForumThemes');
                $themesClass->bindModel('last_author');
                $theme = $themesClass->getById($forum->getLast_theme_id());
                if ($theme) {
                    $forum->setLast_theme($theme);
                    $forum->setLast_author($theme->getLast_author());
                }
            }
            if (!$forum->getLast_theme() || !$forum->getLast_author()) {
                $last_post = __('No posts');
            } else {

                // Получаем заголовок последней обновленной темы
                $last_post_title = substr_ext($forum->getLast_theme()->getTitle(),30);

                // Получаем автора последнего сообщения в этой теме
                $last_theme_author = __('Guest');
                if ($forum->getLast_author()) {
                    $last_theme_author = get_link(h($forum->getLast_author()->getName()), getProfileUrl($forum->getLast_author()->getId(), true), array('title' => __('View profile')));
                }

                // Получаем порядковый номер последнего поста и рассчитываем количество страниц в теме
                $last_theme_post = $forum->getLast_theme()->getPosts()+1;
                $near_pages = \Config::read('posts_per_page', $this->module);
                $page = 0;
                if ($last_theme_post > $near_pages)
                    $page = ceil($last_theme_post / $near_pages);

                $last_post = DrsDate($forum->getLast_theme()->getLast_post()) . '<br>';
                $last_post .= sprintf(__('Last update for forum'),
                            get_link(
                                        h($last_post_title),
                                        $this->getModuleURL('view_theme/' . $forum->getLast_theme_id() . ($page ? '/?page=' . $page : '') . ($last_theme_post ? '#post' . $last_theme_post : '')),
                                        array('title' => __('To last post'))
                                    )
                                    ,$last_theme_author);
            }
        }
        $forum->setLast_post($last_post);



        // Ссылка "Править форум"
        $admin_bar = '';
        if ($this->forumACL('view_forums',$forum->getId())) {
            if ($this->forumACL('replace_forums',$forum->getId())) {
                $admin_bar .= get_link('', $this->getModuleURL('forum_up/' . $forum->getId()), array('id' => 'fum'.$forum->getId(), 'class' => 'drs-up', 'onclick' => "sendu('fum".$forum->getId()."'); return false"))
                            . get_link('', $this->getModuleURL('forum_down/' . $forum->getId()), array('id' => 'fdom'.$forum->getId(), 'class' => 'drs-down', 'onclick' => "sendu('fdom".$forum->getId()."'); return false"));
            }
            if ($this->forumACL('edit_forums',$forum->getId())) {
                $admin_bar .= get_link('', $this->getModuleURL('edit_forum_form/' . $forum->getId()), array('class' => 'drs-edit')) ;
            }
            if ($this->forumACL('delete_forums',$forum->getId())) {
                $admin_bar .= get_link('', $this->getModuleURL('delete_forum/' . $forum->getId()), array('id' => 'fdm'.$forum->getId(), 'class' => 'drs-delete', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fdm".$forum->getId()."')}; return false"));
            }
        }
        $forum->setAdmin_bar($admin_bar);


        /* forum icon */
        $forum_icon = get_url('/template/' . getTemplate() . '/img/guest.png');
        if (file_exists(ROOT . '/data/img/forum_icon_' . $forum->getId() . '.jpg')) {
            $forum_icon = get_url('/data/img/forum_icon_' . $forum->getId() . '.jpg');
        }
        $forum->setIcon_url($forum_icon);
        return $forum;
    }

    /**
     * View threads list (forum)
     */
    public function view_forum($id_forum = null) {
        if (!is_numeric($id_forum))
            return $this->showMessage(__('Value must be numeric'));

        $id_forum = (int)$id_forum;
        if ($id_forum < 1)
            return $this->showMessage(__('Can not find forum'));

        //turn access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        $this->forumACL('view_forums',$id_forum,true);


        //who is here
        $who = array();
        $dir = ROOT . '/sys/logs/forum/';
        $forumFile = $dir . $id_forum . '.dat';
        if (!file_exists($dir))
            mkdir($dir, 0777, true);
        if (file_exists($forumFile)) {
            $who = unserialize(file_get_contents($forumFile));
        }


        if (isset($_SESSION['user']['name'])) {
            if (!isset($who[$_SESSION['user']['id']])) {
                $who[$_SESSION['user']['id']]['profile_link'] = get_link(h($_SESSION['user']['name']), getProfileUrl($_SESSION['user']['id']), true);
                $who[$_SESSION['user']['id']]['expire'] = time() + 1000;
            }
        }


        $who_is_here = '';
        foreach ($who as $key => $val) {
            if ($val['expire'] < time()) {
                unset($who[$key]);
                continue;
            }
            $who_is_here .= $val['profile_link'] . ', ';
        }
        file_put_contents($forumFile, serialize($who));
        //$context = array('who_is_here', substr($who_is_here, 0, -2));
        //are we have cache?
        if ($this->cached && $this->Cache->check($this->cacheKey)) {
            $html = $this->Cache->read($this->cacheKey);
        } else {


            // Получаем информацию о форуме
            $this->Model->bindModel('subforums');
            $this->Model->bindModel('category');
            $this->Model->bindModel('last_theme');
            $forum = $this->Model->getById($id_forum);
            if (!$forum) {
                return $this->showMessage(__('Can not find forum'));
            }


            // Check access to this forum. May be locked by pass or posts count
            $this->__checkForumAccess($forum);
            $this->page_title = h($forum->getTitle());
            $this->page_meta_description = h($forum->getDescription());


            // count themes for page nav
            $themesClass = \OrmManager::getModelInstance('ForumThemes');
            $themesClass->bindModel('author');
            $themesClass->bindModel('last_author');
            $total = $themesClass->getTotal(array('cond' => array('id_forum' => $id_forum)));


            $perPage = intval(\Config::read('themes_per_page', $this->module));
            if ($perPage < 1)
                $perPage = 10;
            list($pages, $page) = pagination($total, $perPage, $this->getModuleURL('view_forum/' . $id_forum));
            //$this->page_title .= ' (' . $page . ')';


            $order = getOrderParam(__CLASS__);
            $themes = $themesClass->getCollection(
                array(
                    'id_forum' => $id_forum
                ),
                array(
                    'page' => $page,
                    'limit' => $perPage,
                    'order' => 'important DESC,' . (empty($order) ? ' last_post DESC,' : $order . ',') . ' id DESC',
                )
            );


            // Nav block
            $markers = array();
            $markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
                    . get_link(__('forum'), $this->getModuleURL()) . __('Separator')
                    . h($forum->getTitle());

            $cntPages = ceil($total / $perPage);
            $recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
            $firstOnPage = ($page - 1) * $perPage + 1;
            $lastOnPage = $firstOnPage + $recOnPage - 1;

            $markers['pagination'] = $pages;
            $markers['add_link'] = ($this->forumACL('add_themes',$id_forum)) ? get_link(__('New topic'), '/forum/add_theme_form/' . $id_forum) : '';
            $markers['meta'] = __('Count all topics') . ' ' . $total . '. ' . ($total > 1 ? __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage : '');
            $this->_globalize($markers);


            $subforums = $forum->getSubforums();
            if (count($subforums) > 0 && $this->forumACL('view_forums',$forum->getId())) {
                foreach ($subforums as $index=>$subforum) {
                    if (!$this->forumACL('view_forums',$subforum->getId())) continue;
                    $subforums[$index] = $this->_parseForumTable($subforum);
                }
                $forum->setSubforums($subforums);

                $forum->setCat_name(__('Subforums title'));
            }




            $cnt_themes_here = is_array($themes) ? count($themes) : 0;
            if ($cnt_themes_here > 0 && is_array($themes)) {
                foreach ($themes as $theme) {

                    $theme = $this->__parseThemeTable($theme);

                    //set cache tags
                    $this->setCacheTag(array(
                        'theme_id_' . $theme->getId(),
                    ));
                }
                $this->setCacheTag(array(
                    'forum_id_' . $id_forum,
                ));
            }


            $forum->setCount_themes_here($cnt_themes_here);
            $forum->setWho_is_here(substr($who_is_here, 0, -2));
            //$forum->setCount_themes(count($themes));
            //write cache
            if ($this->cached)
                $this->Cache->write($html, $this->cacheKey, $this->cacheTags);
        }



        $source = $this->render('themes_list.html', array(
            'themes' => $themes,
            'forum' => $forum,
        ));
        return $this->_view($source);
    }

    /**
     * Check access to this forum.
     * May be locked by pass or posts count
     *
     * @param array $forum
     */
    private function __checkForumAccess($forum) {
        // Если ограничения не настроены - открываем форум
        if (!$forum->getLock_passwd() && !$forum->getLock_posts())
            return true;
        // Если есть ограничение на количество постов
        if ($forum->getLock_posts()) {
            if ($forum->getPosts() <= $forum->getLock_posts()) {
                return true;
            }
            echo $this->showMessage(sprintf(__('locked forum by posts'), $forum->getLock_posts()));
            die();
        }
        // Если есть пароль на вход
        if ($forum->getLock_passwd()) {
            // Если пароль на вход уже был введен
            if (isset($_SESSION['access_forum_' . $forum->getId()]) &&
                $_SESSION['access_forum_' . $forum->getId()] == $forum->getLock_passwd()) {
                return true;

            // Если форма с вводом пароля заполнена и нам отправлен введенный пароль
            } else if (isset($_POST['forum_lock_pass'])) {
                // Введенный пароль и пароль от форума совпадают - открываем форум, иначе выводим сообщение.
                if ($_POST['forum_lock_pass'] == $forum->getLock_passwd()) {
                    $_SESSION['access_forum_' . $forum->getId()] = $_POST['forum_lock_pass'];
                    return true;
                }
                echo $this->showMessage(__('Wrong pass'), $this->getModuleURL('view_forum/' . $forum->getId()));
                die();
            // В ином случае открывем форму ввода пароля
            } else {

                $this->page_title = __('Log in to the forum with a password');

                header("HTTP/1.0 401 Unauthorized");

                /* For client scripts
                if (!$_GET['ajaxLoad'])
                    header("HTTP/1.0 401 Unauthorized");
                */

                $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                                   . get_link(__('forum'), $this->getModuleURL()) . __('Separator')
                                   . get_link($forum->getTitle(), $this->getModuleURL('view_forum/' . $forum->getId())) . __('Separator')
                                   . __('Log in to the forum with a password');
                $this->_globalize($nav);

                echo $this->_view($this->render('forum_passwd_form.html', array()));
                die();
            }
        }
        return true;
    }

    /**
     * @param array $theme
     * @retrun string HTML theme table with replaced markers
     */
    private function __parseThemeTable($theme) {
        //ICONS
        $themeicon = $this->__getThemeIcon($theme);

        $theme->setTheme_url(get_url($this->getModuleURL('view_theme/' . $theme->getId())));


        //ADMINBAR
        $adminbar = '';
        if ($this->forumACL('view_themes',$theme->getId_forum())) {
            if ($this->forumACL('edit_themes',$theme->getId_forum())
                    || (!empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id']
                    && $this->forumACL('edit_mine_themes',$theme->getId_forum()))) {
                $adminbar .= get_link('', $this->getModuleURL('edit_theme_form/' . $theme->getId()), array('class' => 'drs-edit'));
            }
            if ($this->forumACL('close_themes',$theme->getId_forum())) {
                if ($theme->getLocked() == 0) { // заблокировать тему
                    $adminbar .= get_link('', $this->getModuleURL('lock_theme/' . $theme->getId()), array('id' => 'flm'.$theme->getId(), 'class' => 'drs-close', 'onClick' => "sendu('flm".$theme->getId()."'); return false"));
                } else { // разблокировать тему
                    $adminbar .= get_link('', $this->getModuleURL('unlock_theme/' . $theme->getId()), array('id' => 'fulm'.$theme->getId(), 'class' => 'drs-open', 'onClick' => "sendu('fulm".$theme->getId()."'); return false"));
                }
            }
            if ($this->forumACL('important_themes',$theme->getId_forum())) {
                if ($theme->getImportant() == 1) {
                    $adminbar .= get_link('', $this->getModuleURL('unimportant/' . $theme->getId()), array('id' => 'fuim'.$theme->getId(), 'class' => 'drs-unfix', 'onClick' => "sendu('fuim".$theme->getId()."'); return false"));
                } else {
                    $adminbar .= get_link('', $this->getModuleURL('important/' . $theme->getId()), array('id' => 'fim'.$theme->getId(), 'class' => 'drs-fix', 'onClick' => "sendu('fim".$theme->getId()."'); return false"));
                }
            }
            if ($this->forumACL('delete_themes', $theme->getId_forum())
                    || (!empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id']
                    && $this->forumACL('delete_mine_themes',$theme->getId_forum()))) {
                $adminbar .= get_link('', $this->getModuleURL('delete_theme/' . $theme->getId()), array('id' => 'fd'.$theme->getId(), 'class' => 'drs-delete', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fd".$theme->getId()."')}; return false"));
            }
        }
        $theme->setAdminbar($adminbar);


        //USER PROFILE
        $author_url = __('Guest');
        if ($theme->getId_author() && $theme->getAuthor()) {
            $author_url = get_link(h($theme->getAuthor()->getName()), getProfileUrl($theme->getId_author(), true));
        }
        $theme->setAuthorUrl($author_url);


        // Last post author
        $last_user = __('Guest');
        if ($theme->getId_last_author() && $theme->getLast_author()) {
            $last_user = get_link(h($theme->getLast_author()->getName()), getProfileUrl($theme->getId_last_author(), true));
        }


        //NEAR PAGES
        $near_pages = '';
        $cnt_posts = $theme->getPosts()+1;
        $per_page = \Config::read('posts_per_page', $this->module);
        $cnt_near_pages = 0;
        if ($cnt_posts > $per_page) {
            $cnt_near_pages = ceil($cnt_posts / $per_page);
            if ($cnt_near_pages > 1) {
                $near_pages .= '&nbsp;(';
                for ($n = 1; $n < ($cnt_near_pages + 1); $n++) {
                    if ($cnt_near_pages > 5 && $n > 3) {
                        $near_pages .= '&nbsp;...&nbsp;' . get_link(($cnt_near_pages - 1), $this->getModuleURL('view_theme/' . $theme->getId() . '?page='
                                                . ($cnt_near_pages - 1))) . '&nbsp;' . get_link($cnt_near_pages, $this->getModuleURL('view_theme/'
                                                . $theme->getId() . '?page=' . $cnt_near_pages));
                        break;
                    } else {
                        if ($n > 5)
                            break;
                        $near_pages .= ($n > 1 ? '&nbsp;' : '') . get_link($n, $this->getModuleURL('view_theme/' . $theme->getId() . '?page=' . $n));
                    }
                }
                $near_pages .= ')';
            }
        }

        // Ссылка на последний пост
        $last_page = get_link(__('To last post'), $this->getModuleURL('view_theme/' . $theme->getId() . ($cnt_near_pages ? '/?page=' . $cnt_near_pages : '') . ($cnt_posts ? '#post' . $cnt_posts : '')));


        $theme->setLast_page($last_page);
        $theme->setLast_user($last_user);
        $theme->setThemeicon($themeicon);
        $theme->setDrs_css_class(($theme->getImportant()) ? 'drs-theme-important' : '');
        $theme->setNear_pages($near_pages);
        $theme->setImportantly(($theme->getImportant() == 1) ? __('Important').':' : '');


        return $theme;
    }

    /**
     * Return theme icon
     *
     * @param array $theme
     * @return string img HTML tag with URL to needed icon
     */
    private function __getThemeIcon($theme) {
        $hot_theme_limit = 20;

        if (isset($_SESSION['user']['name'])) { // это для зарегистрированного пользователя
            // Если есть новые сообщения (посты) - только для зарегистрированных пользователей
            if (isset($_SESSION['newThemes']) and in_array($theme->getId(), $_SESSION['newThemes'])) {
                if ($theme->getLocked() == 0) // тема открыта
                    if ($theme->getPosts() > $hot_theme_limit)
                        $themeicon = get_img('/template/' . getTemplate() . '/img/folder_hot_new.gif', array(
                            'class' => 'themeicon',
                            'alt' => __('New posts'),
                            'title' => __('New posts')
                        ));
                    else
                        $themeicon = get_img('/template/' . getTemplate() . '/img/folder_new.gif', array(
                            'class' => 'themeicon',
                            'alt' => __('New posts'),
                            'title' => __('New posts')
                        ));
                else // тема закрыта
                    $themeicon = get_img('/template/' . getTemplate() . '/img/folder_lock_new.gif', array(
                        'class' => 'themeicon',
                        'alt' => __('New posts'),
                        'title' => __('New posts')
                    ));
            } else {
                if ($theme->getLocked() == 0) // тема открыта
                    if ($theme->getPosts() > $hot_theme_limit)
                        $themeicon = get_img('/template/' . getTemplate() . '/img/folder_hot.gif', array(
                            'class' => 'themeicon',
                            'alt' => __('No new posts'),
                            'title' => __('No new posts')
                        ));
                    else
                        $themeicon = get_img('/template/' . getTemplate() . '/img/folder.gif', array(
                            'class' => 'themeicon',
                            'alt' => __('No new posts'),
                            'title' => __('No new posts')
                        ));
                else // тема закрыта
                    $themeicon = get_img('/template/' . getTemplate() . '/img/folder_lock.gif', array(
                        'class' => 'themeicon',
                        'alt' => __('No new posts'),
                        'title' => __('No new posts')
                    ));
            }
        } else { // это для не зарегистрированного пользователя
            if ($theme->getLocked() == 0) // тема открыта
                if ($theme->getPosts() > $hot_theme_limit)
                    $themeicon = get_img('/template/' . getTemplate() . '/img/folder_hot.gif', array(
                        'class' => 'themeicon'
                    ));
                else
                    $themeicon = get_img('/template/' . getTemplate() . '/img/folder.gif', array(
                        'class' => 'themeicon'
                    ));
            else // тема закрыта
                $themeicon = get_img('/template/' . getTemplate() . '/img/folder_lock.gif', array(
                    'class' => 'themeicon'
                ));
        }

        return $themeicon;
    }

    /**
     * Return posts list
     */
    public function view_theme($id_theme = null) {
        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'));

        $themeModel = \OrmManager::getModelInstance('ForumThemes');
        $themeModel->bindModel('forum');
        $themeModel->bindModel('poll');
        $theme = $themeModel->getById($id_theme);
        
        if (!$theme || !$theme->getForum())
            return $this->showMessage(__('Topic not found'));


        //turn access
        $this->forumACL('view_forums',$theme->getId_forum(),true);
        $this->forumACL('view_themes',$theme->getId_forum(),true);


        // Check access to this forum. May be locked by pass or posts count
        $this->__checkForumAccess($theme->getForum());
        $id_forum = $theme->getId_forum();

        $this->__checkThemeAccess($theme);



        if ($this->cached && $this->Cache->check($this->cacheKey)) {
            $source = $this->Cache->read($this->cacheKey);
        } else {


            // Если запрошенной темы не существует - возвращаемся на форум
            if (empty($theme))
                return $this->showMessage(__('Topic not found'), $this->getModuleURL('view_forum/' . $id_forum));


            // Заголовок страницы (содержимое тега title)
            $this->page_title = h($theme->getTitle());
            if ($theme->getDescription()) {
                $this->page_meta_description = h($theme->getDescription());
            }


            $markers = array();
            $markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
                    . get_link(__('forum'), $this->getModuleURL()) . __('Separator')
                    . get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $id_forum)) . __('Separator')
                    . h($theme->getTitle());


            // Page nav
            $postsModel = \OrmManager::getModelInstance('ForumPosts');
            $total = $postsModel->getTotal(array('cond' => array('id_theme' => $id_theme)));

            if ($total === 0) {
                $this->__delete_theme($theme);
                if ($this->isLogging)
                    \Logination::write('delete theme (because error uccured)', 'theme id(' . $id_theme . ')');
                return $this->showMessage(__('Topic not found'), $this->getModuleURL('view_forum/' . $id_forum));
            }
            list($pages, $page) = pagination($total, \Config::read('posts_per_page', $this->module), $this->getModuleURL('view_theme/' . $id_theme));
            $markers['pagination'] = $pages;
            //$this->page_title .= ' (' . $page . ')';



            // SELECT posts
            $postsModel->bindModel('author');
            $postsModel->bindModel('editor');
            $postsModel->bindModel('attacheslist');
            $posts = $postsModel->getCollection(array(
                'id_theme' => $id_theme,
            ), array(
                'order' => 'time ASC, id ASC',
                'page' => $page,
                'limit' => \Config::read('posts_per_page', $this->module),
            ));



            // Ссылка "Ответить" (если тема закрыта - выводим сообщение "Тема закрыта")
            if ($theme->getLocked() == 0)
                $markers['add_link'] = ($this->forumACL('add_themes',$id_forum)) ? get_link(__('Answer'), '/forum/add_theme_form/' . $id_forum . '#sendForm') : '';
            else
                $markers['closed_theme'] = __('Theme is locked');

            $admin_bar = array();
            if ($this->forumACL('edit_themes',$id_forum)) {
                $admin_bar[] = array('url' => get_url($this->getModuleURL('move_posts_form/' . $id_theme)), 'title' => __('Move posts'));
                if ($this->forumACL('add_themes',$id_forum))
                    $admin_bar[] = array('url' => get_url($this->getModuleURL('split_theme_form/' . $id_theme)), 'title' => __('Split theme'));

                $admin_bar[] = array('url' => get_url($this->getModuleURL('edit_theme_form/' . $id_theme)), 'title' => __('Edit theme'));
                $admin_bar[] = array('url' => get_url($this->getModuleURL('unite_themes_form/' . $id_theme)), 'title' => __('Unite themes'));
            }
            if ($this->forumACL('close_themes', $id_forum)) {
                if ($theme->getLocked() == 0)
                    $admin_bar[] = array('url' => get_url($this->getModuleURL('lock_theme/' . $theme->getId())), 'title' => __('Lock theme'));
                else
                    $admin_bar[] = array('url' => get_url($this->getModuleURL('unlock_theme/' . $theme->getId())), 'title' => __('Unlock theme'));
            }
            if ($this->forumACL('important_themes',$id_forum)) {
                if ($theme->getImportant() == 1)
                    $admin_bar[] = array('url' => get_url($this->getModuleURL('unimportant/' . $theme->getId())), 'title' => __('Unimportant theme'));
                else
                    $admin_bar[] = array('url' => get_url($this->getModuleURL('important/' . $theme->getId())), 'title' => __('Important theme'));
            }
            /*
            // Необходимо добавить подтверждение удаления темы
            if ($this->forumACL('delete_themes', $theme->getId_forum())
              || (
                  !empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id']
                  && $this->forumACL('delete_mine_themes',$theme->getId_forum())
                 )
            ) {
              $admin_bar[] = array('url' => get_url($this->getModuleURL('delete_theme/' . $theme->getId())), 'title' => __('Delete theme'));
            }
            */
            // TODO
            if ($admin_bar && is_array($admin_bar) && count($admin_bar) > 0) {

                $markers['admin_bar'] = '<div id="theme_modbar"><div id="theme_modbar_buttom"></div><ul id="theme_modbar_list">';
                foreach ($admin_bar as $index => $command)
                    $markers['admin_bar'] .= '<li><a href="' . $command['url'] . '" onClick="sendu(this); return false">' . $command['title'] . '</a></li>';

                $markers['admin_bar'] .= '</ul></div>';
            } else
                $markers['admin_bar'] = '';
            ///TODO

            $markers['admin_bar_list'] = $admin_bar;

            if (!$this->forumACL('add_posts', $id_forum))
                $markers['add_link'] = '';
            $markers['meta'] = '';

            $this->_globalize($markers);


            $first_top = false;
            if ($page > 1 && $theme->getFirst_top() == '1') {
                $post = $postsModel->getFirst(array(
                    'id_theme' => $id_theme,
                    ), array(
                    'order' => 'time ASC, id ASC',
                    ));
                if ($post) {
                    array_unshift($posts, $post);
                    $first_top = true;
                }
            }
            $this->setCacheTag('theme_id_' . $id_theme);


            // Polls render
            $polls = $theme->getPoll();
            if (is_array($polls) && count($polls) && !empty($polls[0])) {
                $theme->setPoll($this->_renderPoll($polls[0]));
            } else
                $theme->setPoll('');


            $obj = $this;
            $theme->setThemeicon(function() use($obj,$theme) {return $obj->__getThemeIcon($theme);});

            $source = $this->render('posts_list.html', array(
                'posts' => $this->__parsePostsTable($posts, $page, $first_top, $theme),
                'theme' => $theme,
                'reply_form' => function() use($obj,$theme) {return $obj->add_post_form($theme);},
            ));


            //write into cache
            if ($this->cached)
                $this->Cache->write($source, $this->cacheKey, $this->cacheTags);
        }


        // Если страницу темы запросил зарегистрированный пользователь, значит он ее просмотрит
        if (isset($_SESSION['user']['name']) and isset($_SESSION['newThemes'])) {
            if (count($_SESSION['newThemes']) > 0) {
                if (in_array($id_theme, $_SESSION['newThemes'])) {
                    unset($_SESSION['newThemes'][$id_theme]);
                }
            } else {
                unset($_SESSION['newThemes']);
            }
        }

        // Добавляем просмотр
        if (!$this->material_are_viewed($id_theme)) {
            $theme->setViews($theme->getViews() + 1);
            $theme->save();
        }

        //clean cache
        $this->Cache->clean(CACHE_MATCHING_TAG, array('action_viev_forum', 'theme_id_' . $id_theme));
        return $this->_view($source);
    }

    /**
     * Parse posts table
     */
    private function __parsePostsTable($posts, $page, $first_top = false, $one_theme = null) {
        if ($posts) {
            $post_num = (($page - 1) * \Config::read('posts_per_page', $this->module));

            foreach ($posts as $post) {
                if (!$this->forumACL('view_forums',$post->getId_forum())) continue;
                if (!$this->forumACL('view_themes',$post->getId_forum())) continue;
                // Если автор сообщения (поста) - зарегистрированный пользователь
                $postAuthor = $post->getAuthor();
                $author_status = ($postAuthor && $postAuthor->getStatus()) ? $postAuthor->getStatus() : 0;
                if ($postAuthor) {
                    if (!property_exists($postAuthor, 'processComplete')) {
                        // Аватар
                        $postAuthor->setAvatar(getAvatar($post->getId_author()));

                        // Статус пользователя
                        $status = \ACL::get_group_info();
                        $user_status = $status[$author_status];
                        $postAuthor->setStatus_title($user_status['title']);
                        $postAuthor->setStatus_color($user_status['color']);


                        // Рейтинг пользователя (по количеству сообщений)
                        $rating = $postAuthor->getPosts();
                        $rank_star = getUserRating($rating);
                        $postAuthor->setRank($rank_star['rank']);
                        if ($postAuthor->getState())
                            $postAuthor->setRank($postAuthor->getState());
                        $postAuthor->setUser_rank(get_img('/template/' . getTemplate() . '/img/' . $rank_star['img']));


                        // Если пользователь заблокирован
                        if ($postAuthor->getBlocked()) {
                            $postAuthor->setStatus_on('<span class="statusBlock">' . __('Banned') . '</span>');
                            $postAuthor->setStatus_line('');
                        }


                        $signature = ($postAuthor->getSignature()) ? \PrintText::getSignature($postAuthor->getSignature(), $author_status) : '';
                        $postAuthor->setSignature($signature);


                        // If author is authorized user.
                        $email = '';
                        $privat_message = '';
                        $author_site = '';
                        $user_profile = '';
                        $icon_params = array('class' => 'user-details');


                        if ($post->getId_author()) {
                            $user_profile = '&nbsp;' . get_link(__('View profile'), getProfileUrl($post->getId_author(), true), $icon_params);


                            if (isset($_SESSION['user']['name'])) {
                                $privat_message = '&nbsp;' . get_link(__('PM'), '/users/send_msg_form/' . $post->getId_author(), $icon_params);
                            }


                            $author_site = ($postAuthor->getUrl()) ? '&nbsp;' . get_link(__('Author site'), h($postAuthor->getUrl()), array_merge($icon_params, array('target' => '_blank')), true) : '';
                        }
                        $postAuthor->setAuthor_site($author_site);
                        $postAuthor->setProfile_url($user_profile);
                        $postAuthor->setPm_url($privat_message);

                        $postAuthor->processComplete = true;
                    }


                    // Если автор сообщения - незарегистрированный пользователь
                } else {
                    $postAuthor = new \OrmEntity();
                    $postAuthor->setAvatar(getAvatar());
                    $postAuthor->setName(__('Guest'));
                }

                $message = \PrintText::print_page($post->getMessage(), $author_status);

                $attachment = null;
                $attaches = array();
                $locked_attaches = \Config::read('locked_attaches', $this->module);
                $post->setLocked_attaches($locked_attaches);
                if (!$locked_attaches) {
                    $attach_list = $post->getAttacheslist();
                    if (is_array($attach_list)) {
                        $collizion = true;
                        sort($attach_list);
                        foreach ($attach_list as $attach) {
                            $step = false;
                            if (($attach->getIs_image() and file_exists(ROOT . $this->getImagesPath($attach->getFilename()))) or 
                                (file_exists(ROOT . $this->getFilesPath($attach->getFilename())))) {

                                $name = substr($attach->getFilename(), strpos($attach->getFilename(), '_', 0)+1);

                                $attachment .= get_link($name . '(' . getSimpleFileSize($attach->getSize()) . ')',
                                $this->getModuleURL('download_file/' . $attach->getFilename())) . '<br />';

                                $attaches[] = array(
                                    'id' => $attach->getAttach_number(),
                                    'name' => $name,
                                    'date' => getDate(),
                                    'size' => getSimpleFileSize($attach->getSize()),
                                    'filename' => $attach->getFilename(),
                                    'is_img' => $attach->getIs_image(),
                                    'url' => $this->getModuleURL('download_file/' . $attach->getFilename())
                                );


                                //if attach is image and isset markers for this image
                                if ($attach->getIs_image() == '1') {
                                    $message = $this->insertImageAttach($message, $attach->getFilename(), $attach->getAttach_number());
                                }
                                $step = true;
                            }
                            $collizion = $collizion && $step;
                        }
                        /* may be collizion (paranoya mode) */
                        if (!$collizion)
                            $this->deleteCollizions($post);
                    } else
                        $this->deleteCollizions($post);
                }

                if ($attachment != null) {
                    $post->setAttachment($attachment);
                }
                $post->setAttaches_list(array_slice($attaches,0));

                $post->setMessage($message);

                $post->setAuthor($postAuthor);


                // Если сообщение редактировалось...
                if ($post->getId_editor() && $post->getEditor()) {
                    if ($post->getId_author() && $post->getId_author() == $post->getId_editor())
                        $editor = sprintf(__('Edit by author from time'),DrsDate($post->getEdittime()));
                    else {
                        $status_info = \ACL::get_group($post->getEditor()->getStatus());
                        $editor = sprintf(
                            __('Edited by group from time'),
                            $post->getEditor()->getName() . ' ('. $status_info['title'] . ')',
                            DrsDate($post->getEdittime())
                        );
                    }
                } else
                    $editor = '';
                $post->setEditor_info($editor);



                // порядковый номер сообщения в теме
                if ($first_top) {
                    $post->setPost_number(1);
                    $first_top = false;
                } else {
                    $post_num++;
                    $post->setPost_number($post_num);
                }
                $post_number_url = (used_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . get_url($this->getModuleURL('view_post/' . $post->getId()), true);
                $post->setPost_number_url($post_number_url);



                //edit and delete links
                $edit_link = '';
                $delete_link = '';
                $theme = $one_theme;
                if (strtotime($post->getEdittime()) > strtotime($post->getTime()))
                    $lasttime = strtotime($post->getEdittime());
                else
                    $lasttime = strtotime($post->getTime());
                $raw_time_mess = $lasttime - time() + \Config::read('raw_time_mess');
                if (!$theme && $post->getTheme())
                    $theme = $post->getTheme();
                if ($raw_time_mess <= 0 or ($theme->getLocked() != 0)) $raw_time_mess = false;

                if (!empty($_SESSION['user']['name']) && $theme && $theme->getId_forum()) {
                    if  (
                            (
                                $this->forumACL('edit_posts', $theme->getId_forum()) or
                                (
                                    !empty($_SESSION['user']['id']) &&
                                    $post->getId_author() == $_SESSION['user']['id'] &&
                                    $this->forumACL('edit_mine_posts', $theme->getId_forum()) &&
                                    (
                                        \Config::read('raw_time_mess') == 0 or
                                        $raw_time_mess
                                    ) &&
                                    $theme->getLocked() == 0
                                )
                            ) or
                            (
                                $post_num === 1 &&
                                (
                                    $this->forumACL('edit_themes', $theme->getId_forum()) or
                                    (
                                        !empty($_SESSION['user']['id']) &&
                                        $theme->getId_author() == $_SESSION['user']['id'] &&
                                        $this->forumACL('edit_mine_themes', $theme->getId_forum())
                                    )
                                )
                            )
                        )
                    {
                        $edit_link = get_link(__('Edit'), $this->getModuleURL('edit_post_form/' . $post->getId()));
                    }


                    if  (
                            $post_num !== 1 &&
                            (
                                $this->forumACL('delete_posts', $theme->getId_forum()) or
                                (
                                    !empty($_SESSION['user']['id']) &&
                                    $post->getId_author() == $_SESSION['user']['id'] &&
                                    $this->forumACL('delete_mine_posts', $theme->getId_forum()) &&
                                    (
                                        \Config::read('raw_time_mess') == 0 or
                                        $raw_time_mess
                                    ) &&
                                    $theme->getLocked() == 0
                                )
                            )
                        )
                    {
                        $delete_link = get_link(__('Delete'), $this->getModuleURL('delete_post/' . $post->getId()), array('onClick' => "return confirm('" . __('Are you sure?') . "')"));
                    }



                }

                /*<TODO>*/
                $on_top = get_link(__('To top'), '#top', array(), true);
                $post->setOn_top_link($on_top);
                /*</TODO>*/

                $post->setEdit_link($edit_link);
                $post->setDelete_link($delete_link);
                $post->setRaw_time_mess($raw_time_mess);



                //set tags for cache
                $this->setCacheTag(array(
                    'post_id_' . $post->getId(),
                    'user_id_' . $post->getId_author(),
                ));
            }
        }
        return $posts;
    }

    /**
     * View post for users
     *
     * @param ind $user_id
     * @return none
     */
    public function user_posts($user_id = null) {

        if (!is_numeric($user_id))
            return $this->showMessage(__('Value must be numeric'));

        $user_id = (int)$user_id;
        if ($user_id < 1)
            return $this->showMessage(__('Can not find user'));

        //turn access
        \ACL::turnUser(array($this->module, 'view_forums_list'),true);
        \ACL::turnUser(array($this->module, 'view_forums'),true);
        \ACL::turnUser(array($this->module, 'view_themes'),true);

        $this->page_title = __('User messages');

        if ($this->cached && $this->Cache->check($this->cacheKey)) {
            $source = $this->Cache->read($this->cacheKey);
        } else {
            $usersModel = \OrmManager::getModelInstance('Users');
            $user = $usersModel->getById($user_id);
            if (!$user)
                return $this->showMessage(__('Can not find user'));


            // Заголовок страницы (содержимое тега title)
            $this->page_title .= ' "' . h($user->getName()) . '"';


            $markers = array();
            $markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
                    . get_link(__('forum'), $this->getModuleURL()) . __('Separator') . __('User messages') . ' "' . h($user->getName()) . '"';


            // Page nav
            $postsModel = \OrmManager::getModelInstance('ForumPosts');
            $total = $postsModel->getTotal(array('cond' => array('id_author' => $user_id)));
            if ($total > 0) {
                list($pages, $page) = pagination($total, \Config::read('posts_per_page', $this->module), $this->getModuleURL('user_posts/' . $user_id));
                $markers['pagination'] = $pages;
                //$this->page_title .= ' (' . $page . ')';



                // SELECT posts
                $postsModel->bindModel('theme');
                $postsModel->bindModel('author');
                $postsModel->bindModel('editor');
                $postsModel->bindModel('attacheslist');
                $posts = $postsModel->getCollection(array(
                    'id_author' => $user_id,
                ), array(
                    'order' => 'time DESC, id DESC',
                    'page' => $page,
                    'limit' => \Config::read('posts_per_page', $this->module),
                ));
            } else {
                $markers['pagination'] = null;
                $posts = array();
                $page = 1;
            }
            // обнуляем маркеры, что бы избежать ошибок.
            $markers['add_link'] = '';
            $markers['admin_bar'] = '';
            $markers['meta'] = '';
            $markers['reply_form'] = '';
            $this->_globalize($markers);


            $source = $this->render('posts_list.html', array(
                'posts' => $this->__parsePostsTable($posts, $page),
                'theme' => array('poll' => null, 'title' => __('User messages') . ' "' . h($user->getName()) . '"'),
            ));


            //write into cache
            if ($this->cached)
                $this->Cache->write($source, $this->cacheKey, $this->cacheTags);
        }

        return $this->_view($source);
    }

    private function __savePoll($theme) {
        $poll = isset($_POST['poll']) ? '1' : '0';
        $poll_question = isset($_POST['poll_question']) ? h(trim(mb_substr($_POST['poll_question'], 0, 250))) : '';
        $poll_ansvers = isset($_POST['poll_ansvers']) ? h(trim(mb_substr($_POST['poll_ansvers'], 0, 1000))) : '';


        if ($poll && $poll_ansvers) {

            $ansvers = explode("\n", $poll_ansvers);

            $variants = array();
            if (count($ansvers) && is_array($ansvers)) {
                foreach ($ansvers as $ansver) {
                    $variants[] = array(
                        'ansver' => $ansver,
                        'votes' => 0,
                    );
                }
            }

            $data = array(
                'variants' => json_encode($variants),
                'question' => $poll_question,
                'theme_id' => $theme->getId(),
                'voted_users' => '',
            );


            $poll = new \ForumModule\ORM\ForumPollsEntity($data);
            $poll->save();
            return true;
        }
        return false;
    }

    protected function _renderPoll($poll) {
        if (!$poll) {

        }


        $questions = json_decode($poll->getVariants(), true);
        if (!$questions && !is_array($questions)) {

        }


        $all_votes_summ = 0;
        foreach ($questions as $case) {
            $all_votes_summ += $case['votes'];
        }

        // Find 1% value
        $percent = round($all_votes_summ / 100, 2);


        // Show percentage graph for each variant
        foreach ($questions as $k => $case) {
            $questions[$k] = array(
                'ansver' => h($case['ansver']),
                'votes' => $case['votes'],
                'percentage' => ($case['votes'] > 0) ? round($case['votes'] / $percent) : 0,
                'ansver_id' => $k + 1,
            );

            //$poll->setPercentage(round($case / $percent));
        }

        $poll->setVariants($questions);


        // Did user voted
        if (!empty($_SESSION['user']['name'])) {
            $voted_users = explode(',', $poll->getVoted_users());
            if ($voted_users && is_array($voted_users)) {


                if (!in_array($_SESSION['user']['id'], $voted_users)) {
                    $poll->setCan_voted(1);
                }
            }
        }


        return $this->render('polls.html', array('poll' => $poll));
    }

    /**
     *
     */
    public function vote_poll($id) {


        header('X-Robots-Tag: noindex,nofollow');

        $this->counter = false;
        $this->cached = false;

        if (empty($_SESSION['user']))
            die('ERROR: '.__('Permission denied'));

        if (!is_numeric($id))
            die('ERROR: '.__('Value must be numeric'));

        $id = (int)$id;
        if ($id < 1)
            die('ERROR: '.__('empty ID'));


        $ansver_id = (!empty($_GET['ansver'])) ? intval($_GET['ansver']) : 0;
        if ($ansver_id < 1)
            die('ERROR: '.__('empty ANSVER_ID'));


        $pollsModel = new ORM\ForumPollsModel;
        $poll = $pollsModel->getById($id);

        if (empty($poll))
            die('ERROR: '.__('poll not found'));

        $variants = json_decode($poll->getVariants(), true);
        if ($variants && is_array($variants)) {


            if (!array_key_exists($ansver_id - 1, $variants))
                die('ERROR: '.__('wrong ansver'));


            // Check user ability
            $voted_users = explode(',', $poll->getVoted_users());
            if (!empty($voted_users)) {
                if (in_array($_SESSION['user']['id'], $voted_users)) {
                    die('ERROR: '.__('you already voted'));
                } else {
                    $voted_users[] = $_SESSION['user']['id'];
                }
            } else {
                $voted_users = array($_SESSION['user']['id']);
            }

            $poll->setVoted_users(implode(',', $voted_users));


            $variants[$ansver_id - 1]['votes']++;

            $poll->setVariants(json_encode($variants));
            $poll->save();




            // Create response data for AJAX request
            $all_votes_summ = 0;
            foreach ($variants as $case) {
                $all_votes_summ += $case['votes'];
            }

            // Find 1% value
            $percent = round($all_votes_summ / 100, 2);


            // Show percentage graph for each variant
            foreach ($variants as $k => $case) {
                $variants[$k] = array(
                    'ansver' => h($case['ansver']),
                    'votes' => $case['votes'],
                    'percentage' => ($case['votes'] > 0) ? round($case['votes'] / $percent) : 0,
                    'ansver_id' => $k + 1,
                );
            }

            die(json_encode($variants));
        }

        die('ERROR');
    }

    private function __checkThemeAccess($theme) {
        $fid = $theme->getId_forum();
        $rules = $theme->getGroup_access();
        $id = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;

        foreach ($rules as $k => $v)
            if ('' === $v)
                unset($rules[$k]);

        if (in_array($id, $rules)) {
            return $this->showMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $fid));
        }
    }

    /**
     * View last posts and last themes
     * Build list with themes ordered by add date
     *
     * @return string html content
     */
    public function last_posts() {

        $this->page_title = __('Last update');


        if ($this->cached && $this->Cache->check($this->cacheKey)) {
            $html = $this->Cache->read($this->cacheKey);
            return $this->_view($html);
        }

        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        // Page nav
        $nav = array();
        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $total = $themesModel->getTotal();
        $perPage = intval(\Config::read('themes_per_page', $this->module));
        if ($perPage < 1)
            $perPage = 10;
        list($pages, $page) = pagination($total, $perPage, $this->getModuleURL('last_posts/'));
        $nav['pagination'] = $pages;
        //$this->page_title .= ' (' . $page . ')';


        $cntPages = ceil($total / $perPage);
        $recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
        $firstOnPage = ($page - 1) * $perPage + 1;
        $lastOnPage = $firstOnPage + $recOnPage - 1;

        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(__('forum'), $this->getModuleURL()) . __('Separator') . __('Last update');
        $nav['meta'] = __('Count all topics') . ' ' . $total . '. ' . ($total > 1 ? __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage : '');
        $this->_globalize($nav);

        if ($total < 1)
            return $this->_view(__('No topics'));



        //get records
        $themesModel->bindModel('forum');
        $themesModel->bindModel('author');
        $themesModel->bindModel('last_author');
        $order = getOrderParam(__CLASS__);
        $themes = $themesModel->getCollection(array(), array(
            'order' => (empty($order) ? 'last_post DESC' : $order),
            'page' => $page,
            'limit' => $perPage,
        ));


        foreach ($themes as $theme) {
            if ($theme) {
                if (!$this->forumACL('view_forums',$theme->getParent_forum_id())) continue;
                if (!$this->forumACL('view_forums',$theme->getId_forum())) continue;
                if (!$this->forumACL('view_themes',$theme->getParent_forum_id())) continue;
                if (!$this->forumACL('view_themes',$theme->getId_forum())) continue;
                $theme_pf = $theme->getForum() ? get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $theme->getId_forum())) : '';
                $theme->setParent_forum($theme_pf);
                $theme = $this->__parseThemeTable($theme);

                //set cache tags
                $this->setCacheTag(array(
                    'theme_id_' . $theme->getId(),
                ));
            }
        }


        // write into cache
        if ($this->cached) {
            $this->Cache->write($html, $this->cacheKey, $this->cacheTags);
        }

        //pr($themes); die();
        $source = $this->render('lastposts_list.html', array(
            'context' => array(
                'forum_name' => __('Last update'),
            ),
            'themes' => $themes
            )
        );
        $this->_view($source);
    }

    /**
     * Create HTML form for edit forum and paste current values into inputs
     */
    public function edit_forum_form($id_forum = null) {
        if (!is_numeric($id_forum))
            return $this->showMessage(__('Value must be numeric'));

        $id_forum = (int)$id_forum;
        if ($id_forum < 1)
            return $this->showMessage(__('Can not find forum'));
        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'), $this->getModuleURL('view_forum/'.$id_forum));

        //check access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        $this->forumACL('view_forums',$id_forum,true);
        $this->forumACL('edit_forums',$id_forum,true);

        $html = '';
        $action = get_url($this->getModuleURL('update_forum/' . $id_forum));


        // Если при заполнении формы были допущены ошибки
        if (isset($_SESSION['editForumForm'])) {
            $title = h($_SESSION['editForumForm']['title']);
            $description = h($_SESSION['editForumForm']['description']);
            unset($_SESSION['editForumForm']);
        } else {
            // Получаем из БД информацию о форуме
            $forum = $this->Model->getById($id_forum);
            $title = $forum ? h($forum->getTitle()) : '';
            $description = $forum ? h($forum->getDescription()) : '';
        }

        $this->page_title = __('Editing forum');
        // nav block
        $navi = array();
        $navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
            . get_link(__('forum'), $this->getModuleURL()) . __('Separator')
            . get_link(h($title), '/' . $this->module . '/view_forum/' . $id_forum . '/') . __('Separator')
            . __('Editing forum');
        $this->_globalize($navi);


        // Считываем в переменную содержимое файла,
        // содержащего форму для редактирования форума
        $source = $this->render('editforumform.html', array(
            'context' => array(
                'action' => $action,
                'title' => $title,
                'description' => $description,
            ),
        ));

        $html = $html . $source;
        return $this->_view($html);
    }

    /**
     * Get request and work for it. \Validate data and update record
     */
    public function update_forum($id_forum = null) {
        if (!is_numeric($id_forum))
            return $this->showMessage(__('Value must be numeric'));

        $id_forum = (int)$id_forum;
        if ($id_forum < 1)
            return $this->showMessage(__('Can not find forum'));

        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'));

        //check access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        $this->forumACL('view_forums',$id_forum,true);
        $this->forumACL('edit_forums',$id_forum,true);

        $forum = $this->Model->getById($id_forum);
        if (!$forum)
            return $this->showMessage(__('Can not find forum'));


        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $title = trim(mb_substr($_POST['title'], 0, 120));
        $description = trim(mb_substr($_POST['description'], 0, 250));


        // Check fields fo empty values and valid chars
        $error = '';
        
        if (empty($title))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Forum name')) . '</li>' . "\n";
        elseif (!\Validate::cha_val($title, V_TITLE_NOHTML))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Forum name')) . '</li>' . "\n";
        if (!empty($description) and !\Validate::cha_val($description, V_TITLE_NOHTML))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Description')) . '</li>' . "\n";


        // if an errors
        if (!empty($error)) {
            $_SESSION['editForumForm'] = array();
            $_SESSION['editForumForm']['errors'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' .
                    "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            $_SESSION['editForumForm']['title'] = $title;
            $_SESSION['editForumForm']['description'] = $description;

            return $this->showMessage($_SESSION['editForumForm']['errors'], $this->getModuleURL('update_forum/'.$id_forum));
        }

        $forum->setTitle($title);
        $forum->setDescription($description);
        $forum->save();

        //clean cache
        $this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));
        if ($this->isLogging)
            \Logination::write('editing forum', 'forum id(' . $id_forum . ')');
        return $this->showMessage(__('Forum update is successful'), $this->getModuleURL('view_forum/'.$id_forum), 'ok');
    }

    /**
     * raise forum
     *
     * @id_forum (int)    forum ID
     * @return           info message
     */
    public function forum_up($id_forum = null) {
        if (!is_numeric($id_forum))
            return $this->showMessage(__('Value must be numeric'));

        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'));

        $id_forum = (int)$id_forum;
        if ($id_forum < 1)
            return $this->showMessage(__('Can not find forum'));


        //check access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        $this->forumACL('view_forums',$id_forum,true);
        $this->forumACL('replace_forums',$id_forum,true);

        // upper forum
        $forum = $this->Model->getById($id_forum);
        if (!$forum)
            return $this->showMessage(__('Can not find forum'));
        // upper position
        $order_up = $forum->getPos();



        $dforum = $this->Model->getFirst(array(
            'pos < ' . $order_up,
            'in_cat' => $forum->getIn_cat(),
            'parent_forum_id' => $forum->getParent_forum_id(),
        ), array(
            'order' => 'pos DESC',
        ));
        if (!$dforum)
            return $this->showMessage(__('Forum is above all'));


        // Порядок следования и ID форума, который находится выше и будет "опущен" вниз
        // ( поменявшись местами с форумом, который "поднимается" вверх )
        $order_down = $dforum->getPos();

        // replace forums
        $dforum->setPos($order_up);
        $res1 = $dforum->save();

        $forum->setPos($order_down);
        $res2 = $forum->save();


        //clean cache
        $this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));

        if ($this->isLogging)
            \Logination::write('uping forum', 'forum id(' . $id_forum . ')');
        if ($res1 && $res2)
            return $this->showMessage(__('Operation is successful'), false,'alert');
        else
            return $this->showMessage(__('Some error occurred'));
    }

    /**
     * down forum
     *
     * @id_forum (int)    forum ID
     * @return           info message
     */
    public function forum_down($id_forum = null) {
        if (!is_numeric($id_forum))
            return $this->showMessage(__('Value must be numeric'));

        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'));

        $id_forum = (int)$id_forum;
        if ($id_forum < 1)
            return $this->showMessage(__('Can not find forum'));


        //check access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        $this->forumACL('view_forums',$id_forum,true);
        $this->forumACL('replace_forums',$id_forum,true);

        // downing forum
        $id_forum_down = $id_forum;
        $forum = $this->Model->getById($id_forum_down);
        if (!$forum)
            return $this->showMessage(__('Can not find forum'));
        // upper position
        $order_down = $forum->getPos();


        $dforum = $this->Model->getFirst(array(
            'pos > ' . $order_down,
            'in_cat' => $forum->getIn_cat(),
            'parent_forum_id' => $forum->getParent_forum_id(),
        ), array(
            'order' => 'pos ASC',
        ));
        if (!$dforum)
            return $this->showMessage(__('Forum is below all'));


        // Порядок следования и ID форума, который находится ниже и будет "поднят" вверх
        // ( поменявшись местами с форумом, который "опускается" вниз )
        $order_up = $dforum->getPos();

        // replace forums
        $dforum->setPos($order_down);
        $res1 = $dforum->save();

        $forum->setPos($order_up);
        $res2 = $forum->save();


        //clean cache
        $this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));

        if ($this->isLogging)
            \Logination::write('down forum', 'forum id(' . $id_forum . ')');
        if ($res1 && $res2)
            return $this->showMessage(__('Operation is successful'), false,'alert');
        else
            return $this->showMessage(__('Some error occurred'));
    }

    /**
     * delete forum
     *
     * @id_forum (int)    forum ID
     * @return            info message
     */
    public function delete_forum($id_forum = null) {

        if (!is_numeric($id_forum))
            return $this->showMessage(__('Value must be numeric'));

        $id_forum = (int)$id_forum;
        if ($id_forum < 1)
            return $this->showMessage(__('Can not find forum'));

        //check access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        $this->forumACL('view_forums',$id_forum,true);
        $this->forumACL('delete_forums',$id_forum,true);

        $forum = $this->Model->getById($id_forum);
        if (!$forum)
            return $this->showMessage(__('Can not find forum'));


        // Можно удалить только форум, который не содержит тем (в целях безопасности)
        $themeModel = \OrmManager::getModelInstance('ForumThemes');
        $themes = $themeModel->getTotal(array('cond' => array('id_forum' => $id_forum)));
        if ($themes > 0) {
            return $this->showMessage(__('Can not delete forum with themes'));
        } else {
            $forum->delete();
        }

        //clean cache
        $this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));
        if ($this->isLogging)
            \Logination::write('delete forum', 'forum id(' . $id_forum . ')');
        return $this->showMessage(__('Operation is successful'), false,'alert');
    }

    /**
     * form per add theme into forum
     *
     * @id_forum (int)    forum ID
     * @return            html content
     */
    public function add_theme_form($id_forum = null) {

        if (!is_numeric($id_forum))
            return $this->showMessage(__('Value must be numeric'));

        $id_forum = (int)$id_forum;
        if ($id_forum < 1)
            return $this->showMessage(__('Can not find forum'));

        //check access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        $this->forumACL('view_forums',$id_forum,true);
        $this->forumACL('add_themes',$id_forum,true);

        $writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;

        $this->page_title = __('New topic');

        $forum = $this->Model->getById($id_forum);
        if (!$forum)
            return $this->showMessage(__('Can not find forum'));



        // Check access to this forum. May be locked by pass or posts count
        $this->__checkForumAccess($forum);


        // errors
        if (isset($_SESSION['addThemeForm'])) {
            $theme = h($_SESSION['addThemeForm']['theme']);
            $description = h($_SESSION['addThemeForm']['description']);
            $message = $_SESSION['addThemeForm']['message'];
            $gr_access = $_SESSION['addThemeForm']['gr_access'];
            $first_top = $_SESSION['addThemeForm']['first_top'];
            $locked = $_SESSION['addThemeForm']['locked'];
            $poll = $_SESSION['addThemeForm']['poll'];
            $poll_question = h($_SESSION['addThemeForm']['poll_question']);
            $poll_ansvers = h($_SESSION['addThemeForm']['poll_ansvers']);
            unset($_SESSION['addThemeForm']);
        }

        $max_attaches = \Config::read('max_attaches', $this->module);
        if (empty($max_attaches) || !is_numeric($max_attaches))
            $max_attaches = 5;
        $locked_attaches = \Config::read('locked_attaches', $this->module);
        $markers = array(
            'action' => get_url($this->getModuleURL('add_theme/' . $id_forum)),
            'theme' => (!empty($theme)) ? $theme : '',
            'description' => (!empty($description)) ? $description : '',
            'main_text' => (!empty($message)) ? $message : '',
            'gr_access' => (!empty($gr_access)) ? $gr_access : array(),
            'first_top' => (!empty($first_top)) ? $first_top : '0',
            'locked' => (!empty($locked)) ? $locked : '0',
            'poll' => (!empty($poll)) ? $poll : '0',
            'poll_question' => (!empty($poll_question)) ? $poll_question : '',
            'poll_ansvers' => (!empty($poll_ansvers)) ? $poll_ansvers : '',
            'max_attaches' => $max_attaches,
            'locked_attaches' => $locked_attaches
        );

        // nav block
        $navi = array();
        $navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(__('forum'), $this->getModuleURL()) . __('Separator')
                . get_link($forum->getTitle(), $this->getModuleURL('view_forum/' . $id_forum)) . __('Separator')
                . __('New topic');
        $this->_globalize($navi);

        $html = '';
        $html = $this->render('addthemeform.html', array(
            'context' => $markers,
        ));
        return $this->_view($html);
    }

    /**
     * add theme into forum
     *
     * @id_forum (int)    forum ID
     * @return            info message
     */
    public function add_theme($id_forum = null) {

        if (!is_numeric($id_forum))
            return $this->showMessage(__('Value must be numeric'));

        $id_forum = (int)$id_forum;
        if ($id_forum < 1)
            return $this->showMessage(__('Some error occurred'));


        if (!isset($_POST['theme']) || !isset($_POST['mainText']))
            return $this->showMessage(__('Some error occurred'));

        //check access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        $this->forumACL('view_forums',$id_forum,true);
        $this->forumACL('add_themes',$id_forum,true);

        $forum = $this->Model->getById($id_forum);
        if (!$forum)
            return $this->showMessage(__('Can not find forum'));



        // Check access to this forum. May be locked by pass or posts count
        $this->__checkForumAccess($forum);

        $user_id = $_SESSION['user']['id'];

        // cut lenght
        $name = trim(mb_substr($_POST['theme'], 0, 55));
        $description = trim(mb_substr($_POST['description'], 0, 128));
        $message = trim($_POST['mainText']);
        $first_top = isset($_POST['first_top']) ? '1' : '0';
        $locked = isset($_POST['locked']) ? '1' : '0';
        $poll = isset($_POST['poll']) ? '1' : '0';
        $poll_question = isset($_POST['poll_question']) ? h(trim(mb_substr($_POST['poll_question'], 0, 250))) : '';
        $poll_ansvers = isset($_POST['poll_ansvers']) ? h(trim(mb_substr($_POST['poll_ansvers'], 0, 1000))) : '';


        $gr_access = array();
        $groups = \ACL::getGroups();
        foreach ($groups as $grid => $grval) {
            if (isset($_POST['gr_access_' . $grid]))
                $gr_access[] = $grid;
        }


        $error = '';
        if (!empty($gr_access) &&
            !$this->forumACL('set_access_themes',$id_forum) &&
            !$this->forumACL('set_access_mine_themes',$id_forum)
        ) {
            $error .= '<li>' . __('You are not allowed to set access to the theme') . '</li>' . "\n";
        }
        if ($locked == 1 && !$this->forumACL('close_themes',$id_forum)) {
            $error .= '<li>' . __('You can not close the theme') . '</li>' . "\n";
        }

        // Check fields of empty values and valid chars
        
        if (empty($name))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Theme title')) . '</li>' . "\n";
        elseif (!\Validate::cha_val($name, V_TITLE_NOHTML))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Theme title')) . '</li>' . "\n";
        if (!empty($description) and !\Validate::cha_val($description, V_TITLE_NOHTML))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Description')) . '</li>' . "\n";
        if (empty($message))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Material body')) . '</li>' . "\n";
        if (mb_strlen($message) > \Config::read('max_post_lenght', $this->module))
            $error .= '<li>' . sprintf(__('Very big "param"'), __('Material body'), \Config::read('max_post_lenght', $this->module)) . '</li>' . "\n";

        if ($poll) {
            if (!\ACL::turnUser(array($this->module,'add_polls')))
                $error .= '<li>' . __('You are not allowed to create polls in the themes') . '</li>' . "\n";
            if (empty($poll_question))
                $error .= '<li>' . sprintf(__('Empty field "param"'), __('Poll question')) . '</li>' . "\n";
            elseif (mb_strlen($poll_question) < 5)
                $error .= '<li>' . sprintf(__('Very small "param"'), __('Poll question'), 5) . '</li>' . "\n";
            if (empty($poll_ansvers))
                $error .= '<li>' . sprintf(__('Empty field "param"'), __('Poll ansvers')) . '</li>' . "\n";
            else {
                $answers = explode("\n", $poll_ansvers);
                if (!$answers || !is_array($answers) || count($answers) < 2)
                    $error .= '<li>' . __('Few "poll_ansvers"') . '</li>' . "\n";
                elseif (count($answers) > 20)
                    $error .= '<li>' . __('Many "poll_ansvers"') . '</li>' . "\n";
            }
        }

        $message = mb_substr($message, 0, \Config::read('max_post_lenght', $this->module));

        // Проверка на валидность аттачей
        $out = checkAttaches($this->module);
        if ($out != null)
            $error .= $out;

        // errors
        if (!empty($error)) {
            $_SESSION['addThemeForm'] = array();
            $_SESSION['addThemeForm']['errors'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' .
                    "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            $_SESSION['addThemeForm']['theme'] = $name;
            $_SESSION['addThemeForm']['description'] = $description;
            $_SESSION['addThemeForm']['message'] = $message;
            $_SESSION['addThemeForm']['gr_access'] = $gr_access;
            $_SESSION['addThemeForm']['first_top'] = $first_top;
            $_SESSION['addThemeForm']['locked'] = $locked;
            $_SESSION['addThemeForm']['poll'] = $poll;
            $_SESSION['addThemeForm']['poll_question'] = $poll_question;
            $_SESSION['addThemeForm']['poll_ansvers'] = $poll_ansvers;
            return $this->showMessage($_SESSION['addThemeForm']['errors'], $this->getModuleURL('add_theme_form/'.$id_forum), 'error');
        }


        $data = array(
            'title' => $name,
            'description' => $description,
            'id_author' => $user_id,
            'time' => new \Expr('NOW()'),
            'id_last_author' => $user_id,
            'last_post' => new \Expr('NOW()'),
            'id_forum' => $id_forum,
            'first_top' => $first_top,
        );


        $data['group_access'] = $gr_access;
        $data['locked'] = $locked;
        $theme = new \ForumModule\ORM\ForumThemesEntity($data);
        $id_theme = $theme->save();
        $theme->setId($id_theme);

        $this->__savePoll($theme);

        $locked_attaches = intval(\Config::read('locked_attaches', $this->module));
        // add first post
        $postData = array(
            'message' => $message,
            'id_author' => $user_id,
            'time' => new \Expr('NOW()'),
            'edittime' => new \Expr('NOW()'),
            'id_theme' => $id_theme,
            'id_forum' => $id_forum,
            'locked_attaches' => $locked_attaches
        );
        $post = new \ForumModule\ORM\ForumPostsEntity($postData);
        $post_id = $post->save();

        /*         * *** ATTACH **** */
        if (!$locked_attaches and \ACL::turnUser(array($this->module, 'upload_files'))) {
            if (downloadAttaches($this->module, $post_id) == null) {
                $postsModel = \OrmManager::getModelInstance('ForumPosts');
                $post = $postsModel->getById($post_id);
                if ($post) {
                    $post->setAttaches('1');
                    $post->save();
                }
            }
        }
        /*         * *** END ATTACH **** */

        // Обновляем число оставленных сообщений и созданных тем
        if (!empty($_SESSION['user']['name'])) {
            $usersModel = \OrmManager::getModelInstance('Users');
            $user = $usersModel->getById($_SESSION['user']['id']);
            if ($user) {
                $user->setThemes($user->getThemes() + 1);
                $user->setPosts($user->getPosts() + 1);
                $user->save();
            }
        }


        $forum->setThemes($forum->getThemes() + 1);
        $forum->setLast_theme_id($id_theme);
        $forum->save();


        //clean cache
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array(
            'user_id_' . $user_id,
            'forum_id_' . $id_forum,
        ));
        if ($this->isLogging)
            \Logination::write('adding theme', 'theme id(' . $id_theme . '), post id(' . $post_id . ')');
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL('view_theme/'. $id_theme),'ok');
    }

    /**
     * form per edit theme
     *
     * @id_forum (int)    theme ID
     * @return            html content
     */
    public function edit_theme_form($id_theme = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);


        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'));

        $this->page_title = __('Edit theme');

        // Получаем из БД информацию о редактируемой теме
        $themeModel = \OrmManager::getModelInstance('ForumThemes');
        $themeModel->bindModel('author');
        $theme = $themeModel->getById($id_theme);
        if (!$theme)
            return $this->showMessage(__('Topic not found'));


        $id_forum = $theme->getId_forum();
        $html = '';


        //check access
        if (!$this->forumACL('view_forums', $id_forum))
            return $this->showMessage(__('Permission denied'), $this->getModuleURL());
        if (!$this->forumACL('view_themes', $id_forum))
            return $this->showMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $id_forum));
        if (!$this->forumACL('edit_themes', $id_forum)
                && (empty($_SESSION['user']['id'])
                    || $theme->getId_author() != $_SESSION['user']['id']
                    || !$this->forumACL('edit_mine_themes', $id_forum)
                   )
           ) {
            return $this->showMessage(__('Permission denied'), $this->getModuleURL('view_theme/' . $id_theme));
        }


        // Если при заполнении формы были допущены ошибки
        if (isset($_SESSION['editThemeForm'])) {
            $name = h($_SESSION['editThemeForm']['theme']);
            $description = h($_SESSION['editThemeForm']['description']);
            $gr_access = $_SESSION['editThemeForm']['gr_access'];
            $first_top = $_SESSION['editThemeForm']['first_top'];
            $locked = $_SESSION['editThemeForm']['locked'];
            unset($_SESSION['editThemeForm']);
        } else {
            $name = h($theme->getTitle());
            $description = h($theme->getDescription());
            $gr_access = $theme->getGroup_access();
            $first_top = $theme->getFirst_top();
            $locked = $theme->getLocked();
        }


        // Формируем список форумов, чтобы можно было переместить тему в другой форум
        $forums = $this->Model->getCollection(array(), array('order' => 'pos'));
        if (!$forums)
            return $this->showMessage(__('Some error occurred'), $this->getModuleURL());


        $options = '';
        foreach ($forums as $forum) {
            if (
                $this->forumACL('view_forums', $forum->getId()) &&
                $this->forumACL('view_themes', $forum->getId()) &&
                $this->forumACL('add_themes', $forum->getId()) &&
                !$forum->getLock_passwd() &&
                (!$forum->getLock_posts() || $forum->getLock_posts() >= $forum->getPosts())
            ) {
                if ($forum->getId() == $theme->getId_forum())
                    $options .= '<option value="' . $forum->getId() . '" selected>' . h($forum->getTitle()) . '</option>' . "\n";
                else
                    $options .= '<option value="' . $forum->getId() . '">' . h($forum->getTitle()) . '</option>' . "\n";
            }
        }


        $author_name = ($theme->getId_author() && $theme->getAuthor()) ? h($theme->getAuthor()->getName()) : __('Guest');
        $data = array(
            'action' => get_url($this->getModuleURL('update_theme/' . $id_theme)),
            'theme' => $name,
            'description' => $description,
            'author' => $author_name,
            'options' => $options,
            'first_top' => (!empty($first_top)) ? $first_top : '0',
        );

        if ($this->forumACL('set_access_themes',$id_forum) || (
            $theme->getId_author() == $_SESSION['user']['id'] && $this->forumACL('set_access_mine_themes',$id_forum))
        ) {
            $data['gr_access'] = (!empty($gr_access)) ? $gr_access : array();
        }
        if ($this->forumACL('close_themes',$id_forum)) {
            $data['locked'] = (!empty($locked)) ? $locked : '0';
        }

        // nav block
        $navi = array();
        $navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
                            . get_link(__('forum'), $this->getModuleURL()) . __('Separator') . __('Edit theme');
        $this->_globalize($navi);


        $source = $this->render('editthemeform.html', array(
            'context' => $data,
        ));
        $html = $html . $source;
        return $this->_view($html);
    }

    /**
     * update theme
     *
     * @id_forum (int)    theme ID
     * @return            info message
     */
    public function update_theme($id_theme = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        // Если не переданы данные формы - функция вызвана по ошибке
        if (!isset($_POST['id_forum']) || !isset($_POST['theme']))
            return $this->showMessage(__('Some error occurred'));

        $id_forum = (int)$_POST['id_forum'];

        if ($id_forum < 1)
            return $this->showMessage(__('Topic not found'));

        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'));




        $themeModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themeModel->getById($id_theme);
        if (!$theme)
            return $this->showMessage(__('Topic not found'));

        //check access
        if (!$this->forumACL('view_forums', $id_forum))
            return $this->showMessage(__('Permission denied'));
        if (!$this->forumACL('view_themes', $id_forum))
            return $this->showMessage(__('Permission denied'),$this->getModuleURL('view_forum/'.$id_forum));
        if (!$this->forumACL('edit_themes', $id_forum)
                && (empty($_SESSION['user']['id'])
                    || $theme->getId_author() != $_SESSION['user']['id']
                    || !$this->forumACL('edit_mine_themes', $id_forum)
                   )
           ) {
            return $this->showMessage(__('Permission denied'),$this->getModuleURL('view_theme/'.$id_theme));
        }

        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $id_from_forum = $theme->getId_forum();
        $name = trim(mb_substr($_POST['theme'], 0, 55));
        $description = trim(mb_substr($_POST['description'], 0, 128));
        $first_top = isset($_POST['first_top']) ? '1' : '0';
        $locked = isset($_POST['locked']) ? '1' : '0';

        $gr_access = array();
        $groups = \ACL::getGroups();
        foreach ($groups as $grid => $grval) {
            if (isset($_POST['gr_access_' . $grid]))
                $gr_access[] = $grid;
        }

        $error = '';
        if (!empty($gr_access) && !(
            $this->forumACL('set_access_themes',$id_forum) ||
            ($theme->getId_author() == $_SESSION['user']['id'] && $this->forumACL('set_access_mine_themes',$id_forum))
        )) {
            $error .= '<li>' . __('You are not allowed to set access to the theme') . '</li>' . "\n";
        }
        if ($locked != $theme->getLocked() && !$this->forumACL('close_themes',$id_forum)) {
            $error .= '<li>' . __('You can not close the theme') . '</li>' . "\n";
        }

        // validate ...
        
        if (empty($name))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Theme title')) . '</li>' . "\n";
        elseif (!\Validate::cha_val($name, V_TITLE_NOHTML))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Theme title')) . '</li>' . "\n";
        if (!empty($description) and !\Validate::cha_val($description, V_TITLE_NOHTML))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Description')) . '</li>' . "\n";

        // errors
        if (!empty($error)) {
            $_SESSION['editThemeForm'] = array();
            $_SESSION['editThemeForm']['errors'] = '<p class="errorMsg">' . __('Some error in form')
                    . '</p>' . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            $_SESSION['editThemeForm']['theme'] = $name;
            $_SESSION['editThemeForm']['description'] = $description;
            $_SESSION['editThemeForm']['gr_access'] = $gr_access;
            $_SESSION['editThemeForm']['first_top'] = $first_top;
            $_SESSION['editThemeForm']['locked'] = $locked;
            return $this->showMessage($_SESSION['editThemeForm']['errors'], $this->getModuleURL('edit_theme_form/'.$id_theme));
        }


        // update theme
        $theme->setTitle($name);
        $theme->setDescription($description);
        $theme->setId_forum($id_forum);
        $theme->setFirst_top($first_top);
        $theme->setGroup_access($gr_access);
        $theme->setLocked($locked);
        $theme->save();


        //update forums info
        if ($id_from_forum != $id_forum) {
            $new_forum = $this->Model->getById($id_forum);
            if (!$new_forum)
                return $this->showMessage(__('No forum for moving'),$this->getModuleURL('edit_theme_form/'.$id_theme));


            $postsModel = \OrmManager::getModelInstance('ForumPosts');
            $posts_cnt = $postsModel->getTotal(array('cond' => array('id_theme' => $id_theme)));

            $from_forum = $this->Model->getById($id_from_forum);
            if ($from_forum) {
                $from_forum->setPosts($from_forum->getPosts() - $posts_cnt + 1);
                $from_forum->setThemes($from_forum->getThemes() - 1);
                $from_forum->save();
            }

            $new_forum->setPosts($new_forum->getPosts() + $posts_cnt - 1);
            $new_forum->setThemes($new_forum->getThemes() + 1);
            $new_forum->save();


            $this->Model->upLastPost($id_from_forum, $id_forum);
        }


        //clean cahce
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
        if ($this->isLogging)
            \Logination::write('editing theme', 'theme id(' . $id_theme . ')');
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL('view_theme/' . $id_theme),'ok');
    }

    /**
     * Deleting theme
     */
    public function delete_theme($id_theme = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'));


        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themesModel->getById($id_theme);
        if (!$theme)
            return $this->showMessage(__('Topic not found'));


        //check access
        if (!$this->forumACL('view_forums', $theme->getId_forum()))
            return $this->showMessage(__('Permission denied'));
        if (!$this->forumACL('view_themes', $theme->getId_forum()))
            return $this->showMessage(__('Permission denied'),$this->getModuleURL('view_forum/'.$theme->getId_forum()));
        if (!$this->forumACL('delete_themes', $theme->getId_forum())
                || (!empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id']
                && !$this->forumACL('delete_mine_themes',$theme->getId_forum()))) {
            return $this->showMessage(__('Permission denied'),$this->getModuleURL('view_forum/'.$theme->getId_forum()));
        }


        $this->__delete_theme($theme);
        if ($this->isLogging)
            \Logination::write('delete theme', 'theme id(' . $id_theme . ')');
        $backlink = get_url($this->getModuleURL('view_forum/' . $theme->getId_forum()));
        return $this->showMessage(__('Theme is deleted'), $backlink, ($backlink == getReferer() ? 'alert' : 'ok'), true);
    }

    /**
     * Close Theme
     */
    public function lock_theme($id_theme = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'));


        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themesModel->getById($id_theme);
        if (!$theme)
            return $this->showMessage(__('Topic not found'));

        if (!$this->forumACL('view_forums', $theme->getId_forum()))
            return $this->showMessage(__('Permission denied'));
        if (!$this->forumACL('view_themes', $theme->getId_forum()))
            return $this->showMessage(__('Permission denied'),$this->getModuleURL('view_forum/'.$theme->getId_forum()));
        if (!$this->forumACL('close_themes', $theme->getId_forum()))
            return $this->showMessage(__('Permission denied'),$this->getModuleURL('view_forum/'.$theme->getId_forum()));

        // Сначала заблокируем сообщения (посты) темы
        $posts = $postsModel->getCollection(array('id_theme' => $id_theme));
        if ($posts) {
            foreach ($posts as $post) {
                $post->setLocked('1');
                $post->save();
            }
        }

        // Теперь заблокируем тему
        $theme->setLocked('1');
        $theme->save();


        //clean cache
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
        if ($this->isLogging)
            \Logination::write('lock theme', 'theme id(' . $id_theme . ')');
        return $this->showMessage(__('Theme is locked'), $this->getModuleURL('view_forum/' . $theme->getId_forum()),'alert');
    }

    /**
     * Unlocking Theme
     */
    public function unlock_theme($id_theme = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'));


        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themesModel->getById($id_theme);
        if (!$theme)
            return $this->showMessage(__('Topic not found'));

        // Check \ACL
        if (!$this->forumACL('view_forums', $theme->getId_forum()))
            return $this->showMessage(__('Permission denied'));
        if (!$this->forumACL('view_themes', $theme->getId_forum()))
            return $this->showMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
        if (!$this->forumACL('close_themes', $theme->getId_forum()))
            return $this->showMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));

        // Сначала заблокируем сообщения (посты) темы
        $posts = $postsModel->getCollection(array('id_theme' => $id_theme));
        if ($posts) {
            foreach ($posts as $post) {
                $post->setLocked('0');
                $post->save();
            }
        }

        // Теперь заблокируем тему
        $theme->setLocked('0');
        $theme->save();


        //clean cache
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
        if ($this->isLogging)
            \Logination::write('unlock theme', 'theme id(' . $id_theme . ')');
        return $this->showMessage(__('Theme is open'), $this->getModuleURL('view_forum/' . $theme->getId_forum()),'alert');
    }

    /**
     * Create reply form
     *
     * @param array $theme Theme info
     * @return string HTML reply form
     */
    private function add_post_form($theme = null) {

        if (empty($theme))
            return null;
        $id_theme = intval($theme->getId());
        if ($id_theme < 1)
            return null;
        $writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;

        if ($this->forumACL('add_posts', $theme->getId_forum())) {
            if ($theme->getLocked() == 1 && !$this->forumACL('close_themes',$theme->getId_forum())) {
                $html = '<div class="not-auth-mess locked">' . __('Theme is locked') . '</div>';
            } else {


                $message = '';
                $html = '';
                // Если при заполнении формы были допущены ошибки
                if (isset($_SESSION['addPostForm'])) {
                    $message = h($_SESSION['addPostForm']['message']);
                    unset($_SESSION['addPostForm']);
                }

                $max_attaches = \Config::read('max_attaches', $this->module);
                if (empty($max_attaches) || !is_numeric($max_attaches))
                    $max_attaches = 5;
                $source = $this->render('replyform.html', array(
                    'context' => array(
                        'action' => get_url($this->getModuleURL('add_post/' . $id_theme)),
                        'main_text' => $message,
                        'max_attaches' => $max_attaches,
                        'locked_attaches' => intval(\Config::read('locked_attaches', $this->module))
                    ),
                ));
                $html = $html . $source;
            }
        } else {
            if (isset($_SESSION['user']['name']))
                $html = '<div class="not-auth-mess access">' . __('Dont have permission to write post') . '</div>';
            else
                $html = '<div class="not-auth-mess guest">' . sprintf(__('Guests cant write posts'), get_url('/users/add_form/'), get_url('/users/login_form/')) . '</div>';
        }

        return $html;
    }

    /**
     * Adding new record into posts table
     *
     * @param int $id_theme
     */
    public function add_post($id_theme = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        if (empty($id_theme) || !isset($_POST['mainText']))
            return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);

        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'),getReferer(),'error', true);

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'),getReferer(),'error', true);



        // Проверяем, не заблокирована ли тема?
        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themesModel->getById($id_theme);
        if (!$theme)
            return $this->showMessage(__('Topic not found'),getReferer());

        // Check \ACL
        $this->forumACL('view_forums', $theme->getId_forum(),true);
        $this->forumACL('view_themes', $theme->getId_forum(),true);
        $this->forumACL('add_posts', $theme->getId_forum(),true);

        if ($theme->getLocked() == 1 && !$this->forumACL('close_themes',$theme->getId_forum()))
            return $this->showMessage(__('Can not write in a closed theme'),getReferer(),'error', true);



        // Check access to this forum. May be locked by pass or posts count
        $forum = $this->Model->getById($theme->getId_forum());
        if (!$forum)
            return $this->showMessage(__('Can not find forum'));
        $this->__checkForumAccess($forum);


        // Обрезаем сообщение (пост) до длины $set['forum']['max_post_lenght']
        $message = trim($_POST['mainText']);



        // Проверяем, правильно ли заполнены поля формы
        $error = '';
        if (empty($message))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Material body')) . '</li>' . "\n";
        if (strlen($message) > \Config::read('max_post_lenght', $this->module))
            $error .= '<li>' . sprintf(__('Very big "param"'), __('Material body'), \Config::read('max_post_lenght', $this->module)) . '</li>' . "\n";


        $gluing = true;
        $max_attaches = \Config::read('max_attaches', $this->module);
        if (empty($max_attaches) || !is_numeric($max_attaches)) $max_attaches = 5;
        for ($i = 1; $i < $max_attaches; $i++) {
            if (!empty($_FILES['attach' . $i]['name'])) {
                //if exists attach files we do not gluing posts
                $gluing = false;
            }
        }


        $message = mb_substr($message, 0, \Config::read('max_post_lenght', $this->module));
        $id_user = $_SESSION['user']['id'];
        // Защита от того, чтобы один пользователь не добавил сразу несколько сообщений
        if (isset($_SESSION['unix_last_post']) && (time() - $_SESSION['unix_last_post'] < 10)) {
            return $this->showMessage(__('Your message has been added'),getReferer(),'error', true);
        }


        // Проверка на валидность аттачей
        $out = checkAttaches($this->module);
        if ($out != null)
            $error .= $out;

        // errors
        if (!empty($error)) {
            $_SESSION['addPostForm'] = array();
            $_SESSION['addPostForm']['errors'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' . "\n" .
                    '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            $_SESSION['addPostForm']['message'] = $message;
            return $this->showMessage($_SESSION['addPostForm']['errors'], getReferer(),'error', true);
        }

        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        //gluing posts
        if ($gluing === true) {
            $prev_post = $postsModel->getFirst(array(
                'id_theme' => $id_theme,
            ), array(
                'order' => 'time DESC, id DESC',
            ));
            if ($prev_post) {
                if (strtotime($prev_post->getEdittime()) > strtotime($prev_post->getTime()))
                    $lasttime = strtotime($prev_post->getEdittime());
                else
                    $lasttime = strtotime($prev_post->getTime());
                $gluing = $lasttime > time() - \Config::read('raw_time_mess');
                $prev_post_author = $prev_post->getId_author();
                if (empty($prev_post_author))
                    $gluing = false;
                if ((mb_strlen($prev_post->getMessage() . $message)) > \Config::read('max_post_lenght', $this->module))
                    $gluing = false;
                if ($prev_post_author != $id_user || empty($id_user))
                    $gluing = false;
            } else {
                $gluing = false;
            }
        }



        if ($gluing === true) {
            $message = $prev_post->getMessage() . "\n\n" . sprintf(__('Added in time'),  DrsOffsetDate(strtotime($prev_post->getTime()))) . "\n\n" . $message;

            $prev_post->setMessage($message);
            $prev_post->setEdittime(new \Expr('NOW()'));
            $prev_post->save();

            $theme->setId_last_author($id_user);
            $theme->setLast_post(new \Expr('NOW()'));
            $theme->save();

            $forum->setLast_theme_id($id_theme);
            $forum->save();
        } else {   // NOT GLUING MESSAGE
            // Все поля заполнены правильно - выполняем запрос к БД
            $post_data = array(
                'message' => $message,
                'id_author' => $id_user,
                'time' => new \Expr('NOW()'),
                'edittime' => new \Expr('NOW()'),
                'id_theme' => $id_theme,
                'id_forum' => $theme->getId_forum()
            );
            $post = new \ForumModule\ORM\ForumPostsEntity($post_data);
            $post_id = $post->save();


            /*       * *** ATTACH **** */
            $locked_attaches = intval(\Config::read('locked_attaches', $this->module));
            $post->setLocked_attaches($locked_attaches);
            if (!$locked_attaches and \ACL::turnUser(array($this->module, 'upload_files'))) {

                /* delete collizions if exists */
                $this->deleteCollizions($post, true);

                if (downloadAttaches($this->module, $post_id) == null) {
                    $post = $postsModel->getById($post_id);
                    if ($post) {
                        $post->setAttaches('1');
                        $post->save();
                    }
                }
            }
            /*       * *** END ATTACH **** */


            $cnt_posts_from_theme = $theme->getPosts()+1;
            $theme->setPosts($cnt_posts_from_theme);
            $theme->setId_last_author($id_user);
            $theme->setLast_post(new \Expr('NOW()'));
            $theme->save();

            // Обновляем количество сообщений для зарегистрированного пользователя
            if (isset($_SESSION['user']['name'])) {
                $usersModel = \OrmManager::getModelInstance('Users');
                $user = $usersModel->getById($id_user);
                if ($user) {
                    $user->setPosts($user->getPosts() + 1);
                    $user->save();
                }
            }


            //update forum info
            $forum->setPosts($forum->getPosts() + 1);
            $forum->setLast_theme_id($id_theme);
            $forum->save();
        }

        // speed spam protected
        $_SESSION['unix_last_post'] = time();

        //clean cache
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme, 'user_id_' . $id_user));

        $near_pages = \Config::read('posts_per_page', $this->module);
        $page = 0;
        $cnt_posts_from_theme = $theme->getPosts()+1;
        if ($cnt_posts_from_theme > $near_pages)
            $page = ceil($cnt_posts_from_theme / $near_pages);

        if ($gluing === false) {
            if ($this->isLogging)
                \Logination::write('adding post', 'post id(' . $post_id . '), theme id(' . $id_theme . ')');
            return $this->showMessage(__('Your message has been added'), $this->getModuleURL(
                                    'view_theme/' . $id_theme . ($page ? '/?page=' . $page : '') . '#post' . $cnt_posts_from_theme),'ok');
        } else {
            $id_last_post = $prev_post->getId_last_post();
            if ($this->isLogging)
                \Logination::write('adding post', 'post id('.$id_last_post.'*gluing), theme id(' . $id_theme . ')');
            return $this->showMessage(__('Your message has been added'), $this->getModuleURL(
                                    'view_theme/' . $id_theme . ($page ? '/?page=' . $page : '') . '#post' . $cnt_posts_from_theme),'ok');
        }
    }

    /**
     * Create Edit post form
     *
     * @param int $id Post ID
     */
    public function edit_post_form($id = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));

        $id = (int)$id;
        if ($id < 1)
            return $this->showMessage(__('Post not found'), getReferer(),'error', true);
        $writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;

        $this->page_title = __('Edit message');

        // Получаем из БД сообщение
        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $post = $postsModel->getById($id);
        if (!$post)
            return $this->showMessage(__('Some error occurred'), getReferer(),'error', true);

        // Check acl
        $this->forumACL('view_forums', $post->getId_forum(),true);
        $this->forumACL('view_themes', $post->getId_forum(),true);

        $id_theme = $post->getId_theme();

        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themesModel->getById($id_theme);
        if (!$theme)
            return $this->showMessage(__('Topic not found'), getReferer(),'error', true);

        if (strtotime($post->getEdittime()) > strtotime($post->getTime()))
            $lasttime = strtotime($post->getEdittime());
        else
            $lasttime = strtotime($post->getTime());
        $raw_time_mess = $lasttime - time() + \Config::read('raw_time_mess');
        if ($raw_time_mess <= 0 or ($theme->getLocked() != 0)) $raw_time_mess = false;
        $first = $postsModel->getFirst(array(
            'id_theme' => $id_theme,
        ), array(
            'order' => 'id ASC'
        ));
        if ($first->getId() == $post->getId())
            $post_num = 1;
        else
            $post_num = 0;
        //check access
        if  (!(
                (
                    !empty($_SESSION['user']['name']) &&
                    $theme->getId_forum()
                ) &&
                (
                    (
                        $this->forumACL('edit_posts', $theme->getId_forum()) or
                        (
                            !empty($_SESSION['user']['id']) &&
                            $post->getId_author() == $_SESSION['user']['id'] &&
                            $this->forumACL('edit_mine_posts',$theme->getId_forum()) &&
                            (
                                \Config::read('raw_time_mess') == 0 or
                                $raw_time_mess
                            ) &&
                            $theme->getLocked() == 0
                        )
                    ) or
                    (
                        $post_num === 1 &&
                        (
                            $this->forumACL('edit_themes',$theme->getId_forum()) or
                            (
                                !empty($_SESSION['user']['id']) &&
                                $theme->getId_author() == $_SESSION['user']['id'] &&
                                $this->forumACL('edit_mine_themes',$theme->getId_forum())
                            )
                        )
                    )
                )
            ))
        {
            return $this->showMessage(__('Permission denied'), $this->getModuleURL('view_theme/' . $id_theme));
        }


        $message = $post->getMessage();
        $add_editor = '1';
        $html = '';
        $markers = array();
        // errors
        if (isset($_SESSION['editPostForm'])) {
            $message = $_SESSION['editPostForm']['message'];
            $add_editor = !empty($_SESSION['editPostForm']['add_editor']) ? '1' : '0';
            unset($_SESSION['editPostForm']);
        }



        $markers = array(
            'action' => get_url($this->getModuleURL('update_post/' . $id)),
            'main_text' => h($message),
            'add_editor' => (!empty($add_editor) ? $add_editor : ''),// Отредактировано ли было сообщение
        );


        /*         * **  ATTACH  *** */

        $max_attaches = \Config::read('max_attaches', $this->module);
        if (empty($max_attaches) || !is_numeric($max_attaches))
            $max_attaches = 5;
        $markers['max_attaches'] = $max_attaches;
        $markers['locked_attaches'] = intval(\Config::read('locked_attaches', $this->module));

        $unlinkfiles = array();
        $attaches = array();
        if (!$markers['locked_attaches'] and $post->getAttaches()) {
            $attachModel = \OrmManager::getModelInstance('ForumAttaches');
            $attach_list = $attachModel->getCollection(array('post_id' => $post->getId()));
            if ($attach_list) {
                foreach ($attach_list as $attach) {
                    if (($attach->getIs_image() and file_exists(ROOT . $this->getImagesPath($attach->getFilename()))) or 
                        (file_exists(ROOT . $this->getFilesPath($attach->getFilename())))) {
                                
                        $unlinkfiles['att' . $attach->getAttach_number()] = '<input type="checkbox" name="unlink' . $attach->getAttach_number()
                                . '" value="1" />' . __('Delete') . "\n";
                        $attaches[] = array(
                            'id' => $attach->getAttach_number(),
                            'name' => substr($attach->getFilename(), strpos($attach->getFilename(), '_', 0)+1),
                            'url' => get_url($attach->getIs_image() ? $this->getImagesPath($attach->getFilename()) : $this->getFilesPath($attach->getFilename())),
                            'url_small' => ($attach->getIs_image()) ? $this->markerSmallImageAttach($attach->getFilename(), $attach->getAttach_number()) : get_url($this->getFilesPath($attach->getFilename())),
                            'date' => $attach->getDate(),
                            'size' => getSimpleFileSize($attach->getSize()),
                            'is_img' => $attach->getIs_image()
                        );
                    }
                }
            }
        }
        $markers['unlinkfiles'] = $unlinkfiles;
        $markers['attaches_list'] = array_slice($attaches,0);
        /*         * **  END  ATTACH  *** */


        // nav block
        $navi = array();
        $navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(__('forum'), $this->getModuleURL()) . __('Separator')
                . get_link($theme->getTitle(), $this->getModuleURL('view_theme/' . $id_theme)) . __('Separator') . __('Edit message');
        $this->_globalize($navi);


        // setReferer();
        $source = $this->render('editpostform.html', array('context' => $markers));
        $html = $html . $source;
        return $this->_view($html);
    }

    /**
     * Update Post record
     *
     * @param int $id Post ID
     */
    public function update_post($id = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        // Если не переданы данные формы - значит функция была вызвана по ошибке
        if (empty($id) || !isset($_POST['mainText']))
            return $this->showMessage(__('Some error occurred'));
        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));

        $id = (int)$id;
        if ($id < 1)
            return $this->showMessage(__('Material not found'));


        // Проверяем, имеет ли пользователь право редактировать это сообщение (пост)
        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $post = $postsModel->getById($id);
        if (!$post)
            return $this->showMessage(__('Some error occurred'));
        $id_theme = $post->getId_theme();

        $this->forumACL('view_forums',$post->getId_forum(),true);
        $this->forumACL('view_themes',$post->getId_forum(),true);

        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themesModel->getById($id_theme);
        if (!$theme)
            return $this->showMessage(__('Topic not found'));


        if (strtotime($post->getEdittime()) > strtotime($post->getTime()))
            $lasttime = strtotime($post->getEdittime());
        else
            $lasttime = strtotime($post->getTime());
        $raw_time_mess = $lasttime - time() + \Config::read('raw_time_mess');
        if ($raw_time_mess <= 0 or ($theme->getLocked() != 0)) $raw_time_mess = false;

        // Узнаем каким по счету является редактируемый пост в своей теме
        $post_num = $postsModel->getTotal(
            array(
                'order' => 'id ASC',
                'cond' => array(
                    'id_theme' => $id_theme,
                    '((time = \'' . $post->getTime() . '\' AND id < ' . $id . ') OR time < \'' . $post->getTime() . '\')',
                ),
            )
        );
        // Вычисляем на какой странице находится пост
        $near_pages = \Config::read('posts_per_page', $this->module);
        $page = 0;
        $post_num++;
        if ($post_num > $near_pages)
            $page = ceil($post_num / $near_pages);

        //check access
        if  (!(
                (
                    !empty($_SESSION['user']['name']) &&
                    $theme->getId_forum()
                ) &&
                (
                    (
                        $this->forumACL('edit_posts',$theme->getId_forum()) or
                        (
                            !empty($_SESSION['user']['id']) &&
                            $post->getId_author() == $_SESSION['user']['id'] &&
                            $this->forumACL('edit_mine_posts',$theme->getId_forum()) &&
                            (
                                \Config::read('raw_time_mess') == 0 or
                                $raw_time_mess
                            ) &&
                            $theme->getLocked() == 0
                        )
                    ) or
                    (
                        $post_num === 1 &&
                        (
                            $this->forumACL('edit_themes', $theme->getId_forum()) or
                            (
                                !empty($_SESSION['user']['id']) &&
                                $theme->getId_author() == $_SESSION['user']['id'] &&
                                $this->forumACL('edit_mine_themes', $theme->getId_forum())
                            )
                        )
                    )
                )
            ))
        {
            return $this->showMessage(__('Permission denied'),getReferer(),'error', true);
        }

        // Обрезаем сообщение до длины $set['forum']['max_post_lenght']
        $message = trim($_POST['mainText']);
        $add_editor = isset($_POST['add_editor']) ? '1' : '0';



        // check fields...
        $error = '';
        if (empty($message))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Message')) . '</li>' . "\n";
        if (mb_strlen($message) > \Config::read('max_post_lenght', $this->module))
            $error .= '<li>' . sprintf(__('Very big "param"'), __('Message'), \Config::read('max_post_lenght', $this->module)) . '</li>' . "\n";


        // Проверка на валидность аттачей
        $out = checkAttaches($this->module);
        if ($out != null)
            $error .= $out;
        /* if an error */
        if (!empty($error)) {
            $_SESSION['editPostForm'] = array();
            $_SESSION['editPostForm']['errors'] = '<p class="errorMsg">' . __('Some error in form')
                    . '</p>' . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            $_SESSION['editPostForm']['message'] = $message;
            return $this->showMessage($_SESSION['editPostForm']['errors'], $this->getModuleURL('view_theme/' . $id_theme . ($page ? '/?page=' . $page : '') . '#post' . $post_num), 'error');
        }

        $user_id = $_SESSION['user']['id'];

        /*         * ***   ATTACH   **** */
        if (\ACL::turnUser(array($this->module, 'upload_files'))) {

            downloadAttaches($this->module, $id, true);

            $attachModel = \OrmManager::getModelInstance('ForumAttaches');
            $attach_exists = $attachModel->getCollection(array('post_id' => $id));
            $attach_exists = ($attach_exists > 0) ? '1' : '0';
            $post->setAttaches($attach_exists);
            //$this->deleteCollizions($post);
        }
        /*       * ***  END ATTACH   **** */


        // Все поля заполнены правильно - выполняем запрос к БД
        $message = mb_substr($message, 0, \Config::read('max_post_lenght', $this->module));
        $post->setMessage($message);
        if (!$this->forumACL('edit_posts', $theme->getId_forum()) || $add_editor) {
            $post->setId_editor($user_id);
            $post->setEdittime(new \Expr('NOW()'));
        }
        $post->save();

        //clean cache
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('post_id_' . $id));
        if ($this->isLogging)
            \Logination::write('editing post', 'post id(' . $id . '), theme id(' . $id_theme . ')');
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL('view_theme/' . $id_theme . ($page ? '/?page=' . $page : '') . '#post' . $post_num), 'ok');


    }


    /**
     * deleting post from forum
     * @id     post ID
     * @return none
     */
    public function delete_post($id = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));

        $id = (int)$id;
        if ($id < 1)
            return $this->showMessage(__('Material not found'),getReferer(),'error', true);


        // Получаем из БД информацию об удаляемом сообщении - это нужно,
        // чтобы узнать, имеет ли право пользователь удалить это сообщение
        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $post = $postsModel->getById($id);
        if (!$post)
            return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);

        $this->forumACL('view_forums',$post->getId_forum(),true);
        $this->forumACL('view_themes',$post->getId_forum(),true);

        if ($post->getId_author()) {
            $usersModel = \OrmManager::getModelInstance('Users');
            $user = $usersModel->getById($post->getId_author());
        }


        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themesModel->getById($post->getId_theme());

        if (strtotime($post->getEdittime()) > strtotime($post->getTime()))
            $lasttime = strtotime($post->getEdittime());
        else
            $lasttime = strtotime($post->getTime());
        $raw_time_mess = $lasttime - time() + \Config::read('raw_time_mess');
        if ($raw_time_mess <= 0 or ($theme->getLocked() != 0)) $raw_time_mess = false;
        $first = $postsModel->getFirst(array(
            'id_theme' => $theme->getId(),
        ), array(
            'order' => 'id ASC'
        ));
        if ($first->getId() == $post->getId())
            $post_num = 1;
        else
            $post_num = 0;
        //check access
        if  (!(
                $post_num !== 1 &&
                (
                    $this->forumACL('delete_posts',$theme->getId_forum()) or
                    (
                        !empty($_SESSION['user']['id']) &&
                        $post->getId_author() == $_SESSION['user']['id'] &&
                        $this->forumACL('delete_mine_posts',$theme->getId_forum()) &&
                        (
                            \Config::read('raw_time_mess') == 0 or
                            $raw_time_mess
                        ) &&
                        $theme->getLocked() == 0
                    )
                )
            ))
        {
            return $this->showMessage(__('Permission denied'));
        }




        // Удаляем файл, если он есть
        $attachModel = \OrmManager::getModelInstance('ForumAttaches');
        if ($post->getAttaches()) {
            $attach_list = $attachModel->getCollection(array('post_id' => $id));
            if (count($attach_list) && is_array($attach_list)) {
                foreach ($attach_list as $attach) {
                    if ($attach->getIs_image() and file_exists(ROOT . $this->getImagesPath($attach->getFilename()))) {
                        if (@unlink(ROOT . $this->getImagesPath($attach->getFilename()))) {
                            $attach->delete();
                        }
                    } elseif (file_exists(ROOT . $this->getFilesPath($attach->getFilename()))) {
                        if (@unlink(ROOT . $this->getFilesPath($attach->getFilename()))) {
                            $attach->delete();
                        }
                    }
                }
            }
        }
        $post->delete();

        // Если это - единственное сообщение темы, то надо удалить и тему
        $postscnt = $postsModel->getTotal(array('cond' => array('id_theme' => $post->getId_theme())));


        $deleteTheme = false;
        if ($postscnt == 0) {
            if ($user) {
                // Прежде чем удалять тему, надо обновить таблицу TABLE_USERS
                $user->setThemes($user->getThemes() - 1);
                $user->save();
            }

            if ($theme) {
                $theme->delete();
            }
            // Если мы удалили тему, то мы не можем в нее вернуться;
            // поэтому редирект будет на страницу форума, а не страницу темы
            $deleteTheme = true;
        }


        if ($user) {
            // Обновляем количество сообщений, оставленных автором сообщения ...
            $user->setPosts($user->getPosts() - 1);
            $user->save();
        }


        // ... и таблицу .themes
        if (!$deleteTheme) {
            $last_post = $postsModel->getFirst(array(
                'id_theme' => $post->getId_theme(),
            ), array(
                'order' => 'id DESC'
            ));

            if ($theme) {
                if ($last_post) {
                    $theme->setId_last_author($last_post->getId_author());
                    $theme->setLast_post($last_post->getTime());
                }
                $theme->setPosts($postscnt - 1);
                $theme->save();
            }
        }

        //clean cache
        $cahceKey = array('post_id_' . $id);
        if (isset($deleteTheme))
            $cahceKey[] = 'theme_id_' . $post->getId_theme();
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, $cahceKey);
        if ($this->isLogging)
            \Logination::write('delete post', 'post id(' . $id . '), theme id(' . $post->getId_theme() . ')');


        //update forum info
        $last_theme = $themesModel->getFirst(array(
            'id_forum' => $theme->getId_forum(),
        ), array(
            'order' => '`last_post` DESC',
        ));

        $forum = $this->Model->getById($theme->getId_forum());
        if ($deleteTheme) {
            if ($forum) {
                $forum->setThemes($forum->getThemes() - 1);
                $forum->setLast_theme_id($last_theme ? $last_theme->getId() : '0');
                $forum->save();
            }
            return $this->showMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $theme->getId_forum()),'ok');
        } else {
            if ($forum) {
                $forum->setPosts($forum->getPosts() - 1);
                $forum->setLast_theme_id($last_theme ? $last_theme->getId() : '0');
                $forum->save();
            }
            return $this->showMessage(__('Operation is successful'), $this->getModuleURL('view_theme/' . $theme->getId()),'alert');
        }
    }

    /**
     * View themes for users
     *
     * @param ind $user_id
     * @return none
     */
    public function user_themes($user_id = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        if (!is_numeric($user_id))
            return $this->showMessage(__('Value must be numeric'));

        $user_id = (int)$user_id;
        if ($user_id < 1)
            return $this->showMessage(__('Can not find user'));

        $this->page_title = __('User themes');
        $html = '';

        if ($this->cached && $this->Cache->check($this->cacheKey)) {
            $html = $this->Cache->read($this->cacheKey);
            return $this->_view($html);
        }

        $usersModel = \OrmManager::getModelInstance('Users');
        $user = $usersModel->getById($user_id);
        if (!$user)
            return $this->showMessage(__('Can not find user'));


        // Заголовок страницы (содержимое тега title)
        $this->page_title .= ' "' . h($user->getName()) . '"';


        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $total = $themesModel->getTotal(array('cond' => array('id_author' => $user_id)));
        $perPage = intval(\Config::read('themes_per_page', $this->module));
        if ($perPage < 1)
            $perPage = 10;
        list($pages, $page) = pagination($total, $perPage, $this->getModuleURL('user_themes/' . $user_id));


        // Page nav
        $nav = array();
        $nav['pagination'] = $pages;
        //$this->page_title .= ' (' . $page . ')';


        $cntPages = ceil($total / $perPage);
        $recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
        $firstOnPage = ($page - 1) * $perPage + 1;
        $lastOnPage = $firstOnPage + $recOnPage - 1;

        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(__('forum'), $this->getModuleURL()) . __('Separator') . __('User themes') . ' "' . h($user->getName()) . '"';
        $nav['meta'] = __('Count all topics') . ' ' . $total . '. ' . ($total > 1 ? __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage : '');
        $this->_globalize($nav);


        //get records
        $themesModel->bindModel('author');
        $themesModel->bindModel('last_author');
        $themesModel->bindModel('postslist');
        $themesModel->bindModel('forum');
        $order = getOrderParam(__CLASS__);
        $themes = $themesModel->getCollection(array(
            'id_author' => $user_id,
        ), array(
            'order' => (empty($order) ? 'time DESC' : $order),
            'group' => 'id',
            'page' => $page,
            'limit' => $perPage,
        ));


        foreach ($themes as $theme) {
            if ($theme) {
                if (!$this->forumACL('view_forums',$theme->getId_forum())) continue;
                if (!$this->forumACL('view_themes',$theme->getId_forum())) continue;

                $parent_forum = $theme->getForum() ? get_link($theme->getForum()->getTitle()
                                , $this->getModuleURL('view_forum/' . $theme->getId_forum())) : '';
                $theme->setParent_forum($parent_forum);
                $theme = $this->__parseThemeTable($theme);


                //set cache tags
                $this->setCacheTag(array(
                    'theme_id_' . $theme->getId(),
                ));
            }
        }


        // write into cache
        if ($this->cached) {
            $this->Cache->write($html, $this->cacheKey, $this->cacheTags);
        }

        //pr($themes); die();
        $source = $this->render('lastposts_list.html', array(
            'context' => array(
                'forum_name' => __('User themes') . ' "' . h($user->getName()) . '"',
            ),
            'themes' => $themes
            )
        );
        $this->_view($source);
    }

    /**
     * @return forum statistic block
     */
    protected function _get_stat() {
        $markers = array();
        $result = $this->Model->getStats();


        if (!empty($result[0]['last_user_id']) && !empty($result[0]['last_user_name'])) {
            $markers['new_user'] = get_link(h($result[0]['last_user_name']), getProfileUrl($result[0]['last_user_id'], true));
        }
        $markers['count_users'] = getAllUsersCount();
        $markers['count_posts'] = (!empty($result[0]['posts_cnt'])) ? $result[0]['posts_cnt'] : 0;
        $markers['count_themes'] = (!empty($result[0]['themes_cnt'])) ? $result[0]['themes_cnt'] : 0;


        $html = $this->render('get_stat.html', $markers);
        return $html;
    }

    public function download_file($file = null, $mimetype = 'application/octet-stream') {
        $out = user_download_file($this->module, $file, $mimetype);
        if ($out != null)
            return $this->showMessage($out, $this->getModuleURL());
    }

    public function important($id = null) {
        //turn access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));

        $id = (int)$id;
        if ($id < 1)
            return $this->showMessage(__('Material not found'));


        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themesModel->getById($id);
        if (!$theme)
            return $this->showMessage(__('Topic not found'));

        $this->forumACL('important_themes',$theme->getId_forum(),true);

        $theme->setImportant('1');
        $theme->save();

        if ($this->isLogging)
            \Logination::write('important post', 'theme id(' . $id . ')');
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $theme->getId_forum()),'alert');
    }

    public function unimportant($id = null) {
        //turn access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));

        $id = (int)$id;
        if ($id < 1)
            return $this->showMessage(__('Material not found'));


        $themesModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themesModel->getById($id);
        if (!$theme)
            return $this->showMessage(__('Topic not found'));

        $this->forumACL('important_themes',$theme->getId_forum(),true);

        $theme->setImportant('0');
        $theme->save();

        if ($this->isLogging)
            \Logination::write('unimportant post', 'theme id(' . $id . ')');
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $theme->getId_forum()),'alert');
    }

    /**
     * deleting attaches  collizion
     *
     * @post (array)   reply data
     * @clean(boolean) clean all or only collizions
     * @return         none
     */
    private function deleteCollizions($post, $clean = false) {
        /* DB has file */
        $attachModel = \OrmManager::getModelInstance('ForumAttaches');
        $attachments = $attachModel->getCollection(array('post_id' => $post->getId()));
        if ($clean === true) {
            if (count($attachments) && is_array($attachments))
                foreach ($attachments as $attach)
                    $attach->delete();
        } else {
            if (count($attachments) && is_array($attachments)) {
                foreach ($attachments as $key => $attach) {
                    if (($attach->getIs_image() and file_exists(ROOT . $this->getImagesPath($attach->getFilename()))) or
                        (file_exists(ROOT . $this->getFilesPath($attach->getFilename())))) {
                        clearstatcache();
                        continue;
                    }
                    $attach->delete();
                    unset($attachments[$key]);
                }
            }
        }


        /* File has DB record */
        $attach_files = array_merge(glob(ROOT . $this->getFilesPath($post->getId() . '-*')), glob(ROOT . $this->getImagesPath($post->getId() . '-*')));
        if (!empty($attach_files)) {
            foreach ($attach_files as $_key => $attach_file) {
                if ($clean === true) {
                    @unlink($attach_file);
                    continue;
                }
                $record_exists = false;
                if (count($attachments) && is_array($attachments)) {
                    foreach ($attachments as $attach) {
                        if (strrchr($attach_file, '/') == $attach->getFilename()) {
                            $record_exists = true;
                            break;
                        }
                    }
                }
                if ($record_exists === false) {
                    unset($attach_files[$_key]);
                    @unlink($attach_file);
                }
            }
        }
        if ($clean === true)
            return;
        /* posts.attaches flag */
        $flag = (!empty($attach_files) && !empty($attachments)) ? '1' : '0';
        if ($flag != $post->getAttaches()) {
            $post->setAttaches($flag);
            $post->save();
        }
        return;
    }

    //delete theme
    private function __delete_theme($theme) {
        $id_theme = $theme->getId();

        // Step 1: Deleting attached files
        $attachesModel = \OrmManager::getModelInstance('ForumAttaches');
        $attach_files = $attachesModel->getCollection(array('theme_id' => $id_theme));
        if (count($attach_files) && is_array($attach_files)) {
            foreach ($attach_files as $attach_file) {
                if (file_exists(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
                    if (@unlink(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
                        $attach_file->delete();
                    }
                }
            }
        }

        // Step 2: Selecting authors and deleting posts
        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $users = $postsModel->getCollection(array('id_theme' => $id_theme), array('fields' => array('DISTINCT id_author')));
        $postsModel->deleteByTheme($id_theme);

        // Step 3: Deleting poll
        $pollsModel = \OrmManager::getModelInstance('ForumPolls');
        $pollsModel->delete($id_theme);

        // Step 4: Deleting theme
        $theme->delete();

        // Step 5: Deleting collision
        $this->Model->deleteCollisions();

        // Step 6: Updating counters for forum
        $this->Model->updateForumCounters($theme->getId_forum());

        // Step 7: Updating counters for users
        if ($users && is_array($users)) {
            foreach ($users as $user) {
                if ($user) {
                    $this->Model->updateUserCounters($user->getId_author());
                }
            }
        }

        // Step 8: Cleaning cache
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme,));
        $this->Cache->clean(CACHE_MATCHING_TAG, array('module_forum', 'action_index'));
    }

    public function view_post($id_post = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        if (!is_numeric($id_post))
            return $this->showMessage(__('Value must be numeric'));

        $id_post = (int)$id_post;
        if ($id_post < 1)
            return $this->showMessage(__('Material not found'), $this->getModuleURL());

        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $post = $postsModel->getById($id_post);
        if (!$post)
            return $this->showMessage(__('Some error occurred'), $this->getModuleURL());


        $this->forumACL('view_forums',$post->getId_forum(),true);
        $this->forumACL('view_themes',$post->getId_forum(),true);

        $id_theme = $post->getId_theme();
        $post_num = $postsModel->getTotal(
                array(
                    'order' => 'id ASC',
                    'cond' => array(
                        'id_theme' => $id_theme,
                        '((time = \'' . $post->getTime() . '\' AND id < ' . $id_post . ') OR time < \'' . $post->getTime() . '\')',
                    ),
                )
        );

        $page = floor($post_num / \Config::read('posts_per_page', $this->module)) + 1;
        $post_num++;

        /* Мост для GET запросов. */
        $gets = '';
        if (isset($_GET) && is_array($_GET)) {
            $gets = ($page ? '&' : '?');
            $getqueryes = array();
            foreach($_GET as $key => $value)
                if ($key != 'url')
                    $getqueryes[] = $key.'='.$value;

            $gets .= implode('&',$getqueryes);
        }

        redirect($this->getModuleURL('view_theme/' . $id_theme . ($page ? '?page='.$page.$gets : $gets) . ($post_num ? '#post'.$post_num : '')));
    }

    public function split_theme_form($id_theme = null) {
        //turn access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'), $this->getModuleURL());



        $themeModel = \OrmManager::getModelInstance('ForumThemes');
        $themeModel->bindModel('forum');
        $theme = $themeModel->getById($id_theme);
        if (empty($theme) || !$theme->getForum())
            return $this->showMessage(__('Topic not found'), $this->getModuleURL());


        //turn access
        $this->forumACL('view_forums',$theme->getId_forum(),true);
        $this->forumACL('view_themes',$theme->getId_forum(),true);
        $this->forumACL('add_themes', $theme->getId_forum(),true);
        $this->forumACL('edit_themes', $theme->getId_forum(),true);


        // Check access to this forum. May be locked by pass or posts count
        $this->__checkForumAccess($theme->getForum());
        $id_forum = $theme->getId_forum();

        $this->__checkThemeAccess($theme);


        $html = '';
        // Если при заполнении формы были допущены ошибки
        if (isset($_SESSION['editThemeForm'])) {
            $name = h($_SESSION['editThemeForm']['theme']);
            $desc = h($_SESSION['editThemeForm']['description']);
            $gr_access = $_SESSION['editThemeForm']['gr_access'];
            $posts_select = $_SESSION['editThemeForm']['posts_select'];
            $first_top = $_SESSION['editThemeForm']['first_top'];
            $locked = $_SESSION['editThemeForm']['locked'];
            unset($_SESSION['editThemeForm']);
        } else {
            $name = '';
            $desc = '';
            $gr_access = array();
            $posts_select = array();
            $first_top = '';
            $locked = '';
        }


        // Формируем список форумов, чтобы можно было переместить тему в другой форум
        $forums = $this->Model->getCollection(array(), array('order' => 'pos'));
        if (!$forums)
            return $this->showMessage(__('Can not find forum'), $this->getModuleURL("view_theme/$id_theme"));


        $options = '';
        foreach ($forums as $forum) {
            if (
                $this->forumACL('view_forums', $forum->getId()) &&
                $this->forumACL('view_themes', $forum->getId()) &&
                $this->forumACL('add_themes', $forum->getId()) &&
                !$forum->getLock_passwd() &&
                (!$forum->getLock_posts() || $forum->getLock_posts() >= $forum->getPosts())
            ) {
                if ($forum->getId() == $theme->getId_forum())
                    $options .= '<option value="' . $forum->getId() . '" selected>' . h($forum->getTitle()) . '</option>' . "\n";
                else
                    $options .= '<option value="' . $forum->getId() . '">' . h($forum->getTitle()) . '</option>' . "\n";
            }
        }


        // Заголовок страницы (содержимое тега title)
        $this->page_title = __('Split theme') . ' - ' . h($theme->getTitle());


        $markers = array();
        $markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
            . get_link(__('forum'), $this->getModuleURL()) . __('Separator')
            . get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $id_forum)) . __('Separator')
            . get_link($theme->getTitle(), $this->getModuleURL('view_theme/' . $id_theme)) . __('Separator') . __('Split theme');


        // Page nav
        $postsModel = \OrmManager::getModelInstance('ForumPosts');

        $where = array('id_theme' => $id_theme);
        $first_post = $postsModel->getFirst(array(
            'id_theme' => $id_theme,
        ), array(
            'order' => 'time ASC, id ASC',
        ));
        if ($first_post) {
            $where[] = 'id != ' . $first_post->getId();
        }

        $posts_per_page = 100; // \Config::read('posts_per_page', $this->module);
        $total = $postsModel->getTotal(array('cond' => $where));
        if ($total < 1)
            return $this->showMessage(__('Not enough posts'), $this->getModuleURL('view_theme/' . $id_theme));
        list($pages, $page) = pagination($total, $posts_per_page, $this->getModuleURL('split_theme_form/' . $id_theme));
        $markers['pagination'] = $pages;
        //$this->page_title .= ' (' . $page . ')';

        // SELECT posts
        $posts = $postsModel->getCollection($where, array(
            'order' => 'time ASC, id ASC',
            'page' => $page,
            'limit' => $posts_per_page,
        ));


        $markers['meta'] = '';
        $this->_globalize($markers);


        $usersModel = \OrmManager::getModelInstance('Users');
        foreach ($posts as $post) {
            $postAuthor = $usersModel->getById($post->getId_author());
            $post->setAuthor_name($postAuthor ? $postAuthor->getName() : __('Guest'));
            $author_status = ($postAuthor) ? $postAuthor->getStatus() : 0;
            $message = \PrintText::print_page($post->getMessage(), $author_status);
            $post->setMessage($message);
        }


        $data = array(
            'action' => get_url($this->getModuleURL('split_theme/' . $id_theme . '?page=' . $page)),
            'theme' => $name,
            'description' => $desc,
            'options' => $options,
            'posts_select' => (!empty($posts_select)) ? $posts_select : array(),
            'gr_access' => (!empty($gr_access)) ? $gr_access : array(),
            'first_top' => (!empty($first_top)) ? $first_top : '0',
            'locked' => (!empty($locked)) ? $locked : '0',
        );

        $data['gr_access'] = array();
        $data['locked'] = '0';

        $source = $this->render('splitthemeform.html', array(
            'posts' => $posts,
            'theme' => $theme,
            'context' => $data,
        ));

        return $this->_view($html . $source);
    }

    public function split_theme($id_theme = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        // Если не переданы данные формы - функция вызвана по ошибке
        if (!isset($id_theme) || !isset($_POST['id_forum']) || !isset($_POST['theme']))
            return $this->showMessage(__('Some error occurred'));
        if (!is_numeric($id_theme) || !is_numeric($_POST['id_forum']))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        $id_forum = (int)$_POST['id_forum'];
        if ($id_theme < 1 || $id_forum < 1)
            return $this->showMessage(__('Can not find forum'));


        $themeModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themeModel->getById($id_theme);
        if (empty($theme))
            return $this->showMessage(__('Topic not found'));

        //turn access
        $this->forumACL('view_forums',$theme->getId_forum(),true);
        $this->forumACL('view_themes',$theme->getId_forum(),true);

        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $id_from_forum = $theme->getId_forum();
        $name = trim(mb_substr($_POST['theme'], 0, 55));
        $description = trim(mb_substr($_POST['description'], 0, 128));
        $first_top = isset($_POST['first_top']) ? '1' : '0';
        $locked = isset($_POST['locked']) ? '1' : '0';

        $gr_access = array();
        $groups = \ACL::getGroups();
        foreach ($groups as $grid => $grval) {
            if (isset($_POST['gr_access_' . $grid]))
                $gr_access[] = $grid;
        }
        $posts_select = array();
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'post_') === 0) {
                $number = substr($key, strlen('post_'));
                if (!empty($number))
                    $posts_select[] = intval($number);
            }
        }
        $posts_select = array_unique($posts_select, SORT_NUMERIC);


        // validate ...
        $error = '';
        
        if (empty($name))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Theme title')) . '</li>' . "\n";
        elseif (!\Validate::cha_val($name, V_TITLE))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Theme title')) . '</li>' . "\n";
        if (!empty($description) and !\Validate::cha_val($description, V_TITLE))
            $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Description')) . '</li>' . "\n";
        if (empty($posts_select))
            $error .= '<li>' . __('Empty "posts_select"') . '</li>' . "\n";

        // errors
        if (!empty($error)) {
            $_SESSION['editThemeForm'] = array();
            $_SESSION['editThemeForm']['errors'] = '<p class="errorMsg">' . __('Some error in form')
                    . '</p>' . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            $_SESSION['editThemeForm']['theme'] = $name;
            $_SESSION['editThemeForm']['description'] = $description;
            $_SESSION['editThemeForm']['gr_access'] = $gr_access;
            $_SESSION['editThemeForm']['posts_select'] = $posts_select;
            $_SESSION['editThemeForm']['first_top'] = $first_top;
            $_SESSION['editThemeForm']['locked'] = $locked;
            return $this->showMessage($_SESSION['editThemeForm']['errors'], $this->getModuleURL("split_theme_form/$id_theme"));
        }


        //check access
        if (!$this->forumACL('add_themes',$theme->getId_forum()) ||
                !$this->forumACL('edit_themes',$theme->getId_forum())) {
            return $this->showMessage(__('Permission denied'),getReferer(),'error', true);
        }

        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $first_post = $postsModel->getById(min($posts_select));
        if (!$first_post)
            return $this->showMessage(__('Some error occurred'));
        $last_post = $postsModel->getById(max($posts_select));
        if (!$last_post)
            return $this->showMessage(__('Some error occurred'));


        // new theme
        $data = array(
            'title' => $name,
            'description' => $description,
            'id_author' => $first_post->getId_author(),
            'time' => $first_post->getTime(),
            'id_last_author' => $last_post->getId_author(),
            'last_post' => $last_post->getTime(),
            'id_forum' => $id_forum,
            'posts' => count($posts_select) > 0 ? count($posts_select) - 1 : 0,
            'first_top' => $first_top,
        );



        $data['group_access'] = array();
        if ($this->forumACL('set_access_themes',$id_forum) || (
            $user_id == $_SESSION['user']['id'] && $this->forumACL('set_access_mine_themes',$id_forum))
        ) {
            $data['group_access'] = $gr_access;
        }
        $data['locked'] = '0';
        if ($this->forumACL('close_themes',$id_forum)) {
            $data['locked'] = $locked;
        }

        $new_theme = new \ForumModule\ORM\ForumThemesEntity($data);
        $id_new_theme = $new_theme->save();
        if ($id_new_theme == 0)
            $this->showMessage(__('Some error occurred'));

        $postsModel->moveToTheme($id_new_theme, $posts_select);


        $new_last_post = $postsModel->getFirst(array(
            'id_theme' => $theme->getId(),
        ), array(
            'order' => 'id DESC'
        ));

        // update theme
        if ($new_last_post) {
            $theme->setId_last_author($new_last_post->getId_author());
            $theme->setLast_post($new_last_post->getTime());
        }
        $theme->setPosts($theme->getPosts() > count($posts_select) ? $theme->getPosts() - count($posts_select) : 0);
        $theme->save();

        //update forums info
        $new_forum = $this->Model->getById($id_forum);
        if (!$new_forum)
            return $this->showMessage(__('No forum for moving'));

        if ($id_from_forum != $id_forum) {
            $from_forum = $this->Model->getById($id_from_forum);
            if ($from_forum) {
                $from_forum->setPosts($from_forum->getPosts() > count($posts_select) ? $from_forum->getPosts() - count($posts_select) : 0);
                $from_forum->save();
            }

            $new_forum->setPosts($new_forum->getPosts() + count($posts_select) - 1);
            $new_forum->setThemes($new_forum->getThemes() + 1);
            $new_forum->save();
        } else {
            $new_forum->setPosts($new_forum->getPosts() - 1);
            $new_forum->setThemes($new_forum->getThemes() + 1);
            $new_forum->save();
        }

        $this->Model->upLastPost($id_from_forum, $id_forum);


        //clean cahce
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
        if ($this->isLogging)
            \Logination::write('split theme', 'theme id(' . $id_theme . ')');
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL('view_theme/' . $id_new_theme),'ok');
    }

    public function move_posts_form($id_theme = null) {
        //turn access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'), $this->getModuleURL());



        $themeModel = \OrmManager::getModelInstance('ForumThemes');
        $themeModel->bindModel('forum');
        $theme = $themeModel->getById($id_theme);
        if (empty($theme) || !$theme->getForum())
            return $this->showMessage(__('Topic not found'), $this->getModuleURL());

        //turn access
        $this->forumACL('view_forums',$theme->getId_forum(),true);
        $this->forumACL('view_themes',$theme->getId_forum(),true);
        $this->forumACL('edit_themes', $theme->getId_forum(),true);



        // Check access to this forum. May be locked by pass or posts count
        $this->__checkForumAccess($theme->getForum());
        $id_forum = $theme->getId_forum();

        $this->__checkThemeAccess($theme);


        $html = '';
        // Если при заполнении формы были допущены ошибки
        if (isset($_SESSION['editThemeForm'])) {
            $name = h($_SESSION['editThemeForm']['theme']);
            $posts_select = $_SESSION['editThemeForm']['posts_select'];
            unset($_SESSION['editThemeForm']);
        } else {
            $name = '';
            $posts_select = array();
        }


        // Формируем список форумов, чтобы можно было переместить тему в другой форум
        $forums = $this->Model->getCollection(array(), array('order' => 'pos'));
        if (!$forums)
            return $this->showMessage(__('Can not find forum'), $this->getModuleURL("view_theme/$id_theme"));


        // Заголовок страницы (содержимое тега title)
        $this->page_title = __('Move posts') . ' - ' . h($theme->getTitle());


        $markers = array();
        $markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
            . get_link(__('forum'), $this->getModuleURL()) . __('Separator')
            . get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $id_forum)) . __('Separator')
            . get_link($theme->getTitle(), $this->getModuleURL('view_theme/' . $id_theme)) . __('Separator')
            . __('Move posts');


        // Page nav
        $postsModel = \OrmManager::getModelInstance('ForumPosts');

        $where = array('id_theme' => $id_theme);
        $first_post = $postsModel->getFirst(array(
            'id_theme' => $id_theme,
        ), array(
            'order' => 'time ASC, id ASC',
        ));
        if ($first_post) {
            $where[] = 'id != ' . $first_post->getId();
        }

        $posts_per_page = 100; // \Config::read('posts_per_page', $this->module);
        $total = $postsModel->getTotal(array('cond' => $where));
        if ($total < 1)
            return $this->showMessage(__('Not enough posts'), $this->getModuleURL('view_theme/' . $id_theme));
        list($pages, $page) = pagination($total, $posts_per_page, $this->getModuleURL('move_posts_form/' . $id_theme));
        $markers['pagination'] = $pages;
        //$this->page_title .= ' (' . $page . ')';

        // SELECT posts
        $posts = $postsModel->getCollection($where, array(
            'order' => 'time ASC, id ASC',
            'page' => $page,
            'limit' => $posts_per_page,
        ));


        $markers['meta'] = '';
        $this->_globalize($markers);


        $usersModel = \OrmManager::getModelInstance('Users');
        foreach ($posts as $post) {
            $postAuthor = $usersModel->getById($post->getId_author());
            $post->setAuthor_name($postAuthor ? $postAuthor->getName() : __('Guest'));
            $author_status = ($postAuthor) ? $postAuthor->getStatus() : 0;
            $message = \PrintText::print_page($post->getMessage(), $author_status);
            $post->setMessage($message);
        }


        $data = array(
            'action' => get_url($this->getModuleURL('move_posts/' . $id_theme . '?page=' . $page)),
            'theme' => $name,
            'posts_select' => (!empty($posts_select)) ? $posts_select : array(),
        );


        $source = $this->render('movepostsform.html', array(
            'posts' => $posts,
            'theme' => $theme,
            'context' => $data,
        ));

        return $this->_view($html . $source);
    }

    public function move_posts($id_theme = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        // Если не переданы данные формы - функция вызвана по ошибке
        if (!isset($id_theme) || !isset($_POST['theme']))
            return $this->showMessage(__('Some error occurred'));
        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'));


        $themeModel = \OrmManager::getModelInstance('ForumThemes');
        $theme = $themeModel->getById($id_theme);
        if (empty($theme))
            return $this->showMessage(__('Topic not found'));
        $id_forum = $theme->getId_forum();

        $this->forumACL('view_forums',$theme->getId_forum(),true);
        $this->forumACL('view_themes',$theme->getId_forum(),true);
        $this->forumACL('edit_themes', $theme->getId_forum(),true);

        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $id_from_forum = $theme->getId_forum();
        $id_new_theme = trim($_POST['theme']);

        $posts_select = array();
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'post_') === 0) {
                $number = substr($key, strlen('post_'));
                if (!empty($number))
                    $posts_select[] = intval($number);
            }
        }
        $posts_select = array_unique($posts_select, SORT_NUMERIC);


        // validate ...
        $error = '';
        if (empty($id_new_theme))
            $error .= '<li>' . sprintf(__('Empty field "param"'), __('Theme title')) . '</li>' . "\n";
        elseif ($id_new_theme == $id_theme)
            $error .= '<li>' . __('Moving into same topic') . '</li>' . "\n";
        else {
            $new_theme = $themeModel->getById($id_new_theme);
            if (empty($new_theme))
                $error .= '<li>' . __('Topic not found') . '</li>' . "\n";
        }
        if (empty($posts_select))
            $error .= '<li>' . __('Empty "posts_select"') . '</li>' . "\n";

        // errors
        if (!empty($error)) {
            $_SESSION['editThemeForm'] = array();
            $_SESSION['editThemeForm']['errors'] = '<p class="errorMsg">' . __('Some error in form')
                    . '</p>' . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            $_SESSION['editThemeForm']['theme'] = $id_new_theme;
            $_SESSION['editThemeForm']['posts_select'] = $posts_select;
            return $this->showMessage($_SESSION['editThemeForm']['errors'], $this->getModuleURL('move_posts_form/' . $id_theme));
        }

        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $first_post = $postsModel->getById(min($posts_select));
        if (!$first_post)
            return $this->showMessage(__('Some error occurred'),$this->getModuleURL('move_posts_form/' . $id_theme));
        $last_post = $postsModel->getById(max($posts_select));
        if (!$last_post)
            return $this->showMessage(__('Some error occurred'),$this->getModuleURL('move_posts_form/' . $id_theme));


        $postsModel->moveToTheme($id_new_theme, $posts_select);


        $new_last_post = $postsModel->getFirst(array(
            'id_theme' => $theme->getId(),
        ), array(
            'order' => 'id DESC'
        ));

        // update theme
        if ($new_last_post) {
            $theme->setId_last_author($new_last_post->getId_author());
            $theme->setLast_post($new_last_post->getTime());
        }
        $theme->setPosts($theme->getPosts() > count($posts_select) ? $theme->getPosts() - count($posts_select) : 0);
        $theme->save();

        $new_theme->setPosts($new_theme->getPosts() + count($posts_select));
        $new_theme->save();

        //update forums info
        $new_forum = $this->Model->getById($id_forum);
        if (!$new_forum)
            return $this->showMessage(__('No forum for moving'),$this->getModuleURL('move_posts_form/' . $id_theme));

        if ($id_from_forum != $id_forum) {
            $from_forum = $this->Model->getById($id_from_forum);
            if ($from_forum) {
                $from_forum->setPosts($from_forum->getPosts() > count($posts_select) ? $from_forum->getPosts() - count($posts_select) : 0);
                $from_forum->save();
            }

            $new_forum->setPosts($new_forum->getPosts() + count($posts_select));
            $new_forum->save();
        }

        $this->Model->upLastPost($id_from_forum, $id_forum);


        //clean cahce
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
        if ($this->isLogging)
            \Logination::write('move posts', 'theme id(' . $id_theme . ')');
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL('view_theme/' . $id_new_theme),'ok');
    }

    public function unite_themes_form($id_theme = null) {
        //turn access
        \ACL::turnUser(array($this->module,'view_forums_list'),true);

        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'), $this->getModuleURL());



        $themeModel = \OrmManager::getModelInstance('ForumThemes');
        $themeModel->bindModel('forum');
        $theme = $themeModel->getById($id_theme);
        if (empty($theme) || !$theme->getForum())
            return $this->showMessage(__('Topic not found'), $this->getModuleURL());


        //turn access
        $this->forumACL('view_forums',$theme->getId_forum(),true);
        $this->forumACL('view_themes',$theme->getId_forum(),true);
        $this->forumACL('edit_themes', $theme->getId_forum(),true);


        // Check access to this forum. May be locked by pass or posts count
        $this->__checkForumAccess($theme->getForum());
        $id_forum = $theme->getId_forum();

        $this->__checkThemeAccess($theme);


        $html = '';
        // Если при заполнении формы были допущены ошибки
        if (isset($_SESSION['editThemeForm'])) {
            $name = h($_SESSION['editThemeForm']['theme']);
            unset($_SESSION['editThemeForm']);
        } else {
            $name = '';
        }


        // Формируем список форумов, чтобы можно было переместить тему в другой форум
        $forums = $this->Model->getCollection(array(), array('order' => 'pos'));
        if (!$forums)
            return $this->showMessage(__('Some error occurred'), $this->getModuleURL());


        // Заголовок страницы (содержимое тега title)
        $this->page_title = __('Unite themes') . ' - ' . h($theme->getTitle());


        $markers = array();
        $markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
            . get_link(__('forum'), $this->getModuleURL()) . __('Separator')
            . get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $id_forum)) . __('Separator')
            . get_link($theme->getTitle(), $this->getModuleURL('view_theme/' . $id_theme)) . __('Separator')
            . __('Unite themes');


        $markers['pagination'] = '';
        $markers['meta'] = '';
        $this->_globalize($markers);


        $data = array(
            'action' => get_url($this->getModuleURL('unite_themes/' . $id_theme)),
            'theme' => $name,
        );


        $source = $this->render('unitethemesform.html', array(
            'theme' => $theme,
            'context' => $data,
        ));

        return $this->_view($html . $source);
    }

    public function unite_themes($id_theme = null) {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        // Если не переданы данные формы - функция вызвана по ошибке
        if (!isset($id_theme) || !isset($_POST['theme']))
            return $this->showMessage(__('Some error occurred'));
        if (!is_numeric($id_theme))
            return $this->showMessage(__('Value must be numeric'));

        $id_theme = (int)$id_theme;
        if ($id_theme < 1)
            return $this->showMessage(__('Topic not found'));


        $themeModel = \OrmManager::getModelInstance('ForumThemes');
        $themeModel->bindModel('poll');
        $theme = $themeModel->getById($id_theme);
        if (empty($theme))
            return $this->showMessage(__('Topic not found'));
        $id_forum = $theme->getId_forum();

        //turn access
        $this->forumACL('view_forums',$id_forum,true);
        $this->forumACL('view_themes',$id_forum,true);
        $this->forumACL('edit_themes', $id_forum,true);

        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $id_from_forum = $theme->getId_forum();
        $id_new_theme = trim($_POST['theme']);

        // validate ...
        $error = '';
        if (empty($id_new_theme))
            $error = $error . '<li>' . sprintf(__('Empty field "param"'), __('Theme title')) . '</li>' . "\n";
        elseif ($id_new_theme == $id_theme)
            $error = $error . '<li>' . __('Moving into same topic') . '</li>' . "\n";
        else {
            $new_theme = $themeModel->getById($id_new_theme);
            if (empty($new_theme))
                $error = $error . '<li>' . __('Topic not found') . '</li>' . "\n";
        }

        // errors
        if (!empty($error)) {
            $_SESSION['editThemeForm'] = array();
            $_SESSION['editThemeForm']['errors'] = '<p class="errorMsg">' . __('Some error in form')
                    . '</p>' . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            $_SESSION['editThemeForm']['theme'] = $id_new_theme;
            return $this->showMessage($_SESSION['editThemeForm']['errors'], $this->getModuleURL("unite_themes_form/$id_theme"));
        }

        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $posts = $postsModel->getCollection(array('id_theme' => $id_theme), array('fields' => array('id')));
        $posts_select = array();
        if ($posts && is_array($posts)) {
            foreach ($posts as $post) {
                $posts_select[] = intval($post->getId());
            }
        }

        $postsModel->moveToTheme($id_new_theme, $posts_select);

        $polls = $theme->getPoll();
        if ($polls) {
            if ($new_theme->getPoll()) {
                if (is_array($polls)) {
                    foreach ($polls as $poll) {
                        $poll->delete();
                    }
                }
            } else {
                if (is_array($polls)) {
                    $first = true;
                    foreach ($polls as $poll) {
                        if ($first) {
                            $poll->setTheme_id($id_new_theme);
                            $poll->save();
                            $first = false;
                        } else {
                            $poll->delete();
                        }
                    }
                }
            }
        }

        $theme->delete();

        $new_theme->setPosts($new_theme->getPosts() + count($posts_select));
        $new_theme->save();

        //update forums info
        $from_forum = $this->Model->getById($id_from_forum);
        if (!$from_forum)
            return $this->showMessage(__('No forum for moving'),$this->getModuleURL("unite_themes_form/$id_theme"));

        if ($id_from_forum != $id_forum) {
            $from_forum->setPosts($from_forum->getPosts() >= count($posts_select) ? $from_forum->getPosts() - count($posts_select) + 1 : 0);
            $from_forum->setThemes($from_forum->getThemes() - 1);
            $from_forum->save();

            $new_forum = $this->Model->getById($id_forum);
            if ($new_forum) {
                $new_forum->setPosts($new_forum->getPosts() + count($posts_select));
                $new_forum->save();
            }
        } else {
            $from_forum->setPosts($from_forum->getPosts() + 1);
            $from_forum->setThemes($from_forum->getThemes() - 1);
            $from_forum->save();
        }

        $this->Model->upLastPost($id_from_forum, $id_forum);


        //clean cahce
        $this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
        if ($this->isLogging)
            \Logination::write('move posts', 'theme id(' . $id_theme . ')');
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL('view_theme/' . $id_new_theme),'ok');
    }

    // Функция проверки прав доступа к форуму или его механизмам.
    protected function forumACL($key,$id_forum,$redirect = false) {

        // Определяем ID текущего пользователя и ID его группы
        $id_group = 0;
        $id_user = 0;
        if (isset($_SESSION['user']['name'])) {
            $id_group = $_SESSION['user']['status'];
            $id_user = $_SESSION['user']['id'];
        }

        if ((!\ACL::turn(array($this->module,'not_use_global_rights'),false,$id_forum) && // Проверяем права группы общие(если они действуют в этом форуме)
              \ACL::turnUser(array($this->module, $key),false))
              ||
              // Проверяем права группы в форме
              \ACL::turnUser(array($this->module, $key, 'forum.'.$id_forum),false,$id_group)) {
            return true;
        } else {
            if ($redirect)
                return $this->showMessage(__('Permission denied'), getReferer(),'error', true);
            else
                return false;
        }
    }

    /**
     * RSS
     *
     */
    function rss() {
        \ACL::turnUser(array($this->module,'view_forums_list'),true);
        
        $options = array(
            "model_instance" => 'ForumThemes',
            "bind_tables" => array('author','forum'),
            "query_params" => array(
                'order' => 'time DESC'
            ),
            "field_lastBuildDate" => function($records) {
                return strtotime($records[0]->getLast_post());
            },
            "fields_item" => array(
                "link" => function($record, $sitename) {
                    return $sitename . get_url($this->module . '/view_theme/' . $record->getId());
                },
                "pubDate" => function($record) {
                    return strtotime($record->getTime());
                },
                "description" => function($record) {
                    $announce = '';
                    
                    $post = $record->getFirst_post();
                    if ($post->getMessage() != null) {
                        $announce .= \PrintText::getAnnounce($post->getMessage(), '', \Config::read('rss_lenght', '__rss__'));
                        $atattaches = ($post->getAttacheslist() && count($post->getAttacheslist())) ? $post->getAttacheslist() : array();
                        if (count($atattaches) > 0) {
                            foreach ($atattaches as $attach) {
                                if ($attach->getIs_image() == '1') {
                                    $announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
                                }
                            }
                        }
                    }
                    return $announce;
                },
                "category" => function($record) {
                    return $record->getForum()->getTitle();
                },
                "enclosure" => function($record, $sitename) {
                    $images = array();
                    
                    $post = $record->getFirst_post();
                    $atattaches = ($post->getAttacheslist() && count($post->getAttacheslist())) ? $post->getAttacheslist() : array();
                    if (count($atattaches) > 0) {
                        
                        foreach ($atattaches as $attach) {
                            if ($attach->getIs_image() == '1') {
                                $images[] = array(
                                    "url" => $sitename.get_url($this->getImagesPath($attach->getFilename())),
                                    "type" => 'image/'.substr(strrchr($attach->getFilename(), "."), 1),
                                );
                                break;
                            }
                        }
                    }
                    return $images;
                }
            )
        );
        
        include_once R.'sys/inc/rss.php';
    }

}
