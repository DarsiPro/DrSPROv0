<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/

include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';


$pageTitle = __('Bans by IP');


if ( !isset( $_GET['ac'] ) ) $_GET['ac'] = 'index';
$actions = array('index','del', 'add');
                    
if (!in_array( $_GET['ac'], $actions )) $_GET['ac'] = 'index';

switch ($_GET['ac']) {
    case 'add':
        $content = add();
        break;
    case 'del':
        $content = delete();
        break;
    case 'index':
    default:
        $content = index($pageTitle);
}



$pageNav = $pageTitle;
$pageNavr = '<a class="btn modal-trigger" href="#addBan">
                <i class="mdi-content-add left"></i>' . __('Add') . '
            </a>';
include_once ROOT . '/admin/template/header.php';
?>


<?php echo $content; ?>

<?php

include_once ROOT . '/admin/template/footer.php';

    
function index(&$page_title) {
    $content = null;
    if (file_exists(ROOT . '/sys/logs/ip_ban/baned.dat')) {
        $data = file(ROOT . '/sys/logs/ip_ban/baned.dat');
        if (!empty($data)) {
            foreach($data as $key => $row) {
                $content .= '<tr>
                    <td>' . $row . '</td>
                    <td class="right-align">
                        <a class="btn-floating red" onClick="return confirm(\''.__('Are you sure?').'\');" href="ip_ban.php?ac=del&id=' . $key . '"><i class="small mdi-action-delete"></i></a>
                    </td>
                </tr>';
            }
        }
    }
    
    if (empty($content))
        $content = '<div class="row">
                        <table>
                            <thead>
                                <tr>
                                    <th>IP</th>
                                    <th class="right-align">'.__("Activity").'</th>
                                <tr>
                            </thead>
                            <tbody>
                            <tr><td>' . __('Not bans by IP') . '</td></tr>
                            </tbody>
                        </table>
                    </div>';
    else 
        $content = '<div class="row">
                        <table class="hoverable">
                            <thead>
                                <tr>
                                    <th>IP</th>
                                    <th class="right-align">'.__("Activity").'</th>
                                <tr>
                            </thead>
                            <tbody>
                            ' . $content . '
                            </tbody>
                        </table>
                    </div>';
    
    //add form
    $content .= '<div id="addBan" class="modal modal-fixed-footer">
                    <form action="ip_ban.php?ac=add" method="POST">
                    <div class="modal-content">
                        <h4 class="light">' . __('Adding new IP') . '</h4>
                        <div class="input-field">
                            <input id="ip" type="text" name="ip" />
                            <label for="ip">IP</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
                        <input type="submit" value="'. __('Save') .'" name="send" class="btn" />
                    </div>
                    </form>
                </div>';
    
    $content .= '<script>
                    $(document).ready(function(){
                        // the "href" attribute of .modal-trigger must specify the modal ID that wants to be triggered
                        $(\'.modal-trigger\').leanModal();
                    });
                </script>';

    return $content;
}



/**
* adding IP to ban list
*/
function add() {
    if (empty($_POST['ip'])) redirect('/admin/ip_ban.php');
    $ip = trim($_POST['ip']);
    $error = array();
    
    
    if (!preg_match('#^\d{1,3}\.\d{1,3}.\d{1,3}.\d{1,3}$#', $ip))
        $error[] = __('Wrong chars in IP address');
    
    if (!empty($error)) {
        $_SESSION['message'] = array_merge($_SESSION['message'], $error);
        redirect('/admin/ip_ban.php');
    } else {
        touchDir(ROOT . '/sys/logs/ip_ban/');
        $f = fopen(ROOT . '/sys/logs/ip_ban/baned.dat', 'a+');
        fwrite($f, $ip . "\n");
        fclose($f);
    }
    
    redirect('/admin/ip_ban.php');
}



/**
* deleting ip
*/
function delete() {
    if (!isset($_GET['id'])) redirect('ip_ban.php');
    if (file_exists(ROOT . '/sys/logs/ip_ban/baned.dat')) {
        $data = file(ROOT . '/sys/logs/ip_ban/baned.dat');
        if (!empty($data)) {
            if (array_key_exists($_GET['id'], $data)) {
                $_data = array();
                foreach ($data as $key => $val) {
                    if (empty($val) || $key == $_GET['id']) continue;
                    $_data[$key] = $val;
                }
                $data = implode("", $_data);
                file_put_contents(ROOT . '/sys/logs/ip_ban/baned.dat', $data);
            } else {
                $_SESSION['message'][] = __('This IP is not exists');
            }
        }
    }
    
    redirect('/admin/ip_ban.php');
}

?>