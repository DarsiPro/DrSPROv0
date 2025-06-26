<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/


include_once R.'admin/inc/adm_boot.php';


$pageTitle = __('Users list');


if ( !isset( $_GET['ac'] ) ) $_GET['ac'] = 'index';
$actions = array( 'index',
                    'ank',
                    'del',
                    'save');

if ( !in_array( $_GET['ac'], $actions ) ) $_GET['ac'] = 'index';

switch ( $_GET['ac'] )
{
    case 'ank':
        $content = editAnk($pageTitle);
        break;
    case 'save':
        $content = saveAnk();
        break;
    default:
        $content = index($pageTitle); // главная страница
}


$pageNav = $pageTitle;
$pageNavr = '<a href="list.php">' . __('Users list') . '</a>';


include_once R.'admin/template/header.php';
echo $content;
include_once R.'admin/template/footer.php';



function index(&$page_title) {

    $DB = getDB();
    
    $page_title = __('Users list');
    $order = '';
    $limit = 30;
    $content = '';

    if (!empty($_GET['cond'])) {
        $permision_cond = array('name', 'email', 'status', 'puttime');
        if (!in_array($_GET['cond'], $permision_cond)) $_GET['cond'] = 'puttime';

        $order = (!empty($_GET['value']) && $_GET['value'] == '1') ? ' DESC' : ' ASC';
        $order = 'ORDER BY ' . $_GET['cond'] . $order;
    }

    if (!empty($_POST['search'])) $str_search = "WHERE `name` LIKE '%{$_POST['search']}%'";
    else $str_search = '';
    $count = $DB->query("SELECT COUNT(*) as cnt FROM `" . $DB->getFullTableName('users') . "` {$str_search} {$order}");
    $total = (!empty($count[0]['cnt'])) ? $count[0]['cnt'] : 0;
    list($pages, $page) = pagination($total, $limit, '/admin/users/list.php');
    $start = ($page - 1) * $limit;


    $sql = "SELECT * FROM `" . $DB->getFullTableName('users') . "` {$str_search} {$order} LIMIT {$start}, {$limit}";
    $query = $DB->query($sql);

    $nick = '<th scope="col"><a class="btn-flat" href="'.((!empty($_GET['cond']) && $_GET['cond'] == 'name' && ($_name = true) && $_GET['value'] == 1) ? '?cond=name&value=0' : '?cond=name&value=1') . '">'.(isset($_name) ? $_GET['value'] == 1 ? '<i class="left mdi-hardware-keyboard-arrow-down"></i>' : '<i class="left mdi-hardware-keyboard-arrow-up"></i>' : '') . __('Name') . '</a></th>';
    $email = '<th scope="col"><a class="btn-flat" href="'.((!empty($_GET['cond']) && $_GET['cond'] == 'email' && ($_email = true) && $_GET['value'] == 1) ? '?cond=email&value=0' : '?cond=email&value=1') . '">'.(isset($_email) ? $_GET['value'] == 1 ? '<i class="left mdi-hardware-keyboard-arrow-down"></i>' : '<i class="left mdi-hardware-keyboard-arrow-up"></i>' : '') . __('email') . '</a></th>';
    $puttime = '<th scope="col"><a class="btn-flat" href="'.((!empty($_GET['cond']) && $_GET['cond'] == 'puttime' && ($_puttime = true) && $_GET['value'] == 1) ? '?cond=puttime&value=0' : '?cond=puttime&value=1') . '">'.(isset($_puttime) ? $_GET['value'] == 1 ? '<i class="left mdi-hardware-keyboard-arrow-down"></i>' : '<i class="left mdi-hardware-keyboard-arrow-up"></i>' : '') . __('Registation date') . '</a></th>';
    $status = '<th scope="col"><a class="btn-flat" href="'.((!empty($_GET['cond']) && $_GET['cond'] == 'status' && ($_status = true) && $_GET['value'] == 1) ? '?cond=status&value=0' : '?cond=status&value=1') . '">'.(isset($_status) ? $_GET['value'] == 1 ? '<i class="left mdi-hardware-keyboard-arrow-down"></i>' : '<i class="left mdi-hardware-keyboard-arrow-up"></i>' : '') . __('status') . '</a></th>';

    $pages = '<th scope="col">'. $pages .'</th>';

    $content .= "<table class=\"simple-table\">
                    <thead>
                        <tr>
                            {$pages}
                            {$nick}
                            {$status}
                            {$email}
                            {$puttime}
                            <td>111</td>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td colspan=\"6\">Table footer</td>
                        </tr>
                    </tfoot>
                    <tbody>";

    foreach ($query as $result) {
        $status_info = \ACL::get_group($result['status']);
        $status = $status_info['title'];
        $color = (!empty($status_info['color'])) ? $status_info['color'] : '';
        $content .= "<tr>
                
                <td class=\"collection-item avatar\"><img src=\"".getAvatar($result['id'], $result['email'])."\" class=\"circle\"></td>
                    
                <td><a href=\"?ac=ank&id={$result['id']}\">{$result['name']}</a></td>
                    
                    
                    
                    
                    
                        <td><font color=\"{$color}\">{$status}</font></td>
                        
                        
                        
                        <td>{$result['email']}</td>
                        
                        <td><small>{$result['puttime']}</small></td>
                    
                        <td><a class=\"secondary-content\" href='?ac=ank&id={$result['id']}'><i class=\"mdi-image-edit small\"></i></a></td>
                        
                        
                        
                        
                        
                        
                 </tr>";
    }
    $content .= '</tr>
                </tbody>
                </table>';

    $content .= '<form method="POST" action="?ac=index">
                    <div class="input-field col s6">
                        <input type="text" name="search" />
                    </div>
                    <div class="input-field col s6">
                        <input class="btn left" type="submit" name="send" value="' . __('Search') . '" />
                    </div>
                </form>
    
    

	

    
    
    
    ';

    return $content;

}

//*****************************************************************************************************************
//*****************************************************************************************************************

function editAnk(&$page_title) {

    $DB = getDB();
    
    if (!is_numeric($_GET['id'])) redirect('/admin/users/list.php');

    $page_title = __('Editing profile');
    $content = '';
    $statuses = \ACL::get_group_info();
    $query = $DB->select('users', DB_FIRST, array('cond' => array('id' => $_GET['id'])));

    if (empty($query)) return '<span class="red-text">' . __('Can not find user') . '</span>';

    
    foreach ($query[0] as $key => $value) {
        $$key = (!empty($_SESSION['edit_ank'][$key])) ? $_SESSION['edit_ank'][$key] : $value;
    }
    unset($_SESSION['edit_ank']);

    $page_title = sprintf(__('Editing profile by user'), h($name));

    $mpol = (!empty($query[0]['pol']) && $query[0]['pol'] === 'm') ? 'checked="checked"' : '';
    $fpol = (!empty($query[0]['pol']) && $query[0]['pol'] === 'f') ? 'checked="checked"' : '';


    $content .= '
    <script type="text/javascript">
    $(document).ready(function() {
        $(\'select\').material_select();
    });
    </script>';



    $content .= '
    111<form action="?ac=save&id=' . $_GET['id'] . '" method="POST">
        <div class="row">
            <div>
                <div class="input-field col s12">
                    <input id="login" type="text" name="login" value="' . h($name) .'" />
                    <label for="login">
                        ' . __('Login') . '
                    </label>
                </div>
                <div class="input-field col s12">
                    <input id="full_name" type="text" name="full_name" value="' . h($full_name) .'" />
                    <label for="full_name">
                        ' . __('full_name') . '
                    </label>
                </div>
                <div class="input-field col s12">
                    <input id="state" type="text" name="state" value="' . h($state) .'" />
                    <label for="state">
                        ' . __('Rank') . '
                    </label>
                </div>
                <div class="input-field col s12">
                    <input id="passw" type="text" name="passw" value="" />
                    <label for="passw">
                        ' . __('password') . '
                    </label>
                </div>
                <div class="input-field col s12">
                    <input id="email" type="text" name="email" value="' . h($email) .'" />
                    <label for="email">
                        ' . __('email') . '
                    </label>
                </div>
                <div class="input-field col s12">
                    <input id="url" type="text" name="url" value="' . h($url) .'" />
                    <label for="url">
                        ' . __('url') . '
                    </label>
                </div>
                <div class="input-field col s12 row">
                    <label style="position: static;">
                        ' . __('pol') . '
                    </label>
                    <p>
                        <input type="radio" name="pol" value="m" '.$mpol.' id="polm" /><label for="polm">' . __('m') . '</label>
                        <input type="radio" name="pol" value="f" '.$fpol.' id="polj" /><label for="polj">' . __('f') . '</label>
                    </p>
                </div>
                <div class="input-field col s12">
                    <label style="position: static;">
                        ' . __('Born date') . '
                    </label>
                    <div class="row">
                        <div class="col s4">
                            <select style="width:80px;" name="byear">'
                            . createOptionsFromParams(1940, 2014, $byear) .
                            '</select>
                        </div>
                        <div class="col s4">
                            <select style="width:50px;" name="bmonth">'
                            . createOptionsFromParams(1, 12, $bmonth) .
                            '</select>
                        </div>
                        <div class="col s4">
                            <select style="width:50px;" name="bday">'
                            . createOptionsFromParams(1, 31, $bday) .
                            '</select>
                        </div>
                    </div>
                </div>
                <div class="input-field col s12">
                    <label for="about">
                        ' . __('about') . '
                    </label>
                    <textarea id="about" name="about" class="materialize-textarea">' . h($about) .'</textarea>
                </div>
                <div class="input-field col s12">
                    <label for="signature">
                        ' . __('signature') . '
                    </label>
                    <textarea id="signature" name="signature" class="materialize-textarea" />' . h($signature) .'</textarea>
                </div>
                <div class="input-field col s12">
                    <select name="locked">';

                    if ($locked == 0) {
                        $content .= '
                        <option value="1">' . __('Banned') . '</option>
                        <option value="0" selected="selected">' . __('Not banned') . '</option>';
                    } else {
                        $content .= '
                        <option value="1" selected="selected">' . __('Banned') . '</option>
                        <option value="0">' . __('Not banned') . '</option>';
                    }

                    $content .= '
                    </select>
                    <label>' . __('Ban') . '</label>
                </div>
                <div class="input-field col s12">
                    <select name="status">';

                    foreach ($statuses as $key => $value) {
                        if ($key == 0) continue;
                        $content .= '
                        <option value="' . $key . '"'. ($status == $key ? ' selected="selected"':'') . '>' . $value['title'] . '</option>';
                    }
                    
                    
                    
                    
                    
                    $activation = (!empty($activation))
                     ? '<input id="activation" name="activation" type="checkbox" value="1" /><label for="activation">' . __('Activate') . '</label>'
                     : '<span class="green-text">' . __('Active') . '</span>';
                    
                    
                    
                    $content .=  '
                    </select>
                    <label>' . __('status') . '</label>
                </div>
                <div class="input-field col s12">
                    ' . __('Activation') . ': 
                    ' . $activation . '
                </div>
                <div class="input-field col s12">
                    <input class="btn" type="submit" name="send" value="' . __('Save') . '" />
                </div>
            </div>
        </div>
    </form>';

    return $content;
}


//*****************************************************************************************************************
//*****************************************************************************************************************

function saveAnk() {
    $DB = getDB();

    if (empty($_GET['id']) || !is_numeric($_GET['id'])) redirect('/admin/users/list.php');

    $check_user = $DB->select('users', DB_FIRST, array('cond' => array('id' => (int)$_GET['id'])));
    if (count($check_user) < 1) {
        $_SESSION['message'][] = __('Can not find user');
        redirect('/admin/users/list.php');
    }


    //validate class object for validate data
    $errors = array();
    $content = '';

    //deleting spaces
    $_POST = array_merge(array('login', 'state', 'passw', 'email', 'url', 'pol', 'byear', 'bmonth', 'bday', 'about', 'signature', 'locked', 'status'), $_POST);
    foreach ($_POST as $key => $value) {
        $$key = trim($value);
    }

    if (isset($pol) && ($pol == '1' || $pol == 'm')) $pol = 'm';
    else if (!isset($pol) || $pol === '') $pol = '';
    else $pol = 'f';

    $byear = (isset($byear)) ? intval($byear) : '';
    $byear = (!empty($byear) && ($byear >= 1970 && $byear <= 2008)) ? $byear : 0;
    $bmonth = (isset($bmonth)) ? intval($bmonth) : '';
    $bmonth = (!empty($bmonth) && ($bmonth >= 1 && $bmonth <= 12)) ? $bmonth : 0;
    $bday = (isset($bday)) ? intval($bday) : '';
    $bday = (!empty($bday) && ($bday >= 1 && $bday <= 31)) ? $bday : 0;

    //check data for wrong chars
    if (\Validate::cha_val($login, V_TITLE) !== true)
        $errors[] = '<li>' . __('Wrong chars in field "login"') . '</li>';
    if (!empty($full_name) && \Validate::cha_val($full_name, V_FULLNAME) !== true)
        $errors[] = '<li>' . __('Wrong chars in field "full_name"') . '</li>';
    if (!empty($email) && \Validate::cha_val($email, V_MAIL) !== true)
        $errors[] = '<li>' . __('Wrong chars in filed "e-mail"') . '</li>';
    if (!empty($url) && \Validate::cha_val($url, V_URL) !== true)
        $errors[] = '<li>' . __('Wrong chars in filed "URL"') . '</li>';
    if (!empty($about) && \Validate::cha_val($about, V_TEXT) !== true)
        $errors[] = '<li>' . __('Wrong chars in field "interes"') . '</li>';
    if (!empty($signature) && \Validate::cha_val($signature, V_TEXT) !== true)
        $errors[] = '<li>' . __('Wrong chars in field "signature"') . '</li>';

    //check data for max/min lenght
    if (\Validate::len_val($login, 3, 20) !== true)
        $errors[] = '<li>' . __('Wrong "name" lenght') . '</li>';
        if (\Validate::len_val($full_name, 0, 255) !== true)
                $errors[] = '<li>' . __('Very short "Full name"') . '</li>';
    if (\Validate::len_val($url, 0) !== true)
        $errors[] = '<li>' . __('Very short "URL"') . '</li>';
    if (\Validate::len_val($about, 0, 300) !== true)
        $errors[] = '<li>' . __('Very short "About"') . '</li>';
    if (\Validate::len_val($signature, 0, 250) !== true)
        $errors[] = '<li>' . __('Very short "Signature"') . '</li>';
    if (!empty($passw) && \Validate::len_val($passw, 6, 32) !== true)
        $errors[] = '<li>' . sprintf(__('Very short pass'), 6) . '</li>';


    if ($locked != 1 && $locked != 0) $locked = 0;
    $status = (int)$status;
    if ($status < 1) $status = 1;

    if (!empty($check_user[0]['name']) && $check_user[0]['name'] !== $login) {
        $is_exists = $DB->select('users', DB_FIRST, array('cond' => array('name' => $login)));
        if (!empty($is_exists))
            $errors[] = '<li>' . sprintf(__('Name already exists'), $login) . '</li>';
    }

    if (!empty($errors)) {
        $_SESSION['message'] = array_merge($_SESSION['message'],$errors);
        
        $_SESSION['edit_ank'] = array();
        foreach ($_POST as $key => $value) {
            $_SESSION['edit_ank'][$key] = trim($value);
        }
        redirect('/admin/users/list.php?ac=ank&id=' . $_GET['id']);
    } else {
        $Cache = new \Cache;
        $Cache->prefix = 'gravatar';
        $Cache->cacheDir = 'sys/cache/users/gravatars/';
        if ($Cache->check('user_' . $_GET['id']))
            $Cache->remove('user_' . $_GET['id']);
    }

    $data = array(
        'id'         => $_GET['id'],
        'name'       => $login,
        'full_name'  => $full_name,
        'state'      => $state,
        'email'      => $email,
        'url'        => $url,
        'pol'        => $pol,
        'byear'      => $byear,
        'bmonth'     => $bmonth,
        'bday'       => $bday,
        'about'      => $about,
        'signature'  => $signature,
        'locked'     => $locked,
        'status'     => $status,
    );
    if (!empty($passw)) $data['passw'] = md5crypt($passw);
    if (isset($_POST['activation'])) $data['activation'] = '';
    $DB->save('users', $data);

    redirect('/admin/users/list.php?ac=ank&id=' . $_GET['id']);
}



?>