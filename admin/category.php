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
    if (empty($_GET['m'])) {
        $_SESSION["message"][] = __('Some error occurred');
        redirect('/admin/');
    }
    
    $mod = trim($_GET['m']);
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
$result = '';

//$head = file_get_contents('template/header.php'); // ????
$page_title = __(getCurrMod(),false,getCurrMod());
$cat_selector = '';
$subcat_true = false;

$Register = Register::getInstance();
$Register['module'] = getCurrMod();



/*
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
*/


//$content = index($page_title);


// Обработка действий
    if (RST::method('post')) {
        
        // Сортировка
        if ($s = RST::post('sort')) {
            sorting($s);
        }
        
        // Добавление или редактирование категории
        if ($title = RST::post('title', 'string')) {
            $id = RST::post('id', 'integer');
            add_or_edit($title,$id);
        }
        
        
        
        
        
        // Действия с выбранными
        $ids = RST::post('check');
        if(is_array($ids)) {
            switch(RST::post('operation')) {
                case 'disable':
                    foreach($ids as $id)
						$DB->save(getCurrMod() . '_categories', array('id' => $id, 'visible'=>0));
					break;
                case 'enable':
                    foreach($ids as $id)
						$DB->save(getCurrMod() . '_categories', array('id' => $id, 'visible'=>1));
					break;
                case 'view_on':
                    foreach($ids as $id)
						view_home(1, $id);
					break;
                case 'view_off':
                    foreach($ids as $id)
						view_home(0, $id);
					break;
                case 'move':
                    foreach($ids as $id)
						//sort[id]: 44
						//sort[parent]: 1
						sorting(array('id' => $id, 'parent' => RST::post('option')));
						
						//print_r('k');
					break;
                case 'delete':
                    foreach($ids as $id)
						del($id);
					break;
			}
			
			// нужно сделать чтобы дейсьвие только после
			global $cat_selector;
			$all_sections = getAll_sections();
			$cats_tree = getTreeNode($all_sections);
			$result['tr'] = buildCatsList($cats_tree, $all_sections);
			$edate->select  = '<option value="0">' . __('Root category') . '</option>'.$cat_selector;
			$result['e'] = $edate;
			messege_ajx($result);
			
			
			
			
			
			
			
			
        }
    }else{
        $content = index($page_title);
        
        $pageTitle = $page_title;
        $pageNav = $page_title;
        $pageNavr = '';
    }
    
    







function view_home($val, $cid = false) {
    global $DB;
    if (getCurrMod() == 'foto' || !in_array($val, array(0, 1))) {
        $result['err'] = 'Error';
        messege_ajx($result);
    }
    
    
    if ($cid === false) {
        $id = (!empty($_GET['id'])) ? intval($_GET['id']) : 0;
        if ($id < 1) {
            $result['err'] = sprintf(__('Empty field "param"'), 'ID');
            messege_ajx($result);
        }
    } else {
        $id = $cid;
    }

    
    $childs = $DB->select(getCurrMod() . '_categories', DB_ALL, array('cond' => array('parent_id' => $id)));
    if ($childs && is_array($childs) && count($childs)) {
        foreach ($childs as $child) {
            view_home($val, $child['id']);
        }
    } 
    
    $DB->save(getCurrMod() . '_categories', array('id' => $id, 'view_on_home' => $val));
    $DB->save(getCurrMod(), array('view_on_home' => $val), array('category_id' => $id));

        
    if ($cid === false) exit;
}




















function add_or_edit($title,$id) {
    //$result = '';
    $position = RST::post('pos', 'integer');
    $parent_id = RST::post('id_sec', 'integer');
    if ($parent_id < 0) $parent_id = 0;
    
    global $DB;
    $title = $DB->escape($title);
    
    if (empty($title) || $id < 0) {
        $result['err'] = __('Empty field "title"');
        messege_ajx($result);
    }
    
    if ($id > 0) {
        $changed_cat = $DB->select(getCurrMod() . '_categories', DB_FIRST, array('cond' => array('id' => $id)));
        if (empty($changed_cat) || $id == $parent_id) {
            $result['err'] = __('Edited section not found');
            messege_ajx($result);
        }
    }
    
    if (!empty($parent_id)) {
        $target_section = $DB->select(getCurrMod() . '_categories', DB_COUNT, array('cond' => array('id' => $parent_id)));
        if ($target_section < 1) {
            $result['err'] = __('Parent section not found');
            messege_ajx($result);
        }
    }
    
    $acl_groups = ACL::get_group_info();
    
    $no_access = array();
    if ($acl_groups && is_array($acl_groups)) {
        foreach ($acl_groups as $idg => $group) {
            if (!array_key_exists($idg, RST::post('access'))) {
                $no_access[] = $idg;
            }
        }
    }
    
    /*
    $no_access = (count($no_access)) ? implode(',', $no_access) : '';
    if ($no_access !== '') $no_access = new Expr($no_access);
    */
    
    $no_access = (count($no_access) == 1 && $no_access[0] !== '') ? intval($no_access[0]) : implode(',', $no_access);
    $data = array(
        'title' => substr($_POST['title'], 0, 100), 
        'parent_id' => $parent_id,
        'no_access' => $no_access,
    );
    
    if ($id > 0) {
        $data['id'] = $id;
        $DB->save(getCurrMod() . '_categories', $data);
    }else{
        $newid = $DB->save(getCurrMod() . '_categories', $data);
        $id = $newid;
    }
    
    if (!empty($newid) || !empty($changed_cat) && $changed_cat[0]['parent_id'] != $parent_id) {
        
        
        $last = $DB->query("SELECT MAX(`position`) AS last FROM `" . $DB->getFullTableName(getCurrMod() . '_categories') . "` WHERE `parent_id` = '" . $parent_id . "' LIMIT 1");
        
        if (!empty($last[0]['last'])) {
            $in_pos = ((int)$last[0]['last'] + 1);
            $DB->save(getCurrMod() . '_categories', array('id' => $id, 'parent_id' => $parent_id, 'position' => $in_pos));
        }
    }
    
    global $cat_selector;
    
    $all_sections = getAll_sections();
    $cats_tree = getTreeNode($all_sections);
    $result['tr'] = buildCatsList($cats_tree, $all_sections);
    $edate->select  = '<option value="0">' . __('Root category') . '</option>'.$cat_selector;
    $result['e'] = $edate;
    
    messege_ajx($result);
}












function messege_ajx($result) {
    header("Content-type: application/json; charset=UTF-8");
    header("Cache-Control: must-revalidate");
    header("Pragma: no-cache");
    header("Expires: -1");
    print json_encode($result);
    exit;
}
















function del($id) {
    global $DB;
    $id = (!empty($id)) ? intval($id) : 0;
    if ($id < 1) {
        $result['err'] = sprintf(__('Empty field "param"'), 'ID');
        messege_ajx($result);
    }
    
    
    
    
    
    $childCats = OrmManager::getModelInstance(getCurrMod() . 'Categories')->getOneField('id', array('parent_id' => $id));
    $ids = '`category_id` LIKE \'%'.$id.'%\'';
    if ($childCats && is_array($childCats) && count($childCats) > 0)
        $ids .= ' OR `category_id` IN (' . implode(', ', array_unique($childCats)) . ')';
    $where = array($ids);


    $COUNT = $DB->select(getCurrMod(), DB_COUNT, array('cond' => $where));
    if ($COUNT > 0) {
        //$_SESSION['message'][] = __('Category is not empty.');
        //redirect('/admin/category.php?m=' . getCurrMod());
        $result['err'] = __('Category is not empty.');
        messege_ajx($result);
    }
    
    $childrens = $DB->select(getCurrMod() . '_categories', DB_ALL, array('cond' => array('parent_id' => $id)));
    
    if (!$childrens || !is_array($childrens) || !count($childrens)) {
        delete_category($id);
    } else {
        foreach ($childrens as $category) {
            delete_category($category['id']);
            del($category['id']);
        }
        $DB->query("DELETE FROM `" . $DB->getFullTableName(getCurrMod() . '_categories') . "` WHERE `id`='{$id}'");
    }
    //redirect('/admin/category.php?m=' . getCurrMod());
    
    
    
    
    
    
    
}















// Сортировка
function sorting($s) {
    
    //print_r($s['id']);
    //print_r($s['parent']);
    
    
    
    
    
    global $cat_selector;
    //$result = '';
    $sort = new stdClass;
    $sort->id = intval($s['id']);
    if(isset($s['parent'])) {$sort->parent = intval($s['parent']);}
    $sort->positions = preg_replace('/[^,0-9]/', '', $s['sort']);
    
    if(!empty($sort->id) && isset($sort->parent)) {
        $DB = getDB();
        if(empty($sort->positions)) {// Переместили в новую группу
        
        
        
        
            $last = $DB->query("SELECT MAX(`position`) AS last FROM `" . $DB->getFullTableName(getCurrMod() . '_categories') . "` WHERE `parent_id` = '" . $sort->parent . "' LIMIT 1");
            
            //print_r('$in_pos = '.$last[0]['last']);
            
            
            /*if (!empty($last[0]['last'])) {
                $in_pos = ((int)$last[0]['last'] + 1);
            } else {
                $in_pos = 1;
            }
            */
            $in_pos = !empty($last[0]['last']) ? ((int)$last[0]['last'] + 1) : 1;
            
            
            
            
            
            
            
            
            
                  $DB->save(getCurrMod() . '_categories', array('id' => $sort->id, 'parent_id' => $sort->parent, 'position' => $in_pos));
        }else{
            // Сортировка
            $ids = explode(',', $sort->positions);
            foreach($ids as $i=>$id) {
                $i++;
                $DB->save(getCurrMod() . '_categories', array('id' => $id, 'parent_id' => $sort->parent, 'position' => $i,));
            }
        }
            
        $all_sections = getAll_sections();
        $cats_tree = getTreeNode($all_sections);
        $result['tr'] = buildCatsList($cats_tree, $all_sections);
        $edate->select  = '<option value="0">' . __('Root category') . '</option>'.$cat_selector;
        $result['e'] = $edate;
        
        messege_ajx($result);
    }
}









function getAll_sections() {
    $DB = getDB();
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
    return $all_sections;
}









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
       
       
        $out .= '<tr id="ds_catTr' . $cat['id'] . '" data-id="' . $cat['id'] . '" data-level="' . $level . '" data-parent="' . $cat['parent_id'] . '" data-access="' . $cat['no_access'] . '" class="ds_catTr'.$cat['parent_id'].'"';
        if ($cat[parent_id]) {$out .= ' style="display: none;"';}
        $out .= '><th scope="row"><input type="checkbox" name="check" value="' . $cat['id'] . '" class="checkbox"></th>
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
            <span class="ds_action button-group children-tooltip">';
                $out .= '<a href="javascript:void(0)" data-a="edit" title="' . __('Edit') . '" class="ds_editSt111 button icon-gear"></a>';
                $out .= '<a href="javascript:void(0)" data-a="addSub" title="' . __('Add a nested category') . '" class="ds_addSub111 button icon-list-add"></a>';
                
                
                
                
                
                
                
                
                
                
                
                
                 
                $v = ($cat['visible'] == 1) ? ' green' : '';
                $out .= '<a href="javascript:void(0)" data-a="visible" title="' . __('Show/hide') . '" class="ds_actSt111 button icon-light-bulb'.$v.'"></a>';
               
               
               
               
                
                if (getCurrMod() != 'foto') {
                    $v = ($cat['view_on_home'] == 1) ? ' green' : '';
                    $out .= '<a href="javascript:void(0)" data-a="view" title="' . __('Show/hide') . '" class="button icon-home'.$v.'"></a>';
                }
                
                
                
                
                
                
                /*
                if (getCurrMod() != 'foto') {
                    if ($cat['view_on_home'] == 1) {
                        $out .=  '<a href="?ac=off_home&id='.$cat['id'].'&m='.getCurrMod().'" title="' . __('Down') . '" class="button confirm icon-home green"></a>';
                    } else {
                        $out .=  '<a href="?ac=on_home&id='.$cat['id'].'&m='.getCurrMod().'" title="' . __('Up') . '" class="button confirm icon-home"></a>';
                    }
                }
                */
                
                
                $out .= '
                
                
                <a href="javascript:void(0)" data-a="del" title="' . __('Delete') . '" class="ds_delSt111 button confirm icon-trash"></a>
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
    global $subcat_true; // true если есть хотя бы одна субкатегория из всего списка
    global $edate;
    $edate = new stdClass();
    //$result = '';
    
    //$Register = Register::getInstance();
    $DB = getDB();
    $acl_groups = ACL::get_group_info();

    $page_title .= ' - ' . __('Sections editor');
    $all_sections = getAll_sections();
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
    
    $html .= '
    
    
    <span class="button-group"> 
        <a href="' . get_url('/admin/materials_list.php?m=' . getCurrMod()) . '" class="button green-active">'. __('Materials list') .'</a> 
        <a href="' . get_url('/admin/category.php?m=' . getCurrMod()) . '" class="button green-active active">'. __('Categories') .'</a> 
        </span>
    
    
    
    
    
    
    
    
    
    <a href="javascript:void(0)" onclick="category.catForm()" class="button float-right large-margin-bottom">
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
                <td colspan="7">
                
                ' . __('Activity') . ':
                
                
                <select id="dsRunSel" class="select expandable-list white-gradient glossy">
                    
                    
                    
                    <option value="view_off">' . __('Do not display categories in the menu') . '</option>
                    <option value="view_on">' . __('Display in the categories menu') . '</option>
                    
                    
                    
                    <option value="enable">' . __('Enable') . '</option>
                    <option value="disable">' . __('Disable') . '</option>
                    
                    
                    
                    
                    <option value="activity_with_products_0">Сделать неактивными вместе с материалами</option>
                    <option value="activity_with_products_1">Сделать активными вместе с материалами</option>
                    
                    <option value="move">' . __('Move') . '</option>
                    
                    <option value="delete">Удалить выбранные категории</option>
                    <option value="deleteproduct">Удалить выбранные категории с материалами</option>
                    
                    
                    
                    
                </select>
                
                
                <a id="dsRun" href="javascript:void(0)" class="button grey-gradient glossy">' . __('Run') . '</a>
                
                
                
                
                
                
                </td>
            </tr>
        </tfoot>
        <tbody id="dsList" class="list">';
    if ($c) {
        $html .= buildCatsList($cats_tree, $all_sections);
        $edate->select  = $edate->select.$cat_selector;
    } else {
        $html .= '<tr><td colspan="7"><h2 class="thin no-margin-top">'.__('Sections not found').'</h2></td></tr>';
    }
    $html .= '</tbody></table>';
    
    return $html;
}

/*
function add() {
    global $DB;
    if (empty($_POST['title'])) redirect('/admin/category.php?m=' . getCurrMod());
    
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
 /*   if (!empty($error)) {
        $_SESSION['message'] = array_merge($_SESSION['message'], $error);
        redirect('/admin/category.php?m=' . getCurrMod());
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
        
    redirect('/admin/category.php?m=' . getCurrMod());
}
*/

/*
function edit() {
    


    if (!isset($_GET['id'])) redirect('/admin/category.php?m=' . getCurrMod());
    if (!isset($_POST['title'])) redirect('/admin/category.php?m=' . getCurrMod());
    $id = intval($_GET['id']);
    
    if ($id < 1) redirect('/admin/category.php?m=' . getCurrMod());
    
    
    global $DB;
    
    $acl_groups = ACL::get_group_info();
    
    
    $error = array();

    if (empty($_POST['title'])) $error[] = __('Empty field "title"');
    


    $parent_id = intval($_POST['id_sec']);
    $changed_cat = $DB->select(getCurrMod() . '_categories', DB_FIRST, array('cond' => array('id' => $id)));
    if (empty($changed_cat)) $error[] = __('Edited section not found');

    
    /* we must know changed parent section or not changed her. And check her  */
/*    if (!empty($parent_id) && $changed_cat[0]['parent_id'] != $parent_id) {
        $target_section = $DB->select(getCurrMod() . '_categories', DB_COUNT, array('cond' => array('id' => $parent_id)));
        if ($target_section < 1) $error[] = __('Parent section not found');
    }
    /* if errors exists */
/*    if (!empty($error)) {
        $_SESSION['message'] = array_merge($_SESSION['message'], $error);
        redirect('/admin/category.php?m=' . getCurrMod());
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
/*    $data = array(
        'id' => $id, 
        'title' => substr($_POST['title'], 0, 100), 
        'no_access' => $no_access,
    );
    if (isset($parent_id)) $data['parent_id'] = (int)$parent_id;
    $DB->save(getCurrMod() . '_categories', $data);
        

    redirect('/admin/category.php?m=' . getCurrMod());
}
*/


/*

function delete() {
    global $DB;
    $id = (!empty($_GET['id'])) ? intval($_GET['id']) : 0;
    if ($id < 1) redirect('/admin/category.php?m=' . getCurrMod());
    
    $childCats = OrmManager::getModelInstance(getCurrMod() . 'Categories')->getOneField('id', array('parent_id' => $id));
    $ids = '`category_id` LIKE \'%'.$id.'%\'';
    if ($childCats && is_array($childCats) && count($childCats) > 0)
        $ids .= ' OR `category_id` IN (' . implode(', ', array_unique($childCats)) . ')';
    $where = array($ids);


    $COUNT = $DB->select(getCurrMod(), DB_COUNT, array('cond' => $where));
    if ($COUNT > 0) {
        $_SESSION['message'][] = __('Category is not empty.');
        redirect('/admin/category.php?m=' . getCurrMod());
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
    redirect('/admin/category.php?m=' . getCurrMod());
}
*/

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
















/*

function on_home($cid = false) {
    global $DB;
    if (getCurrMod() == 'foto') redirect('/admin/category.php?m=' . getCurrMod());
    
    
    if ($cid === false) {
        $id = (!empty($_GET['id'])) ? intval($_GET['id']) : 0;
        if ($id < 1) redirect('/admin/category.php?m=' . getCurrMod());
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

        
    if ($cid === false) redirect('/admin/category.php?m=' . getCurrMod());
}



function off_home($cid = false) {
    global $DB;
    if (getCurrMod() == 'foto') redirect('/admin/category.php?m=' . getCurrMod());
    
    if ($cid === false) {
        $id = (!empty($_GET['id'])) ? intval($_GET['id']) : 0;
        if ($id < 1) redirect('/admin/category.php?m=' . getCurrMod());
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

        
    if ($cid === false) redirect('/admin/category.php?m=' . getCurrMod());
}

*/




if(isset($content)) {include_once ROOT . '/admin/template/header.php'; ?>
    <!-- Main title -->
	<hgroup id="main-title" class="thin">
		<h1><?php echo $pageTitle; ?></h1>
		<h2><?php echo date('M');?> <strong><?php echo date('j');?></strong></h2>
	</hgroup>
    <div class="with-padding">
        <?php echo $content;?>
    </div>
    <script>
        var edate = <?php echo json_encode($edate) ?>;
        $(window).on('load', function() {
            $.extend($.fn.confirm.defaults, {message: '<?php echo __('Are you sure?') ?>',confirmText: '<?php echo __('Yes') ?>',cancelText: '<?php echo __('No') ?>'});
        });
    </script>
<?php include_once 'template/footer.php';}