<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/


include_once R.'admin/inc/adm_boot.php';




$pageTitle = __('forum');


// For all popup's(edit & add). Their must be in main wrapper
$popups_content = '';



if (!isset($_GET['ac'])) $_GET['ac'] = 'index';
$permis = array('add', 'del', 'index', 'edit', 'acl');
if (!in_array($_GET['ac'], $permis)) $_GET['ac'] = 'index';

switch($_GET['ac']) {
    case 'index':
        $out = index($pageTitle);
        $content = $out[0];
        $popups_content = $out[1];
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
    case 'acl':
        $content = acl();
        break;
    default:
        $out = index();
        $content = $out[0];
        $popups_content = $out[1];
}


$pageNav = $pageTitle;
$pageNavr = '<a href="#sec" class="btn modal-trigger"><i class="mdi-content-add left"></i>' . __('Add section') . '</a>';

include_once R.'admin/template/header.php';
 ?>

<blockquote>
<?php echo __('If you delete a category, all the materials in it will be removed') ?><br /><br />

<?php echo __('Each forum should be inherited from the section') ?>
</blockquote>

<?php
echo $popups_content;
echo $content;
?>
<script>
$(document).ready(function(){
    // the "href" attribute of .modal-trigger must specify the modal ID that wants to be triggered
    $('.modal-trigger').leanModal();
    $('select').material_select();
});

</script>
<?php

$DB = getDB();

// распределяет категории в иерархическом порядке и добавляет отступы в зависимости от иерархии
function cats($cats, $id, $i = 1) {
    $newcats = array();
    $html = '';
    foreach ($cats as $cat) {
        if (isset($cat['sub']) and $cat['sub'] == $id) {
            unset($cats[$cat['id']]['sub']);
            $newcats[$cat['id']] = $cat;
            $qqq = cats($cats, $cat['id'], $i+1);
            $html .= $cat['text1'];
            for ($z = 1; $z <= $i; $z++)
                $html .= '<div class="cat-indent"> </div>';
            $html .= $cat['text2'];
            $newcats[$cat['id']] = array_merge($newcats[$cat['id']], $qqq[1]);
            $html .= $qqq[2];
        }
    }
    return array($cats, $newcats, $html);
}


/* ACl FORUM FORM */
function showForumRulesById($id_forum) {
    $not_forums_rights = array(
        'not_use_global_rights',
        'view_forums_list',
        'download_files',
        'upload_files',
        'add_polls',
    );

    
    $forum_rules = \ACL::getModuleRules('forum');

    $groups_list = \ACL::get_group_info();
    $html = '<div id="acl' . $id_forum . '" class="modal modal-fixed-footer">
                <form action="cat.php?ac=acl&id=' . $id_forum . '" method="POST" enctype="multipart/form-data">
                <div class="modal-content">
                    <h4>' . __('Permissions for forum') . '</h4>
                    <div>
                        <div class="input-field section">
                            <div class="row">
                                <input
                                       id="not_use_global_rights'.$id_forum.'"
                                       name="not_use_global_rights'.'[forum.'.$id_forum.']'.'"
                                       type="checkbox"
                                       value="1"
                                       '.((\ACL::turn(array('forum','not_use_global_rights'),false,$id_forum)) ? 'checked="checked"' : '').'
                                />
                                <label for="not_use_global_rights'. $id_forum .'">' . __('Not use global rights for this forum') . '</label>
                            </div>
                        </div>';


    foreach ($forum_rules as $title_rule => $rules_all) {
            if (in_array($title_rule,$not_forums_rights))
                continue;
            
            if (isset($rules_all['forum.'.$id_forum]))
                $rules = $rules_all['forum.'.$id_forum];
            else
                $rules = array();

              $html .= '<div class="divider"></div>
                        <div class="input-field section">
                            <h5>' . __($title_rule) . '</h5>
                            <div class="row">';
                            foreach($groups_list as $group_id => $group_info) {
                                $ch_id = $title_rule . '_' . $id_forum . '_' . $group_id;
                                $html .=
                                    '<div class="col s4">
                                        <input
                                           name="'.$title_rule.'['.$id_forum.'_'.$group_id.']'.'"
                                           type="checkbox"
                                           value="1"
                                           '.((isset($rules['groups']) && in_array($group_id,$rules['groups'])) ? 'checked="checked"' : '').'
                                           id="'.$ch_id.'"
                                        />
                                        <label for="'.$ch_id .'"><font color="'.$group_info['color'].'">'.$group_info['title'].'</font></label>
                                    </div>';
                            }
              $html .=     '</div>
                        </div>';
    }


    $html .=        '</div>
                </div>
                <div class="modal-footer">
                    <input type="submit" value="' . __('Save') . '" name="send" class="btn" />
                    <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
                </div>
                </form>
            </div>';

    return $html;
}

/* END \ACL FORUM FORM */

function index(&$page_title) {
    global $DB, $popups_content;

    $DB = getDB();
    deleteCollisions();

    $page_title = __('Forum - sections editor');

    $query = $DB->select('forum_cat', DB_ALL, array('order' => 'previev_id'));

    //cats and position selectors for ADD
    if (count($query) > 0) {
        $cat_selector = '<select name="in_cat" id="cat_secId">';
        foreach ($query as $key => $result) {
            $cat_selector .= '<option value="' . $result['id'] . '">' . h($result['title']) . '</option>';
        }
        $cat_selector .= '</select>';
    } else {
        $cat_selector = '<b>' . __('First, create a section') . '</b>';
    }

    $forums = $DB->select('forums', DB_ALL);


    //selector for subforums
    $sub_selector = '<select name="parent_forum_id">';
    $sub_selector .= '<option value=""></option>';
    if (!empty($forums)) {
        foreach($forums as $forum) {
            $sub_selector .= '<option value="' . $forum['id'] . '">' . h($forum['title']) . '</option>';
        }
    }
    $sub_selector .= '</select>';


    $html = '';



    $popups_content .= '
                    <div id="sec" class="modal modal-fixed-footer">
                        <form action="cat.php?ac=add" method="POST">
                        <div class="modal-content">
                            <h4>' . __('Add section') . '</h4>
                            <div>
                                <div class="input-field col s3">
                                    <input id="in_pos" type="text" name="in_pos" />
                                    <label for="in_pos">
                                        ' . __('Section position') . '
                                    </label>
                                    <small class="right">' . __('Numeric') . '</small>
                                </div>
                                <div class="input-field col s9">
                                    <input type="hidden" name="type" value="section" />
                                    <input id="title" type="text" name="title" />
                                    <label for="title">
                                        ' . __('Title') . '
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="submit" value="'. __('Add').'" name="send" class="btn" />
                            <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
                        </div>
                        </form>
                    </div>';




    $html .= '
        <div class="collection tree">';




    /*
    $html .= '<td align="right">
                <div align="right" class="topButtonL" id="cat_view"><input type="button" name="add" value="' . __('Create forum') . '" onClick="wiOpen(\'cat\');" /></div></td></tr></table>';
    */


    if (count($query) > 0) {
        foreach ($query as $result) {

            $html .= '
            <div class="collection-item">
                <div class="head">
                    <span class="title truncate col max-s7" title="' . h($result['title']) . '">' . h($result['title']) . '</span>
                    <span>#' . $result['id'] . '</span>
                    <div class="right-content">
                        <a href="#addForum' . $result['id'] . '" class="btn-floating green modal-trigger" title="' . __('Add') . '"><i class="mdi-content-add small"></i></a>
                        <a href="#editSec' . $result['id'] . '" class="btn-floating modal-trigger" title="' . __('Edit') . '"><i class="mdi-action-settings small"></i></a>
                        <a class="btn-floating red" title="' . __('Delete') . '" href="?ac=del&id=' . $result['id'] . '&section" onClick="return _confirm();"><i class="mdi-action-delete small"></i></a>
                    </div>
                </div>
                
                
                
                <div class="collection">';


            // Select current section
            $cat_selector_ = str_replace('selected="selected"', ' ', $cat_selector);
            $cat_selector_ = str_replace(
                'value="' . $result['id'] .'"',
                ' selected="selected" value="' . $result['id'] .'"',
                $cat_selector_
            );


            $popups_content .= '
                    <div id="addForum' . $result['id'] . '" class="modal modal-fixed-footer">
                        <form action="cat.php?ac=add" method="POST" enctype="multipart/form-data">
                        <div class="modal-content">
                            <h4>' . __('Add section') . '</h4>
                            <div>
                                <div class="input-field col s12">
                                    ' . $cat_selector_ . '
                                    <label>
                                        ' . __('Parent section') . '
                                    </label>
                                </div>
                                <div class="input-field col s3">
                                    <input id="in_pos' . $result['id'] . '" type="text" name="in_pos" />
                                    <label for="in_pos' . $result['id'] . '">
                                        ' . __('Forum position') . '
                                    </label>
                                    <small class="right">' . __('Numeric') . '</small>
                                </div>
                                <div class="input-field col s9">
                                    <input type="hidden" name="type" value="forum" />
                                    <input id="title' . $result['id'] . '" type="text" name="title" />
                                    <label for="title' . $result['id'] . '">
                                        ' . __('Title of forum') . ':
                                    </label>
                                </div>
                                <div class="input-field col s12 b30tm">
                                    <small>' . __('For which this will be sub-forum') . '</small>
                                    ' . $sub_selector . '
                                    <label>
                                        ' . __('Parent forum') . '
                                    </label>
                                </div>
                                <div class="file-field input-field col s12">
                                    <input placeholder="' . __('Icon') . '" class="file-path validate" type="text"/>
                                    <div class="btn">
                                        <span>' . __('File') . '</span>
                                        <input type="file" name="icon" />
                                    </div>
                                    <small class="right">
                                    ' . __('The desired size 16x16 px') .
                                    ' (' . __('Empty field - no icon') . 
                                    ')</small>
                                </div>
                                <div class="input-field col s12">
                                    <textarea id="description' . $result['id'] . '" name="description" class="materialize-textarea"/></textarea>
                                    <label for="description' . $result['id'] . '">
                                        ' . __('Description') . '
                                    </label>
                                </div>
                                <div class="input-field col s6">
                                    <input id="lock_passwd' . $result['id'] . '" type="text" name="lock_passwd"/>
                                    <label for="lock_passwd' . $result['id'] . '">
                                        ' . __('Lock on passwd') . '
                                    </label>
                                </div>
                                <div class="input-field col s6">
                                    <input id="lock_posts' . $result['id'] . '" type="text" name="lock_posts"/>
                                    <label for="lock_posts' . $result['id'] . '">
                                        ' . __('Lock on posts count') . '
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="submit" value="'. __('Add').'" name="send" class="btn" />
                            <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
                        </div>
                        </form>
                    </div>';




            $popups_content .= '
                    <div id="editSec' . $result['id'] . '" class="modal modal-fixed-footer">
                        <form action="cat.php?ac=edit&id=' . $result['id'] . '" method="POST">
                        <div class="modal-content">
                            <h4>' . __('Section editing') . '</h4>
                            <div>
                                <div class="input-field col s3">
                                    <input id="in_pos' . $result['id'] . '" type="text" name="in_pos" value="' . $result['previev_id'] . '"/>
                                    <label for="in_pos' . $result['id'] . '">
                                        ' . __('Section position') . '
                                    </label>
                                    <small class="right">' . __('Numeric') . '</small>
                                </div>
                                <div class="input-field col s9">
                                    <input type="hidden" name="type" value="section" />
                                    <input id="title' . $result['id'] . '" type="text" name="title" value="' . $result['title'] . '"/>
                                    <label for="title' . $result['id'] . '">
                                        ' . __('Title') . '
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="submit" value="'. __('Save').'" name="send" class="btn" />
                            <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
                        </div>
                        </form>
                    </div>';



            $queryCat = $DB->query("
                SELECT a.*, COUNT(b.`id`) as cnt FROM `" . $DB->getFullTableName('forums') . "` a
                LEFT JOIN `" . $DB->getFullTableName('themes') . "` b ON b.`id_forum` = a.`id`
                WHERE a.`in_cat` = '" . $result['id'] . "' GROUP BY a.`id` ORDER BY a.`pos`");

            if (count($queryCat) > 0) {
                $cats = array();
                foreach ($queryCat as $cat) {

                    /* ACl FORUM FORM */
                    $popups_content .= showForumRulesById($cat['id']);


                    //cat selector and position selector for EDIT FRORUMS
                    $cat_selector = '<select name="in_cat" id="cat_secId">';
                    foreach ($query as $key => $category) {
                        if ($cat['in_cat'] == $category['id']) {
                            $cat_selector .= '<option value="' . $category['id'] . '" selected="selected">' . $category['title'] . '</option>';
                        } else {
                            $cat_selector .= '<option value="' . $category['id'] . '">' . $category['title'] . '</option>';
                        }
                    }
                    $cat_selector .= '</select>';



                    //selector for subforums
                    $sub_selector = '<select name="parent_forum_id">';
                    $sub_selector .= '<option value=""></option>';
                    if (!empty($forums)) {
                        foreach($forums as $forum) {
                            if ($cat['id'] == $forum['id']) continue;
                            $selected = ($cat['parent_forum_id'] == $forum['id']) ? 'selected="selected"' : '';
                            $sub_selector .= '<option value="' . $forum['id'] . '" ' . $selected . '>'
                            . $forum['title'] . '</option>';
                        }
                    }
                    $sub_selector .= '</select>';

                    if (is_file(ROOT.'/data/img/forum_icon_'.$cat['id'].'.jpg')) {
                        $img = get_url('/data/img/forum_icon_'.$cat['id'].'.jpg');
                    } else {
                        $img = get_url('/template/'.getTemplate().'/img/guest.png');
                    }

                    $cats[$cat['id']]['id'] = $cat['id'];
                    $cats[$cat['id']]['text1'] = '
                            <div class="collection-item">
                                <span class="title truncate col max-s7" title="' . h($cat['title']) . '">';
                    
                    $cats[$cat['id']]['text2'] = '<img class="left" src="'.$img.'">' . h($cat['title']) .
                               '</span>
                                <span>#' . $cat['id'] . '</span>
                                <span><i class="mdi-content-content-copy tiny"></i>' . $cat['cnt'] . '</span>
                                <div class="right">
                                    <a href="#acl' . $cat['id'] . '" class="btn-floating modal-trigger" title="' . __('ACL') . '"><i class="mdi-action-verified-user small"></i></a>
                                    <a href="#editForum' . $cat['id'] . '" class="btn-floating modal-trigger" title="' . __('Edit') . '"><i class="mdi-action-settings small"></i></a>
                                    <a class="btn-floating red" title="' . __('Delete') . '" href="?ac=del&id=' . $cat['id'] . '" onClick="return _confirm();"><i class="mdi-action-delete small"></i></a>
                                </div>
                            </div>';
                    if (!empty($cat['parent_forum_id']))
                        $cats[$cat['id']]['sub'] = $cat['parent_forum_id'];



                    /* EDIT FORUM FORM */
                    $popups_content .= '
                    <div id="editForum' . $cat['id'] . '" class="modal modal-fixed-footer">
                        <form action="cat.php?ac=edit&id=' . $cat['id'] . '" method="POST" enctype="multipart/form-data">
                        <div class="modal-content">
                            <h4>' . __('Editing forum') . '</h4>
                            <div>
                                <div class="input-field col s12">
                                    ' . $cat_selector_ . '
                                    <label>
                                        ' . __('Parent section') . '
                                    </label>
                                </div>
                                <div class="input-field col s3">
                                    <input id="in_pos' . $cat['id'] . '" type="text" name="in_pos" value="' . $cat['pos'] . '" />
                                    <label for="in_pos' . $cat['id'] . '">
                                        ' . __('Forum position') . '
                                    </label>
                                    <small class="right">' . __('Numeric') . '</small>
                                </div>
                                <div class="input-field col s9">
                                    <input type="hidden" name="type" value="forum" />
                                    <input id="title' . $cat['id'] . '" type="text" name="title" value="' . $cat['title'] . '" />
                                    <label for="title' . $cat['id'] . '">
                                        ' . __('Title of forum') . ':
                                    </label>
                                </div>
                                <div class="input-field col s12 b30tm">
                                    <small>' . __('For which this will be sub-forum') . '</small>
                                    ' . $sub_selector . '
                                    <label>
                                        ' . __('Parent forum') . '
                                    </label>
                                </div>
                                <div class="file-field input-field col s12">
                                    <input placeholder="' . __('Icon') . '" class="file-path validate" type="text" value="'.basename($img).'"/>
                                    <div class="btn">
                                        <span>' . __('File') . '</span>
                                        <input type="file" name="icon" />
                                    </div>
                                    <small class="right">
                                    ' . __('The desired size 16x16 px') .
                                    ' (' . __('Empty field - no icon') . 
                                    ')</small>
                                </div>
                                <div class="input-field col s12">
                                    <textarea id="description' . $cat['id'] . '" name="description" class="materialize-textarea"/>' . $cat['description'] . '</textarea>
                                    <label for="description' . $cat['id'] . '">
                                        ' . __('Description') . '
                                    </label>
                                </div>
                                <div class="input-field col s6">
                                    <input id="lock_passwd' . $cat['id'] . '" type="text" name="lock_passwd" value="' . $cat['lock_passwd'] . '" />
                                    <label for="lock_passwd' . $cat['id'] . '">
                                        ' . __('Lock on passwd') . '
                                    </label>
                                </div>
                                <div class="input-field col s6">
                                    <input id="lock_posts' . $cat['id'] . '" type="text" name="lock_posts" value="' . $cat['lock_posts'] . '" />
                                    <label for="lock_posts' . $cat['id'] . '">
                                        ' . __('Lock on posts count') . '
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="submit" value="'. __('Save').'" name="send" class="btn" />
                            <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
                        </div>
                        </form>
                    </div>';
                    /* END EDIT FORUM FORM */

                }
                $newcats = array();
                foreach ($cats as $cat) {
                    if (!isset($cat['sub'])) {
                        $newcats[$cat['id']] = $cat;
                        $qqq = cats($cats, $cat['id']);
                        $html .= $cat['text1'].$cat['text2'];
                        $newcats[$cat['id']] = array_merge($newcats[$cat['id']], $qqq[1]);
                        $html .= $qqq[2];
                    }
                }
            } else {
                $html .= '<div class="collection-item"><div class="title">' . __('Empty') . '</div></div>';
            }

            $html .= '
                </div>
            </div>';

        }

    } else {
        $html .= __('While empty');
    }
    
    $html .= '
        </div>';

    return array($html,$popups_content);
}





function edit() {
    global $DB, $popups_content;

    $DB = getDB();

    if (!isset($_POST['title']) || !isset($_POST['type']) || empty($_GET['id'])) {
        redirect('/admin/forum/cat.php');
    }
    if ($_POST['type'] == 'forum' &&
    (!isset($_POST['in_cat']) || !isset($_POST['description']) || !isset($_FILES['icon']))) {
        redirect('/admin/forum/cat.php');
    }
    $id = (int)$_GET['id'];
    if ($id < 1) redirect('/admin/forum/cat.php');
    if (!isset($_POST['in_pos'])) redirect('/admin/forum/cat.php');
    $in_pos = (int)$_POST['in_pos'];
    if ($in_pos < 1)  redirect('/admin/forum/cat.php');
    
    $error = array();
    $title = $_POST['title'];
    if (mb_strlen($title) > 200) $error[] = __('Title more than 200 symbol');



    if ($_POST['type'] == 'forum') {
        $in_cat = (int)$_POST['in_cat'];
        $description = $_POST['description'];
        if (!empty($_FILES['icon']['name'])) {
            if ($_FILES['icon']['size'] > 102400) $error[] = __('Max icon size 100Kb');
            if (!isImageFile($_FILES['icon'])) $error[] = __('Wrong icon format');
            if (!empty($error)) {
                $_SESSION['message'] = array_merge($_SESSION['message'], $error);
                redirect('/admin/forum/cat.php');
            }
        }



        // Lock forum
        $lock_passwd = '';
        $lock_posts = 0;
        if (!empty($_POST['lock_passwd'])) {
            $lock_passwd = $_POST['lock_passwd'];
            if (mb_strlen($lock_passwd) > 100) $error[] = __('Forum passwd more than 100 sym.');
        }
        if (!empty($_POST['lock_posts'])) {
            $lock_posts = $_POST['lock_posts'];
            if (mb_strlen($lock_posts) > 100) $error[] = __('Posts count must be numeric');
        }



        //if isset errors
        if (!empty($error)) {
            $_SESSION['message'] = array_merge($_SESSION['message'], $error);
            redirect('/admin/forum/cat.php');
        }

        //busy position
        $busy = $DB->select('forums', DB_COUNT, array('cond' => array('pos' => $in_pos, 'in_cat' => $in_cat)));
        if ($busy > 0) {
            $DB->query("UPDATE `" . $DB->getFullTableName('forums') . "` SET `pos` = `pos` + 1 WHERE `pos` >= '" . $in_pos . "'");
        }
        //default position ON BOTTOM
        if ($in_pos < 1) {
            $last = $DB->query("SELECT MAX(`pos`) AS last FROM `" . $DB->getFullTableName('forums') . "` WHERE `in_cat` = '" . $in_cat . "' LIMIT 1");
            if (!empty($last[0]['last'])) {
                $in_pos = ((int)$last[0]['last'] + 1);
            } else {
                $in_pos = 1;
            }
        }


        $parent_forum_id = (int)$_POST['parent_forum_id'];
        $parent_forum_id = (!empty($parent_forum_id)) ? $parent_forum_id : '';

        //if allright - saving data
        $query = $DB->save('forums', array(
            'id' => $id,
            'description' => $description,
            'title' => $title,
            'in_cat' => $in_cat,
            'pos' => $in_pos,
            'parent_forum_id' => $parent_forum_id,
            'lock_passwd' => $lock_passwd,
            'lock_posts' => $lock_posts,
        ));
        if ($query) {
            if (move_uploaded_file($_FILES['icon']['tmp_name'], ROOT . '/data/img/forum_icon_' . $id . '.jpg')) {
                chmod(ROOT . '/data/img/forum_icon_' . $id . '.jpg', 0755);
            }
        }


    } else if ($_POST['type'] == 'section') {

        //if isset errors
        if (!empty($error)) {
            $_SESSION['message'] = array_merge($_SESSION['message'], $error);
            redirect('/admin/forum/cat.php');
        }

        //busy position
        $busy = $DB->select('forum_cat', DB_COUNT, array('cond' => array('previev_id' => $in_pos)));
        if ($busy > 0) {
            $DB->query("UPDATE `" . $DB->getFullTableName('forum_cat') . "` SET `previev_id` = `previev_id` + 1 WHERE `previev_id` >= '" . $in_pos . "'");
        }
        //default position ON BOTTOM
        if ($in_pos < 1) {
            $last = $DB->query("SELECT MAX(`previev_id`) AS last FROM `" . $DB->getFullTableName('forum_cat') . "` LIMIT 1");
            if (!empty($last[0]['last'])) {
                $in_pos = ((int)$last[0]['last'] + 1);
            } else {
                $in_pos = 1;
            }
        }

        $DB->save('forum_cat', array(
            'id' => $id,
            'title' => $title,
            'previev_id' => $in_pos,
        ));
    }
    redirect('/admin/forum/cat.php');
}




function acl() {
    $_SESSION['message'][] = __('Some error occurred');
    if (empty($_GET['id'])) redirect('/admin/forum/cat.php');
    $id_forum = (int)$_GET['id'];
    if ($id_forum < 1) redirect('/admin/forum/cat.php');
    array_pop($_SESSION['message']);


    
    $forum_rules = \ACL::getModuleRules('forum');
    $groups_list = \ACL::get_group_info();

    $new_acl_rules = $forum_rules;
    foreach ($forum_rules as $title_rule => $rules) {
        // На всякий случай удаляем повторяющиеся значения
        if (isset($new_acl_rules[$title_rule]['forum.'.$id_forum]['groups']))
            $new_acl_rules[$title_rule]['forum.'.$id_forum]['groups'] = array_unique($new_acl_rules[$title_rule]['forum.'.$id_forum]['groups']);

        foreach ($groups_list as $group_id => $group_info) {
            // Меняем права форума
            if (!empty($_POST[$title_rule][$id_forum . '_' . $group_id])) {
            // Выдаем право
                // Если еще нет разрешений, создаем список разрешенных групп
                if (!isset($new_acl_rules[$title_rule]['forum.'.$id_forum]))
                    $new_acl_rules[$title_rule]['forum.'.$id_forum] = array();
                if (!isset($new_acl_rules[$title_rule]['forum.'.$id_forum]['groups']))
                    $new_acl_rules[$title_rule]['forum.'.$id_forum]['groups'] = array();

                // Если в существующих разрешениях еще нет разрешения для этой группы, добавляем его
                if (!in_array($group_id, $new_acl_rules[$title_rule]['forum.'.$id_forum]['groups'])) {
                    $new_acl_rules[$title_rule]['forum.'.$id_forum]['groups'][] = $group_id;
                }
            // Забираем право
            } else {
                // Если список разрешенных групп для права существует и группа для запрета в него входит, то удаляем её из этого списка
                if (isset($new_acl_rules[$title_rule]['forum.'.$id_forum])) {
                    if (isset($new_acl_rules[$title_rule]['forum.'.$id_forum]['groups']) && ($offkey = array_search($group_id, $new_acl_rules[$title_rule]['forum.'.$id_forum]['groups'])) !== false)
                        unset($new_acl_rules[$title_rule]['forum.'.$id_forum]['groups'][$offkey]);
                    // Если список юзеров пустой - удаляем список
                    if (empty($new_acl_rules[$title_rule]['forum.'.$id_forum]['users']))
                        unset($new_acl_rules[$title_rule]['forum.'.$id_forum]['users']);
                    // Если список групп пустой - удаляем список
                    if (empty($new_acl_rules[$title_rule]['forum.'.$id_forum]['groups']))
                        unset($new_acl_rules[$title_rule]['forum.'.$id_forum]['groups']);
                    // Если массив с правами форума пустой - удаляем список прав форума
                    if (empty($new_acl_rules[$title_rule]['forum.'.$id_forum]))
                        unset($new_acl_rules[$title_rule]['forum.'.$id_forum]);
                }

            }
        }
    }

    /* Список независящих от основых прав групп форумов */
    // На всякий случай убираем повторяющиеся id
    $new_acl_rules['not_use_global_rights'] = array_unique($new_acl_rules['not_use_global_rights']);
    // Если отправлено непустое значение - добавлем форум в список независящих от глобальных прав
    if (!empty($_POST['not_use_global_rights']['forum.'.$id_forum])) {
        if (!in_array($id_forum,$new_acl_rules['not_use_global_rights']))
            $new_acl_rules['not_use_global_rights'][] = $id_forum;
    // Если пустое
    } else {
        if (($offkey = array_search($id_forum, $new_acl_rules['not_use_global_rights'])) !== false)
            unset($new_acl_rules['not_use_global_rights'][$offkey]);
    }


    \ACL::save_rules($new_acl_rules,'/data/acl/forum.php');


    redirect('/admin/forum/cat.php');
}





function add() {
    global $DB, $popups_content;

    $DB = getDB();

    if (empty($_POST['type'])) redirect('/admin/forum/cat.php');
    if (!isset($_POST['title'])) redirect('/admin/forum/cat.php');
    if (!isset($_POST['in_pos'])) redirect('/admin/forum/cat.php');

    $in_pos = (int)$_POST['in_pos'];
    if ($_POST['type'] == 'forum' && (!isset($_FILES['icon']) || !isset($_POST['in_cat']))) redirect('/admin/forum_cat.php');
    $title = $_POST['title'];
    $error = array();
    if (empty($title)) $error[] = __('Empty field "title"');


    if ($_POST['type'] == 'section') {
        if (mb_strlen($title) > 200) $error[] = __('Title more than 200 symbol');
        //if isset errors
        if (!empty($error)) {
            $_SESSION['message'] = array_merge($_SESSION['message'], $error);
            redirect('/admin/forum/cat.php');
        }

        //busy position
        $busy = $DB->select('forum_cat', DB_COUNT, array('cond' => array('previev_id' => $in_pos)));
        if ($busy > 0) {
            $DB->query("UPDATE `" . $DB->getFullTableName('forum_cat') . "` SET `previev_id` = `previev_id` + 1 WHERE `previev_id` >= '" . $in_pos . "'");
        }
        //default position ON BOTTOM
        if ($in_pos < 1) {
            $last = $DB->query("SELECT MAX(`previev_id`) AS last FROM `" . $DB->getFullTableName('forum_cat') . "` LIMIT 1");
            if (!empty($last[0]['last'])) {
                $in_pos = ((int)$last[0]['last'] + 1);
            } else {
                $in_pos = 1;
            }
        }
        $DB->save('forum_cat', array('title' => $title, 'previev_id' => $in_pos));


    } elseif ($_POST['type'] == 'forum') {
        $in_cat = (int)$_POST['in_cat'];
        if (!empty($_FILES['icon']['name'])) {
            if ($_FILES['icon']['size'] > 102400) $error[] = __('Max icon size 100Kb');
            if (!isImageFile($_FILES['icon'])) $error[] = __('Wrong icon format');
        }


        // Lock forum
        $lock_passwd = '';
        $lock_posts = 0;
        if (!empty($_POST['lock_passwd'])) {
            $lock_passwd = $_POST['lock_passwd'];
            if (mb_strlen($lock_passwd) > 100) $error[] = __('Forum passwd more than 100 sym.');
        }
        if (!empty($_POST['lock_posts'])) {
            $lock_posts = $_POST['lock_posts'];
            if (mb_strlen($lock_posts) > 100) $error[] = __('Posts count must be numeric');
        }


        if (!empty($error)) {
            $_SESSION['message'] = array_merge($_SESSION['message'], $error);
            redirect('/admin/forum/cat.php');
        }

        //busy position
        $busy = $DB->select('forums', DB_COUNT, array('cond' => array('pos' => $in_pos, 'in_cat' => $in_cat)));
        if ($busy > 0) {
            $DB->query("UPDATE `" . $DB->getFullTableName('forums') . "` SET `pos` = `pos` + 1 WHERE `pos` >= '" . $in_pos . "'");
        }
        //default position ON BOTTOM
        if ($in_pos < 1) {
            $last = $DB->query("SELECT MAX(`pos`) AS last FROM `" . $DB->getFullTableName('forums') . "` WHERE `in_cat` = '" . $in_cat . "' LIMIT 1");
            if (!empty($last[0]['last'])) {
                $in_pos = ((int)$last[0]['last'] + 1);
            } else {
                $in_pos = 1;
            }
        }

        $parent_forum_id = (int)$_POST['parent_forum_id'];
        $parent_forum_id = (!empty($parent_forum_id)) ? $parent_forum_id : '';

        $description = $_POST['description'];
        $id = $DB->save('forums', array(
            'description' => $description,
            'title' => $title,
            'in_cat' => $in_cat,
            'pos' => $in_pos,
            'parent_forum_id' => $parent_forum_id,
            'lock_passwd' => $lock_passwd,
            'lock_posts' => $lock_posts,
        ));

        if (empty($id)) {
            $_SESSION['message'][] = __('Some error occurred');
            redirect('/admin/forum/cat.php');
        }

        if (!empty($_FILES['icon']['name'])) {
            if (move_uploaded_file($_FILES['icon']['tmp_name'], ROOT . '/data/img/forum_icon_' . $id . '.jpg')) {
                chmod(ROOT . '/data/img/forum_icon_' . $id . '.jpg', 0755);
            }
        }
    }
    redirect('/admin/forum/cat.php');

}





function delete() {
    global $DB, $popups_content;

    $DB = getDB();

    if (empty($_GET['id']) || !is_numeric($_GET['id']))  header ('Location: /');
    $id = (int)$_GET['id'];
    if ($id < 1) redirect('/admin/forum/cat.php');

    if (!isset($_GET['section'])) {
        $sql = $DB->select('themes', DB_ALL, array('cond' => array('id_forum' => $id)));
        if (count($sql) > 0) {
            foreach ($sql as $result) {
                delete_theme($result['id']);
            }
        }
        $DB->query("DELETE FROM `" . $DB->getFullTableName('forums') . "` WHERE `id`='{$id}'");
        if (file_exists(ROOT . '/data/img/forum_icon_' . $id . '.jpg'))
            unlink(ROOT . '/data/img/forum_icon_' . $id . '.jpg');
    } else {
        $sql = $DB->select('forums', DB_ALL, array('cond' => array('in_cat' => $id)));
        if (count($sql) > 0) {
            foreach ($sql as $_result) {
                $sql = $DB->select('themes', DB_ALL, array('cond' => array('id_forum' => $_result['id'])));
                if (count($sql) > 0) {
                    foreach ($sql as $result) {
                        delete_theme($result['id']);
                    }
                }
                if (file_exists(ROOT . '/data/img/forum_icon_' . $_result['id'] . '.jpg'))
                    unlink(ROOT . '/data/img/forum_icon_' . $_result['id'] . '.jpg');
            }
        }
        $DB->query("DELETE FROM `" . $DB->getFullTableName('forums') . "` WHERE `in_cat`='{$id}'");
        $DB->query("DELETE FROM `" . $DB->getFullTableName('forum_cat') . "` WHERE `id`='{$id}'");
    }
    redirect('/admin/forum/cat.php');
}

// Функция удаляет тему; ID темы передается методом GET
function delete_theme($id_theme) {
    global $DB, $popups_content;

    $DB = getDB();

    // Если не передан ID темы, которую надо удалить
    if (empty($id_theme)) {
        redirect('/admin/forum/cat.php');
    }
    $id_theme = (int)$id_theme;
    if ( $id_theme < 1 ) {
        redirect('/admin/forum/cat.php');
    }

    // delete colision ( this is paranoia )
    $DB->query("DELETE FROM `" . $DB->getFullTableName('themes') . "` WHERE id NOT IN (SELECT DISTINCT id_theme FROM `" . $DB->getFullTableName('posts') . "`)");
    $DB->query("DELETE FROM `" . $DB->getFullTableName('posts') . "` WHERE id_theme NOT IN (SELECT id FROM `" . $DB->getFullTableName('themes') . "`)");



    // Сперва мы должны удалить все сообщения (посты) темы;
    // начнем с того, что удалим файлы вложений
    $res = $DB->select('posts', DB_ALL, array('cond' => array('id_theme' => $id_theme)));
    if (count($res) > 0) {
        foreach ($res as $file) {
            // Удаляем файл, если он есть
            $attach_files = $DB->select('forum_attaches', DB_ALL, array('cond' => array('post_id' => $file['id'])));
            if (count($attach_files) > 0) {
                foreach ($attach_files as $attach_file) {
                    if (file_exists(ROOT . '/data/files/forum/' . $attach_file['filename'])) {
                        if (@unlink(ROOT . '/data/files/forum/' . $attach_file['filename'])) {
                            $DB->query("DELETE FROM `" . $DB->getFullTableName('forum_attaches') . "` WHERE `id`='" . $attach_file['id'] . "'");
                        }
                    }
                }
            }
            // заодно обновляем таблицу TABLE_USERS - надо обновить поле posts (кол-во сообщений)
            if ( $file['id_author'] ) {
                $DB->query("UPDATE `" . $DB->getFullTableName('users') . "` SET `posts` = `posts` - 1 WHERE `id` = '" . $file['id_author'] . "'");
            }
        }
    }


    $attach_files = $DB->select('forum_attaches', DB_ALL, array('cond' => array('theme_id' => $id_theme)));
    if (count($attach_files) > 0) {
        foreach ($attach_files as $attach_file) {
            if (file_exists(ROOT . '/data/files/forum/' . $attach_file['filename'])) {
                if (@unlink(ROOT . '/data/files/forum/' . $attach_file['filename'])) {
                    $DB->query("DELETE FROM `" . $DB->getFullTableName('forum_attaches') . "` WHERE `id`='" . $attach_file['id'] . "'");
                }
            }
        }
    }

    //we must know id_forum
    $theme = $DB->select('themes', DB_FIRST, array('cond' => array('id' => $id_theme)));


    //delete posts and theme
    $p_res = $DB->query("DELETE FROM `" . $DB->getFullTableName('posts') . "` WHERE `id_theme` = '" . $id_theme . "'");
    $t_res = $DB->query("DELETE FROM `" . $DB->getFullTableName('themes') . "` WHERE `id` = '" . $id_theme . "'");

    if (!empty($theme[0]['id_author'])) {
        // Обновляем таблицу TABLE_USERS - надо обновить поле themes
        $u_res = $DB->query("UPDATE `" . $DB->getFullTableName('users') . "` SET `themes` = `themes` - 1
                WHERE `id` = '" . $theme[0]['id_author'] . "'");
    }
    //clean cache
    $Cache = new \Cache;
    $Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme,));
    $Cache->clean(CACHE_MATCHING_TAG, array('module_forum', 'action_index'));
}


//delete "0" values from forums pos AND forums_cat previev_id
function deleteCollisions() {
    global $DB, $popups_content;

    $DB = getDB();

    $categories_err = $DB->select('forum_cat', DB_COUNT, array('cond' => array('previev_id' => 0)));
    $forums_err = $DB->select('forums', DB_COUNT, array('cond' => array('pos' => 0)));
    if ($categories_err > 0 || $forums_err > 0) {
        $categories = $DB->select('forum_cat', DB_ALL);
        if (count($categories) > 0) {
            foreach ($categories as $cat_key => $cat) {
                $forums = $DB->select('forums', DB_ALL, array('cond' => array('in_cat' => $cat['id'])));
                if (count($forums) > 0) {
                    foreach ($forums as $key => $forum) {
                        $DB->save('forums', array(
                            'id' => $forum['id'],
                            'pos' => ($key + 1),
                        ));
                    }
                }
                if ((int)$cat['previev_id'] < 1) {
                    $DB->save('forum_cat', array(
                        'id' => $cat['id'],
                        'previev_id' => ($cat_key + 1),
                    ));
                }
            }
        }
    }
    return;
}

include_once R.'admin/template/footer.php';
?>