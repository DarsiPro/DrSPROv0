<?php
/**
 * @project     DarsiPro
 * @author      Петров Евгений <email@mail.ru>
 * @package     Entry dot
 * @url         https://darsi.pro
 */

include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';


/**
 * Возвращает текущий модуль, который мы редактируем
 */
function getCurrMod() {
    if (empty($_GET['mod'])) {
        $_SESSION["message"][] = __('Some error occurred');
        redirect('/admin/');
    }
    
    $mod = trim($_GET['mod']);
    if (!Config::read($mod.".std_admin_pages.category")) {
        $_SESSION["message"][] = __('Some error occurred');
        redirect('/admin/');
    }
    return $mod;
}



/**
 * Try find collision
 */
/*function deleteCatsCollision() {
    global $DB;
    $collision = $DB->select(getCurrMod() . '_categories', DB_ALL, array(
        'joins' => array(
            array(
                'type' => 'LEFT',
                'table' => getCurrMod() . '_categories',
                'alias' => 'b',
                'cond' => '`b`.`id` = `a`.`parent_id`',
            ),
        ),
        'fields' => array('COUNT(`b`.`id`) as cnt', '`a`.*'),
        'alias' => 'a',
        'group' => '`a`.`parent_id`',
    ));
    
    if ($collision && is_array($collision) && count($collision)) {
        foreach ($collision as $key => $cat) {
            if (!empty($cat['parent_id']) && empty($cat['cnt'])) {
                $DB->save(getCurrMod() . '_categories',
                array(
                    'parent_id' => 0,
                ), 
                array(
                    'id' => $cat['id']
                ));
            }
        }
    }
}
deleteCatsCollision();
*/


//$head = file_get_contents('template/header.php'); // ????
$page_title = __(getCurrMod(),false,getCurrMod());
$cat_selector = '';
$subcat_true = false;

$Register = Register::getInstance();
$Register['module'] = getCurrMod();




if (!isset($_GET['ac'])) $_GET['ac'] = 'index';


$permis = array('add', 'del', 'index', 'edit', 'off_home', 'on_home');

if (!in_array($_GET['ac'], $permis)) $_GET['ac'] = 'index';




switch($_GET['ac']) {
    case 'index':
        $content = index($page_title);
        break;
    case 'del':
        $content = delete();
        break;
    case 'add':
        $content = add();
        break;
    case 'edit':
        $content = edit();
        break;
    case 'on_home':
        $content = on_home();
        break;
    case 'off_home':
        $content = off_home();
        break;
    default:
        $content = index();
}






// Обработка действий
    if (RST::method('post')) {
        //print_r('qq'.RST::method('post').'qq');
        
        // не работает в процессе ......
        // Действия с выбранными
        $ids = RST::post('check');
        if(is_array($ids)) {
            
            switch(RST::post('action')) {
                
                case 'disable': {
                    foreach($ids as $id)
						//$this->articles_categories->update_articles_category($id, array('visible'=>0)); 
						print_r('disable');
					break;
                }
                case 'enable': {
                    foreach($ids as $id)
						//$this->articles_categories->update_articles_category($id, array('visible'=>1));
						print_r('enable');
					break;
                }
                case 'delete': {
                    foreach($ids as $id)
						//$this->articles_categories->delete_articles_category($id);
						print_r('delete');
					break;
                }
                
			}
        }
    }



























//$pageSection = 'category';  // если заданно - по нему происходит доп. подключение category.js
$pageTitle = $page_title;
$pageNav = $page_title;
$pageNavr = '';


function getTreeNode($array, $id = false, $level=0) {
    global $subcat_true;
    
    
    
    $out = array();
    
    foreach ($array as $key => $val) {
        if ($id === false && empty($val['parent_id'])) {
            $val['level'] = $level;
            $out[$val['id']] = array(
                'category' => $val,
                'subcategories' => getTreeNode($array, $val['id'],$level += 1),
            );
            $level = 0;
            unset($array[$key]);
        
            
            
        } else {
            if(!$subcat_true) {$subcat_true = true;}
            $val['level'] = $level;
            if ($val['parent_id'] == $id) {
                $out[$val['id']] = array(
                    'category' => $val,
                    'subcategories' => getTreeNode($array, $val['id'],$level += 1),
                );
                $level--;
                unset($array[$key]);
            }
        }
    }
    return $out;
}


function buildCatsList($catsTree, $catsList, $level = 0) {
    global $cat_selector;
    
    //$Register = Register::getInstance();
    $DB = getDB();
    $acl_groups = ACL::get_group_info();
    $out = '';
    
    if (!$catsTree || !is_array($catsTree)) return $out;
    
    
    foreach ($catsTree as $id => $node) {
        if (!isset($node['category'])) continue;
        
        $cat = $node['category'];
        $no_access = (isset($cat['no_access']) && $cat['no_access'] !== '') ? explode(',', $cat['no_access']) : array();
        $catsList = ($catsList && is_array($catsList) && count($catsList)) ? $catsList : array();
        
        $cat_selector .= '<option value="' . $cat['id'] . '">';
        $cat_selector .= str_repeat(' - ', $cat['level']);
        $cat_selector .= h($cat['title']) . '</option>';
       
       
       
       
       
       
       //<a class="ui-sortable-handle" tooltip="Нажмите и перетащите категорию для изменения порядка сортировки в каталоге" href="javascript:void(0);">
              //  <i class="icon-arrow-combo"></i>
           // </a>
       
       
        $out .= '<tr id="ds_catTr' . $cat['id'] . '" data-id="' . $cat['id'] . '" data-level="' . $level . '" data-parent="' . $cat[parent_id] . '" data-access="' . $cat[no_access] . '" class="ds_catTr'.$cat[parent_id].'"';
        if ($cat[parent_id]) {$out .= ' style="display: none;"';}
        $out .= '><th scope="row"><input type="checkbox" name="check[]" value="' . $cat['id'] . '" class="checkbox"></th>
        <th>
            
            
            <span class="list-sort"></span>
            
            
        </th>
        <th class="number">' . $cat['id'] .'</th>
        <td class="name">';
        $out .= '<div class="float-left" style="width: 20px;">';
        if (isset($node['subcategories']) && is_array($node['subcategories']) && count($node['subcategories'])) {
            $out .= '<a id="ds_catTL' . $cat['id'] . '" href="javascript:void(0)" data-id="' . $cat['id'] . '" class="ds_catTL icon-size2 icon-squared-plus"></a>';
        }else{
            $out .= '&nbsp;';
        }
            $out .= '</div>';
         $out .= str_repeat('<i class="icon-right-thin mid-margin-right"></i>', $level) .'
            <span>'. h($cat['title']) .'</span> (' . $cat['cnt'] . ')
                <a class="" href="/odejda" tooltip="Открыть категорию на сайте" target="_blank"><i class="icon-link"></i></a>
        </td>
        <td>инфо ' . $cat['position'] . '</td>
        <td><a class="tip" href="/odejda" aria-label="Перейти на страницу категории" tooltip="Перейти на страницу категории" flow="up" target="blank">/odejda</a></td>
        <td class="align-right low-padding">
            <span class="button-group children-tooltip">';
                if (getCurrMod() != 'foto') {
                    if ($cat['view_on_home'] == 1) {
                        $out .=  '<a href="?ac=off_home&id='.$cat['id'].'&mod='.getCurrMod().'" title="' . __('Down') . '" class="button confirm icon-home icon-size2 green"></a>';
                    } else {
                        $out .=  '<a href="?ac=on_home&id='.$cat['id'].'&mod='.getCurrMod().'" title="' . __('Up') . '" class="button confirm icon-home icon-size2"></a>';
                    }
                }
                
                $out .= '
                <a href="javascript:void(0)" title="' . __('Edit') . '" class="ds_editSt button icon-gear icon-size2"></a>
                <a href="?ac=del&id=' . $cat['id'] . '&mod='.getCurrMod().'" title="' . __('Delete') . '" class="button confirm icon-trash icon-size2"></a>
            </span>
                </td>
        </tr>';
        
        if (isset($node['subcategories']) && is_array($node['subcategories']) && count($node['subcategories'])) {
            $out .= buildCatsList($node['subcategories'], $catsList, $level+1);
        }
    }
    return $out;
}






function index(&$page_title) {
    global $cat_selector;
    global $subcat_true; // true если есть хотябы одна субкатегория из всего списка
    global $edate;
    $edate = new stdClass();
    $result = '';
    
    //$Register = Register::getInstance();
    $DB = getDB();
    $acl_groups = ACL::get_group_info();


    $page_title .= ' - ' . __('Sections editor');
    
    
    
    
    
    // Обработка действий
    if (RST::method('post')) {
        //print_r('qq'.RST::method('post').'qq');
        
        
        // Сортировка
        $s = RST::post('sort');
        if (isset($s)) {
            $sort = new stdClass;
            $sort->id = intval($s['id']);
            $sort->parent = intval($s['parent']);
            $sort->positions = preg_replace('/[^,0-9]/', '', $s['sort']);
            if(!empty($sort->id)) {
                if(isset($sort->parent)) {
                    if(empty($sort->positions)) {// Переместили в новую группу
                        $last = $DB->query("SELECT MAX(`position`) AS last FROM `" . $DB->getFullTableName(getCurrMod() . '_categories') . "` WHERE `parent_id` = '" . $sort->parent . "' LIMIT 1");
                        if (!empty($last[0]['last'])) {
                            $in_pos = ((int)$last[0]['last'] + 1);
                        } else {
                            $in_pos = 1;
                        }
                        $DB->save(getCurrMod() . '_categories', array('id' => $sort->id, 'parent_id' => $sort->parent, 'position' => $in_pos));
                    }else{
                        // Сортировка
                        $ids = explode(',', $sort->positions);
                        foreach($ids as $i=>$id) {
                            $i++;
                            $DB->save(getCurrMod() . '_categories', array('id' => $id, 'parent_id' => $sort->parent, 'position' => $i,));
                            
                        }
                    }
                }
            }else{
                exit;
            }
        }
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        /*
        if (isset($_POST['sort'])) {
            $sort = $_POST['sort'];
            if(!empty($sort['id'])) {
                if(isset($sort[parent])) {
                    if(empty($sort['sort'])) {// Переместили в новую группу
                        $last = $DB->query("SELECT MAX(`position`) AS last FROM `" . $DB->getFullTableName(getCurrMod() . '_categories') . "` WHERE `parent_id` = '" . $sort['parent'] . "' LIMIT 1");
                        if (!empty($last[0]['last'])) {
                            $in_pos = ((int)$last[0]['last'] + 1);
                        } else {
                            $in_pos = 1;
                        }
                        $DB->save(getCurrMod() . '_categories', array('id' => $sort['id'], 'parent_id' => $sort['parent'], 'position' => $in_pos));
                    }else{
                        // Сортировка
                        $ids = explode(',', $sort['sort']);
                        foreach($ids as $i=>$id) {
                            $i++;
                            $DB->save(getCurrMod() . '_categories', array('id' => $id, 'parent_id' => $sort['parent'], 'position' => $i,));
                            
                        }
                    }
                }
            }else{
                exit;
            }
        }
        */
        
        
        
        
    }
    
        
    
    
    
    $all_sections = $DB->select(getCurrMod() . '_categories', DB_ALL, array(
        'joins' => array(
            array(
                'alias' => 'b',
                'type' => 'LEFT',
                'table' => getCurrMod(),
                'cond' => 'a.`id` = b.`category_id`',
            ),
        ),
        'fields' => array('a.*', 'COUNT(b.`id`) as cnt'),
        'alias' => 'a',
        'group' => 'a.`id`',
        'order' => 'position',
    ));
    
    if (!$all_sections || !is_array($all_sections)) $all_sections = array();
    
    
    $html = '';
    
    $cats_tree = getTreeNode($all_sections);
    
    
    
    
    $edate->mod = getCurrMod();
        $edate->groups = '';
        
        $n = 0;
        if ($acl_groups && is_array($acl_groups)) {
            foreach ($acl_groups as $gid => $group) {
                if (($n % 3) == 0) $edate->groups .= '</tr><tr>';
                $edate->groups .= '<td class="with-mid-padding"><input id="ac_' . $gid . '" type="checkbox" name="access[' . $gid . ']" value="' . $gid 
                . '" class="checkbox" checked="checked" /><label class="with-small-padding" for="ac_' . $gid . '"> ' . h($group['title']) . '</label></td>';
                $n++;
            }
        }
        $edate->editing = __('Section editing');
        $edate->title   = __('Title');
        $edate->add     = __('Add section');
        $edate->parent  = __('Parent section');
        $edate->select  = '<option value="0">' . __('Root category') . '</option>';
        $edate->access  = __('Access for');
        $edate->save    = __('Save');
        $edate->close   = __('Close');
        
    
    
    // формирование страницы с категориями
    if ($c = count($all_sections) > 0) {
    $html .= '<p class="big-message">
	    <a href="#" title="'.__('Close').'" class="close with-tooltip">✕</a>
	    <span class="big-message-icon icon-red icon-warning with-small-padding"></span>
	    <strong>'. __('If you delete a category, all the materials in it will be removed').'</strong><br>
    </p>';
    }
    
    $html .= '<a href="javascript:void(0)" onclick="category.catForm()" class="button float-right large-margin-bottom">
                <span class="button-icon"><span class="icon-list-add icon-size2"></span></span>' . __('Add section') . '
            </a>';
    $html .= '<table class="simple-table">
        <thead>
            <tr>
                <th class="checkbox-cell" scope="col"><input class="checkbox';
                if (!$c) {$html .= ' disabled';}
                $html .= '" type="checkbox" name="check-all" id="check-all" value="1"></th>
                <th class="checkbox-cell" scope="col"></th>
                <th class="checkbox-cell" scope="col" class="align-center">ID</th>
                <th scope="col" class="align-left">' . __('Title');
                if ($subcat_true) {
                    $html .= '<a id="ds_catAllsh" href="javascript:void(0)" class="margin-left with-tooltip icon-squared-plus" title="' . __('Show hide categories') . '"></a>';
                    
                }
                $html .= '</th>
                <th scope="col">Text</th>
                <th scope="col" class="align-center">URL</th>
                <th scope="col" class="align-right">' . __('Activity') . '</th>
            </tr>
        </thead>
        <tfoot class="fixed">
            <tr>
                <td colspan="7">Table footer</td>
            </tr>
        </tfoot>
        <tbody id="dsList" class="list">';
    if ($c) {
        
        $result['tr'] = buildCatsList($cats_tree, $all_sections);
        
        $html .= $result['tr'];
        $edate->select  = $edate->select.$cat_selector;
        $result['e'] = $edate;
        
    } else {
        $html .= '<tr><td colspan="7"><h2 class="thin no-margin-top">'.__('Sections not found').'</h2></td></tr>';
    }
    $html .= '</tbody></table>';
    
    
    
    
    if (empty($sort)) {
        return $html;
    }else{
        header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($result);
            exit;
    }
}


function add() {
    global $DB;
    if (empty($_POST['title'])) redirect('/admin/category.php?mod=' . getCurrMod());
    
    $acl_groups = ACL::get_group_info();
    
    
    $error = array();
    $title = getDB()->escape($_POST['title']);
    $in_cat = intval((int)$_POST['id_sec']);
    if ($in_cat < 0) $in_cat = 0;
    
    if (empty($title)) $error[] = __('Empty field "title"');
    
    $no_access = array();
    if ($acl_groups && is_array($acl_groups)) {
        foreach ($acl_groups as $id => $group) {
            if (!array_key_exists($id, $_POST['access'])) {
                $no_access[] = $id;
            }
        }
    }
    $no_access = (count($no_access)) ? implode(',', $no_access) : '';
    if ($no_access !== '') $no_access = new Expr($no_access);
    
    /* if errors exists */
    if (!empty($error)) {
        $_SESSION['message'] = array_merge($_SESSION['message'], $error);
        redirect('/admin/category.php?mod=' . getCurrMod());
    }
    
    
    if (empty($error)) {
        $id = $DB->save(getCurrMod() . '_categories', array(
            'title' => $title,
            'parent_id' => $in_cat,
            'no_access' => $no_access,
        ));
        
        if (!empty($id)) {
            $data = array(
            'id' => $id, 
            'position' => $id,
            );
            $DB->save(getCurrMod() . '_categories', $data);
        }
        
    }
        
    redirect('/admin/category.php?mod=' . getCurrMod());
}



function edit() {
    


    if (!isset($_GET['id'])) redirect('/admin/category.php?mod=' . getCurrMod());
    if (!isset($_POST['title'])) redirect('/admin/category.php?mod=' . getCurrMod());
    $id = intval($_GET['id']);
    
    if ($id < 1) redirect('/admin/category.php?mod=' . getCurrMod());
    
    
    global $DB;
    
    $acl_groups = ACL::get_group_info();
    
    
    $error = array();

    if (empty($_POST['title'])) $error[] = __('Empty field "title"');
    


    $parent_id = intval($_POST['id_sec']);
    $changed_cat = $DB->select(getCurrMod() . '_categories', DB_FIRST, array('cond' => array('id' => $id)));
    if (empty($changed_cat)) $error[] = __('Edited section not found');

    
    /* we must know changed parent section or not changed her. And check her  */
    if (!empty($parent_id) && $changed_cat[0]['parent_id'] != $parent_id) {
        $target_section = $DB->select(getCurrMod() . '_categories', DB_COUNT, array('cond' => array('id' => $parent_id)));
        if ($target_section < 1) $error[] = __('Parent section not found');
    }
    /* if errors exists */
    if (!empty($error)) {
        $_SESSION['message'] = array_merge($_SESSION['message'], $error);
        redirect('/admin/category.php?mod=' . getCurrMod());
    }
    
    
    $no_access = array();
    if ($acl_groups && is_array($acl_groups)) {
        foreach ($acl_groups as $gid => $group) {
            if (!array_key_exists($gid, $_POST['access'])) {
                $no_access[] = $gid;
            }
        }
    }
    $no_access = (count($no_access) == 1 && $no_access[0] !== '') ? intval($no_access[0]) : implode(',', $no_access);
    
    
    /* prepare data to save */
    $data = array(
        'id' => $id, 
        'title' => substr($_POST['title'], 0, 100), 
        'no_access' => $no_access,
    );
    if (isset($parent_id)) $data['parent_id'] = (int)$parent_id;
    $DB->save(getCurrMod() . '_categories', $data);
        

    redirect('/admin/category.php?mod=' . getCurrMod());
}





function delete() {
    global $DB;
    $id = (!empty($_GET['id'])) ? intval($_GET['id']) : 0;
    if ($id < 1) redirect('/admin/category.php?mod=' . getCurrMod());
    
    $childCats = OrmManager::getModelInstance(getCurrMod() . 'Categories')->getOneField('id', array('parent_id' => $id));
    $ids = '`category_id` LIKE \'%'.$id.'%\'';
    if ($childCats && is_array($childCats) && count($childCats) > 0)
        $ids .= ' OR `category_id` IN (' . implode(', ', array_unique($childCats)) . ')';
    $where = array($ids);


    $COUNT = $DB->select(getCurrMod(), DB_COUNT, array('cond' => $where));
    if ($COUNT > 0) {
        $_SESSION['message'][] = __('Category is not empty.');
        redirect('/admin/category.php?mod=' . getCurrMod());
    }
    
    $childrens = $DB->select(getCurrMod() . '_categories', DB_ALL, array('cond' => array('parent_id' => $id)));
    
    if (!$childrens || !is_array($childrens) || !count($childrens)) {
        delete_category($id);
    } else {
        foreach ($childrens as $category) {
            delete_category($category['id']);
            delete($category['id']);
        }
        $DB->query("DELETE FROM `" . $DB->getFullTableName(getCurrMod() . '_categories') . "` WHERE `id`='{$id}'");
    }
    redirect('/admin/category.php?mod=' . getCurrMod());
}


function delete_category($id) {
    global $DB;
    $records = $DB->select(getCurrMod(), DB_ALL, array('cond' => array('category_id' => $id)));
    if ($records || is_array($records) || count($records) > 0) {
        $drsurl = new DrsUrl;
        foreach ($records as $record) {
            $DB->query("DELETE FROM `" . $DB->getFullTableName(getCurrMod()) . "` WHERE `id`='{$record['id']}'");
            
            
            $hlufile = $drsurl->searchHluById($record['id'], getCurrMod());
            if (file_exists($hlufile))
                _unlink($hlufile);
            
            if (getCurrMod() == 'foto') {
                if (file_exists(ROOT . '/data/files/foto/full/' . $record['filename'])) 
                    _unlink(ROOT . '/data/files/foto/full/' . $record['filename']);
                if (file_exists(ROOT . '/data/files/foto/preview/' . $record['filename'])) 
                    _unlink(ROOT . '/data/files/foto/preview/' . $record['filename']);
                    
            } else {
                $attaches = $DB->select(getCurrMod() . '_attaches', DB_ALL, array('cond' => array('entity_id' => $record['id'])));
                if ($attaches && is_array($attaches) && count($attaches)) {
                    foreach ($attaches as $attach) {
                        $DB->query("DELETE FROM `" . $DB->getFullTableName(getCurrMod() . '_attaches') 
                        . "` WHERE `id`='{$attach['id']}'");
                        if (file_exists(ROOT . '/data/files/' . getCurrMod() . '/' . $attach['filename']))
                            _unlink(ROOT . '/data/files/' . getCurrMod() . '/' . $attach['filename']);
                    }
                }
                
                if (getCurrMod() == 'loads') {
                    if (file_exists(ROOT . '/data/files/loads/' . $record['download'])) 
                        _unlink(ROOT . '/data/files/loads/' . $record['download']);
                }
            } 
        }
    }
    $DB->query("DELETE FROM `" . $DB->getFullTableName(getCurrMod() . '_categories') . "` WHERE `id`='{$id}'");
    return true;
}



function on_home($cid = false) {
    global $DB;
    if (getCurrMod() == 'foto') redirect('/admin/category.php?mod=' . getCurrMod());
    
    
    if ($cid === false) {
        $id = (!empty($_GET['id'])) ? intval($_GET['id']) : 0;
        if ($id < 1) redirect('/admin/category.php?mod=' . getCurrMod());
    } else {
        $id = $cid;
    }

    
    $childs = $DB->select(getCurrMod() . '_categories', DB_ALL, array('cond' => array('parent_id' => $id)));
    if ($childs && is_array($childs) && count($childs)) {
        foreach ($childs as $child) {
            on_home($child['id']);
        }
    } 
    
    $DB->save(getCurrMod() . '_categories', array('id' => $id, 'view_on_home' => 1));
    $DB->save(getCurrMod(), array('view_on_home' => 1), array('category_id' => $id));

        
    if ($cid === false) redirect('/admin/category.php?mod=' . getCurrMod());
}



function off_home($cid = false) {
    global $DB;
    if (getCurrMod() == 'foto') redirect('/admin/category.php?mod=' . getCurrMod());
    
    if ($cid === false) {
        $id = (!empty($_GET['id'])) ? intval($_GET['id']) : 0;
        if ($id < 1) redirect('/admin/category.php?mod=' . getCurrMod());
    } else {
        $id = $cid;
    }

    
    $childs = $DB->select(getCurrMod() . '_categories', DB_ALL, array('cond' => array('parent_id' => $id)));
    if ($childs && is_array($childs) && count($childs)) {
        foreach ($childs as $child) {
            off_home($child['id']);
        }
    } 
    
    $DB->save(getCurrMod() . '_categories', array('id' => $id, 'view_on_home' => 0));
    $DB->save(getCurrMod(), array('view_on_home' => 0), array('category_id' => $id));

        
    if ($cid === false) redirect('/admin/category.php?mod=' . getCurrMod());
}

include_once ROOT . '/admin/template/header.php'; ?>



<!-- Main title -->
		<hgroup id="main-title" class="thin">
			<h1><?php echo $pageTitle; ?></h1>
			<h2><?php echo date('M');?> <strong><?php echo date('j');?></strong></h2>
		</hgroup>

<div class="with-padding">
    
    <?php echo $content; ?>
</div>











<script>













var edate = <?php echo json_encode($edate) ?>;
$(window).on('load', function() {
    

    
    $.extend($.fn.confirm.defaults, {message: '<?php echo __('Are you sure?') ?>',confirmText: '<?php echo __('Yes') ?>',cancelText: '<?php echo __('No') ?>'});
    //category.init();
});

/*
localStorage.removeItem('activeTab'+edate.mod);


// Показать/скрыть категории по клику
$('body').on("click", ".ds_catTL",function () {
    // Удалить существующие специальные строки
    $('tr.row-drop').remove();
    hideShowRows($(this));
});


function hideShowRows(ts, aj=false) {
    let LS = JSON.parse(localStorage.getItem('activeTab'+edate.mod)),
        i = ts.data('id'), // show id нажатой кнопки
        t = ts.closest('tr'),
        p = t.data('level'), // от какого
        n = t.next(),
        l = n.data('level'); // до какого
    if (!LS) {LS=[];}
    if (ts.hasClass('icon-squared-minus')){
            ts.removeClass('icon-squared-minus');
            ts.addClass('icon-squared-plus');
            // Удаление из localStorage
            let m = LS.indexOf('#ds_catTL'+ $(ts).data('id'));
            if (m !== -1) {LS.splice(m, 1);}
            localStorage.setItem('activeTab'+edate.mod, JSON.stringify(LS));
        while (l > p) {
            n.hide().find('td.name>div>a.icon-squared-minus').toggleClass('icon-squared-plus icon-squared-minus');
            // Удаление из localStorage
            m = LS.indexOf('#ds_catTL'+ n.data('id'));
            if (m !== -1) {LS.splice(m, 1);}
            localStorage.setItem('activeTab'+edate.mod, JSON.stringify(LS));
            n = n.next();
            l = n.data('level');
        }
    } else {
        ts.removeClass('icon-squared-plus');
        ts.addClass('icon-squared-minus');
        $('.ds_catTr'+i).show();// show
        if(!aj){
        // Сохранение в localStorage
        LS.push('#ds_catTL'+ $(ts).data('id'));
        localStorage.setItem('activeTab'+edate.mod, JSON.stringify(LS));
        }
    }
}

$(window).on('load', function() {
    
    
    
    $("tbody.list tr .list-sort").hover(function() {
          let group = $(this).closest('tr').data('parent');
          $('tbody.list tr').addClass('ui-state-disabled');
          $('tbody.list tr.ds_catTr'+group).removeClass('ui-state-disabled');
    });
    
 
    var fixHelperCategory = function(e, ui) {
        trStyle = "color:#1585cf!important;background-color:#fff!important;";
        // берем id текущей строки
        var id = $(ui).data('id');
        // достаем уровень вложенности данной строки
        var level = $(ui).data('level');
        level++;
        
        // берем порядковый номер текущей строки
        // thisSortNumber = $(ui).data('sort');
        $('tbody.list tr').each(function(index) {
            if($(this).data('id') == id) {
                thisSortNumber = index;
                return false;
            }
        }); 
        
        // фикс скрола
        //$('.section-category .table-wrapper').css('overflow', 'visible');
        
        // поиск ширины для жесткой записи, чтобы не разебывалось
        width = $('.simple-table').width();
        width *= 0.9;
        uiq = '<div style="width:'+width+'px;position:fixed;"><table style="width:100%;"><tr style="'+trStyle+'">'+$(ui).html()+'</tr>';
        group = 'ds_catTr'+$(ui).data('parent');
        var trCount = $('tbody.list tr').length;
        for(i = thisSortNumber+1; i < trCount; i++) {
            if(($('tbody.list tr:eq('+i+')').hasClass(group)) || (($('tbody.list tr:eq('+i+')').data('level') < level))) {
                break;
            } else {
                if(($('tbody.list tr:eq('+i+')').data('level') >= level)) {
                    uiq += '<tr style="'+trStyle+'display:'+$('tbody.list tr:eq('+i+')').css('display')+'">'+$('tbody.list tr:eq('+i+')').html()+'</tr>';
                    $('tbody.list tr:eq('+i+')').css('visibility','hidden');
                    
                }
            }
        }
        uiq += '</table></div>';
        return uiq;
    };


    $('.list').has('.list-sort').sortable({
        handle: '.list-sort',
        placeholder: 'ui-state-highlight',
        start: function(event, ui) {
            $('tr.row-drop').remove();
            ui.item.data('ou', $('.ds_catTr'+$(ui.item).data('parent')).map(function() {
                return $(this).data("sort");
            }).get().join());
            
            let id = $(ui.item).closest('tr').data('id'),
                par = $(ui.item).closest('tr').data('parent');
            $('.ds_catTr'+par).each(function( index, el) { // производим перебор элементов <tr> коллекции jQuery
                if ($(el).find('td.name>div>a.icon-squared-minus').toggleClass('icon-squared-plus icon-squared-minus')){
                    let t = $(el),
                        y = '',
                        i = t.data('id'),
                        p = t.data('level'), // от какого
                        n = t.next(),
                        l = n.data('level'); // до какого
                    while (l > p) {
                        y = n.next();
                        $('#ds_catTr'+n.data('id')).hide();
                        i = n.data('id');
                        n = y;
                        l = n.data('level');
                    }
                }
            });
        },
        
        sort: function(e) {
          //var Y = e.pageY; // положения по оси Y
          //$('.ui-sortable-helper').offset({ top: (Y-10)});
        },
        
        items: 'tr:not(.ui-state-disabled)',
        helper: fixHelperCategory,
        
        stop: function(event, ui) {
            let r = '',
                u = '',
                id = $(ui.item).data('id'); // id перемещенной tr
            $($('.ds_catTr'+id).get().reverse()).each(function( index, el) { // производим перебор элементов <tr> коллекции jQuery
                let t = $(el),
                    y = '',
                    i = t.data('id'),
                    p = t.data('level'), // от какого
                    n = t.next(),
                    l = n.data('level'); // до какого
                $('#ds_catTr'+i).detach().insertAfter('#ds_catTr'+id);
                while (l > p) {
                    y = n.next();
                    $('#ds_catTr'+n.data('id')).detach().insertAfter('#ds_catTr'+i);
                    i = n.data('id');
                    n = y;
                    l = n.data('level');
                }
            });
            $("tbody.list tr").css('visibility','visible');
        },
        update: function(event, ui) {// срабатывает если было перемещение
            let ou = ui.item.data('ou').split(','), // sort
                nu = $('.ds_catTr'+$(ui.item).data('parent')).map(function() { // id текущее положение после сортировки
                    return $(this).data("id");
                }).get().join().split(','),
                sarr = {};
            $.each(nu,function(index,value){
                sarr[value] = ou[index];
            });
            saveSort({'sort': sarr});
        }
    }).disableSelection();
});


function saveSort(data) {
    $.ajax({
        dataType: 'json',
        data: data,
        success: function(data){   
            $('tbody.list').html(data);
            let LS = JSON.parse(localStorage.getItem('activeTab'+edate.mod));
            if(LS){
                $.each(LS,function(index,value){
                    hideShowRows($(value), true);
                });
            }
        }
	});
}
    
$(document).on('click', '.ds_closeSt', function(){
    $(this).closest('tr.row-drop').remove();
    $(this).closeModal();
});
    
$('body').on('click', 'tbody td', function(event){
    // Не обрабатывайте, если был нажат какой-либо другой щелчок
    if (event.target !== this){return;}
    rowdrop(this);
});

$('body').on('click', '.ds_editSt', function(event){
    // Не обрабатывайте, если был нажат какой-либо другой щелчок
    if (event.target !== this){return;}
    rowdrop($(this).closest('td'));
});

function rowdrop(t) {
    var tr = $(t).parent(),
	row = tr.next('.row-drop'),
	rows;
	// Если нажать на специальную строку
	if (tr.hasClass('row-drop')){return;}
	// Если уже существует специальная строка
	if (row.length > 0){
		// Remove row
		row.remove();
		return;
	}
	// Удалить существующие специальные строки
	rows = tr.siblings('.row-drop');
	if (rows.length > 0){
		// Remove rows
		rows.remove();
	}
	// Add row
    catform(tr);
}


function catform(tr=false) {
    if (tr) {
        var  col = tr.children().length,
        eadd = edate.editing;
        cl = 'row-drop';
        cl2 = ' white';
        ac = 'edit&id='+tr.find('th.number').text(),
        name = tr.find('td.name span').text(),
        parent = tr.data('parent'),
        access =  tr.data('access').toString().split(',');
    }else{
        var  col = 1,
        ac = 'add',
        name = '',
        eadd = edate.add;
        cl = '',
        cl2 = '';
    }
    var content = 
    '<tr class="'+cl+'"><td colspan="'+col+'">'+
        '<form action="category.php?mod='+edate.mod+'&ac='+ac+'" method="POST">'+
            '<fieldset class="fieldset margin-top'+cl2+'"><legend class="legend">'+eadd+'</legend>'+
                '<p class="button-height block-label"><label for="input" class="label"><small>Additional information</small>'+edate.title+'</label><input type="text" name="title" id="input" class="input full-width" value="'+name+'"></p>'+
                '<p class="button-height block-label"><label for="input" class="label"><small>Additional information</small>'+edate.parent+'</label><select name="id_sec" id="ecatSel" class="select">'+edate.select+'</select></p>'+
                '<p>'+edate.access+'</p>'+
                '<table class=""><tr>'+edate.groups+'</tr></table>'+
            '</fieldset>'+
            '<div class="float-right margin-bottom">'+
                '<button type="submit" name="send" class="button glossy mid-margin-right">'+edate.save+
                    '<span class="button-icon green-gradient right-side"><span class="icon-save"></span></span>'+
                '</button>'+
				'<a href="javascript:void(0)" class="ds_closeSt button glossy margin-right">'+edate.close+'<span class="button-icon red-gradient right-side"><span class="icon-cross"></span></span></a>'+
			'</div>'+
		'</form></td>'+
	'</tr>';
    if (tr) {
        $(content).insertAfter(tr);
        $('#ecatSel option[value='+parent+']').prop('selected', true).change();
        $.each(access, function(x, v) {
            tr.next('tr').find('#ac_'+v+'').prop('checked', false).change();
        });
    }else{
        $.modal({
            title: eadd,
            content: content,
            beforeContent: '<table>',
            afterContent: '</table>',
            draggable: false,
            resizable: false,
            closeOnBlur: true,
            buttons:  false
        })
    }
}*/
</script>

<?php include_once 'template/footer.php';