<?php
/**
* @project    DarsiPro CMS
* @package    Pages Module
* @url        https://darsi.pro
*/

namespace PagesModule;

Class ActionsHandler extends \Module {

    /**
     * @module_title  title of module
     */
    public $module_title = 'Страницы';

    /**
     * @template  layout for module
     */
    public $template = 'pages';

    /**
     * @module module indentifier
     */
    public $module = 'pages';


    function __construct($params) {
        parent::__construct($params);

        $this->setModel();
        
        
    }

    /**
     * default action
     */
    function index($id = null, $s = null, $x = null) {
        //if isset ID - we need load page with this ID
        if (!empty($id)) {
			if (!preg_match('#^[\da-zа-я_\-./]+$#iu', $id)) {
                http_response_code(404);
                include_once R.'sys/inc/error.php';
                die();
            }

            $page = $this->Model->getByUrl($id);
            if (!$page) {
                if (is_numeric($id)) {
                    $id = intval($id);
                    if ($id < 2) {
                        http_response_code(404);
                        include_once R.'sys/inc/error.php';
                        die();
                    }

                    $page = $this->Model->getById($id);
                    if (!$page) {
                        http_response_code(404);
                        include_once R.'sys/inc/error.php';
                        die();
                    }
                } else {
                    http_response_code(404);
                    include_once R.'sys/inc/error.php';
                    die();
                }
            }
            $id = $page->getId();

            $this->page_title = $page->getName();
            $this->page_meta_keywords = $page->getMeta_keywords();
            $this->page_meta_description = $page->getMeta_description();
            $source = $page->getContent();
            $source = $this->renderString($source, array('entity' => $page));


            // Tree line
            $navi['navigation'] = get_link(__('Home'), '/');
            $cnots = explode('.', $page->getPath());
            if (false !== ($res = array_search(1, $cnots)))
                unset($cnots[$res]);
            if (!empty($cnots)) {
                $ids = "'" . implode("', '", $cnots) . "'";
                $pages = $this->Model->getCollection(array(
                    "`id` IN (" . $ids . ")"
                ), array(
                    'order' => 'path',
                ));

                if (!empty($pages) && is_array($pages)) {
                    foreach ($pages as $p) {
                        $navi['navigation'] .= __('Separator') . get_link(__($p->getName()), '/' . $this->Model->buildUrl($p->getId()));
                    }
                }
            }
            $navi['navigation'] .= __('Separator') . h($page->getName());

            $navi['page_id'] = $id;
            $this->_globalize($navi);
            
            if (!$page->getTemplate())
                $page_templ = 'main.html';
            else
                $page_templ = $page->getTemplate();
            
            // disable using main.html for base in $this->_view()
            $this->wrap = false;
            return $this->_view($this->render($page_templ, array('content'=>$source) ));


            //may be need view latest materials
        } else {
            $this->page_title = \Config::read('title');
            $latest_on_home = \Config::read('latest_on_home');
            $navi = null; //vsyakiy sluchay:)
            //if we want view latest materials on home page
            if (is_array($latest_on_home) && count($latest_on_home) > 0) {

                // Navigation Block
                $navi = array();
                $navi['add_link'] = (\ACL::turnUser(array('news','add_materials'))) ? get_link(__('Add material'), '/news/add_form') : '';
                $navi['navigation'] = get_link(__('Home'), '/');
                $this->_globalize($navi);


                if ($this->cached && $this->Cache->check($this->cacheKey)) {
                    $html = $this->Cache->read($this->cacheKey);
                    return $this->_view($html);
                }


                //create SQL query
                $entities = $this->Model->getEntitiesByHomePage($latest_on_home);


                //if we have records
                if (count($entities) > 0) {

                    // Get users(authors)
                    $uids = array();
                    $mod_mats = array('news' => array(), 'stat' => array(), 'loads' => array());
                    foreach ($entities as $key => $mat) {
                        $uids[] = $mat->getAuthor_id();
                        switch ($mat->getSkey()) {
                            case 'news':
                                $mod_mats['news'][$key] = $mat;
                                break;
                            case 'stat':
                                $mod_mats['stat'][$key] = $mat;
                                break;
                            case 'loads':
                                $mod_mats['loads'][$key] = $mat;
                                break;
                        }
                    }


                    $uids = '(' . implode(', ', $uids) . ')';
                    $uModel = \OrmManager::getModelInstance('users');
                    $authors = $uModel->getCollection(array('`id` IN ' . $uids));


                    foreach ($mod_mats as $module => $mats) {
                        if (count($mats) > 0) {
                            $attach_ids = array();
                            foreach ($mats as $mat) {
                                $attach_ids[] = $mat->getId();
                            }


                            $ids = implode(', ', $attach_ids);
                            $attModel = \OrmManager::getModelInstance($module . 'Attaches');
                            $attaches = $attModel->getCollection(array('`entity_id` IN (' . $ids . ')'));

                            foreach ($mats as $mat) {
                                if ($attaches) {
                                    foreach ($attaches as $attach) {
                                        if ($mat->getId() == $attach->getEntity_id()) {
                                            $currAttaches = $mat->getAttaches();
                                            if (is_array($currAttaches)) {
                                                $currAttaches[] = $attach;
                                            } else {
                                                $currAttaches = array($attach);
                                            }

                                            $mat->setAttaches($currAttaches);
                                        }
                                    }
                                }
                            }
                        }
                    }


                    $entities = $mod_mats['news'] + $mod_mats['stat'] + $mod_mats['loads'];
                    ksort($entities);


                    //if we have materials for view on home page (now we get their an create page)
                    $info = null;
                    foreach ($entities as $result) {
                        foreach ($authors as $author) {
                            if ($result->getAuthor_id() == $author->getId()) {
                                $result->setAuthor($author);
                                break;
                            }
                        }


                        // Create and replace markers
                        $this->Register['current_vars'] = $result;

                        //moder panel
                        $result->setModer_panel($this->_getAdminBar($result->getId(), $result->getSkey()));
                        $entry_url = get_url(entryUrl($result, $result->getSkey()));
                        $result->setEntry_url($entry_url);


                        $matattaches = ($result->getAttaches() && count($result->getAttaches())) ? $result->getAttaches() : array();
                        $announce = $result->getMain();


                        $announce = \PrintText::getAnnounce($announce, '', \Config::read('announce_lenght'), $result);


                        if (count($matattaches) > 0) {
                            $img = array();
                            foreach ($matattaches as $attach) {
                                if ($attach->getIs_image() == '1') {
                                    $announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number(), $result->getSkey());
                                    $img['url_'.$attach->getAttach_number()] = $this->markerImageAttach($attach->getFilename(), $attach->getAttach_number(),  $result->getSkey());
                                    $img['small_url_'.$attach->getAttach_number()] = $this->markerSmallImageAttach($attach->getFilename(), $attach->getAttach_number(), $result->getSkey());
                                }
                            }
                            $result->setImg($img);
                        }

                        $result->setAnnounce($announce);

                        $result->setProfile_url(getProfileUrl($result->getAuthor_id()));

                        $result->setModule_name($result->getSkey());

                        $category = $result->getCategories();
                        $result->setCategory($category[0]);
                        $category_name = '';
                        foreach($category as $n => $cat) {$category_name .= ($n !== 0 ? ', ' : '').h($cat->getTitle());}
                        $result->setCategory_name(h($category_name));
                        $result->setCategory_url(get_url('/'.$result->getSkey().'/category/'.$result->getCategory_id()));

                        $result->setModule_title(\Config::read('title', $result->getSkey()));


                        //set users_id that are on this page
                        $this->setCacheTag(array(
                            'module_' . $result->getSkey(),
                            'record_id_' . $result->getId(),
                        ));
                    }

                    $html = $this->render('list.html', array('entities' => $entities));


                    //write int cache
                    if ($this->cached)
                        $this->Cache->write($html, $this->cacheKey, $this->cacheTags);
                }


                if (empty($html))
                    $html = $this->render('list.html');
                return $this->_view($html);
            } else {
                return $this->_view('');
            }
            return $this->showMessage(__('Some error occurred'));
        }
    }

    /**
     * @param int $id - record ID
     * @param string $module - module
     * @return string - admin buttons
     *
     * create and return admin bar
     */
    protected function _getAdminBar($id, $module) {
        $moder_panel = '';
        if (\ACL::turnUser(array($module,'edit_materials'))) {
            $moder_panel .= get_link('', '/' . $module . '/edit_form/' . $id, array('class' => 'drs-edit'));
        }
        if (\ACL::turnUser(array($module,'up_materials'))) {
            $moder_panel .= get_link('', '/' . $module . '/upper/' . $id, array('id' => 'fum'.$id, 'class' => 'drs-up', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fum".$id."')}; return false"));
        }
        if (\ACL::turnUser(array($module,'on_home'))) {
            $moder_panel .= get_link('', '/' . $module . '/off_home/' . $id, array('id' => 'fom'.$id, 'class' => 'drs-on', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fom".$id."')}; return false"));
        }
        if (\ACL::turnUser(array($module,'delete_materials'))) {
            $moder_panel .= get_link('', '/' . $module . '/delete/' . $id, array('id' => 'fdm'.$id, 'class' => 'drs-delete', 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu('fdm".$id."')}; return false"));
        }

        return $moder_panel;
    }

}
