<?php
/**
* @project    DarsiPro CMS
* @package    Authorization
* @url        https://darsi.pro
*/

header('Content-Type: text/html; charset=utf-8');


$DB = getDB();
$Register = Register::getInstance();



if (!isset($_SESSION['message']))
    $_SESSION['message'] = array();


if (ADM_REFER_PROTECTED == 1) {
    $script_name = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
    $script_name = strrchr($script_name, '/');
    if ($script_name != '/index.php') {
        $referer = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
        preg_match('#^https?://([^/]+)#', $referer, $match);
        if (empty($match[1]) || $match[1] != $_SERVER['SERVER_NAME'])
            redirect('/admin/index.php');
    }
}


if (Config::read('active', 'users') and !isset($_SESSION['user']['name']))
    redirect('/');

if (!isset($_SESSION['adm_panel_authorize']) || $_SESSION['adm_panel_authorize'] < time() || empty($_SESSION['user'])) {
    $errors = [];
    
    if (isset($_POST['send']) && isset($_POST['passwd'])) {

        // Защита от перебора пароля - при каждой неудачной попытке время задержки увеличивается
        if (isset($_SESSION['count']) && $_SESSION['count'] > time()) {
            $errors[] = sprintf(__('You must wait'), ($_SESSION['count'] - time()));
        } else {
            unset($_SESSION['count']);
        }

        $pass = $_POST['passwd'];

        if (empty($pass)) $errors[] = sprintf(__('Empty field "param"'), __('Password'));

        // Проверять существование такого пользователя есть смысл только в том
        // случае, если поля не пустые и не содержат недопустимых символов
        if (empty($error)) {
            $users = $DB->select('users', DB_FIRST, array('cond' => array('name' => $_SESSION['user']['name'])));

            $check_password = false;
            if (count($users) > 0 && !empty($users[0])) {
                $check_password = checkPassword($users[0]['passw'], $pass);
            }

            if (count($users) < 1 || !$check_password) {
                $errors[] = __('Wrong pass');
            } else {
                //turn access
                ACL::turnUser(array('__panel__', 'entry'),true,array($users[0]['status'],$users[0]['id']));
            }
        }

        // Если были допущены ошибки при заполнении формы
        if (count($errors) > 1) {
            if (!isset($_SESSION['count']))
                $_SESSION['count'] = 1;
            else if ($_SESSION['count'] < 10)
                $_SESSION['count']++;
            else if ($_SESSION['count'] < time())
                $_SESSION['count'] = time() + 10;
            else
                $_SESSION['count'] = $_SESSION['count'] + 10;
        }

        if (count($errors) == 0) {
            $_SESSION['user'] = $users[0];
            $_SESSION['adm_panel_authorize'] = (time() + Config::read('session_time', '__secure__'));
            redirect(getReferer(), true);
        }
    }


    $pageTitle = __('Admin Panel Authorization');
?>



<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo __('Admin Panel Authorization') ?></title>
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    
    <link type="text/css" rel="stylesheet" href="<?php echo WWW_ROOT ?>/admin/template/css/materialize.min.css"  media="screen,projection"/>
    <link type="text/css" rel="stylesheet" href="<?php echo WWW_ROOT ?>/admin/template/css/main.css"  media="screen,projection"/>

    <!--Let browser know website is optimized for mobile-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
</head>
<body class="grey lighten-4">
    <main class="valign-wrapper">
        <div class="valign row">
            <div class="card">
                <form method="POST" action="">
                    <div class="card-content">
                        <span class="card-title black-text">
                            <?php echo __('Please retype the password') ?>
                        </span>
                        <div class="input-field">
                            <input placeholder="<?php echo __('Password') ?>" name="passwd" id="password" type="password" class="validate"/>
                        </div>
                    </div>
                    <div class="card-action">
                        <button class="btn waves-effect waves-light" type="submit" name="send">
                            <?php echo __('Sign in') ?>
                            <i class="mdi-content-send right"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <!--Import jQuery before materialize.js-->
    <script type="text/javascript" src="<?php echo WWW_ROOT ?>/admin/js/jquery.js"></script>
    <script type="text/javascript" src="<?php echo WWW_ROOT ?>/admin/template/js/materialize.min.js"></script>
    
    
    <?php
        // Уведомления
        if (count($errors)>0) {
            echo '<script type="text/javascript">';
            foreach($errors as $k => $v) {
                echo "Materialize.toast('". $v ."', 4000);\n";
            }
            unset($errors);
            echo '</script>';
        }
    ?>
    
</body>
</html>






<?php
    die();

    
    
} else if (!empty($_SESSION['adm_panel_authorize'])) {
    $_SESSION['adm_panel_authorize'] = (time() + Config::read('session_time', '__secure__'));
    
    if (ACL::turnUser(array('__panel__', 'restricted_access'))) {
    
        $url = preg_replace('#^.*/([^/]+)\.\w{2,5}$#i', "$1", $_SERVER['SCRIPT_NAME']);
        //var_dump($url);
        if (!empty($url) && $url != 'index') {
            if (!ACL::turnUser(array('__panel__', 'restricted_access_' . $url))) {
                $_SESSION['message'][] = __('Permission denied');
                redirect('/admin/');
            }
        }
    }
}






if (!empty($_GET['install'])) {
    $instMod = (string)$_GET['install'];
    if (!empty($instMod) && preg_match('#^[a-z]+$#i', $instMod)) {
        $ModulesInstaller = new ModuleInstaller();
        $ModulesInstaller->installModule($instMod);
        redirect('/admin/');
    }
}



function getAdmFrontMenuParams() {
    $ModulesInstaller = new ModuleInstaller();
    $out = array();
    $modules = glob(ROOT . '/modules/*', GLOB_ONLYDIR);
    
    if (count($modules)) {
        foreach ($modules as $i => $modPath) {
            $mod = basename($modPath);
            if (!$ModulesInstaller->checkModule($mod)) {
                $menuPath = $modPath . '/menu.php';
                $out[$mod] = array(
                    'title' => __($mod,false,$mod),
                    'pages' => array(
                        WWW_ROOT.'/admin/settings.php?m='.$mod => __('Settings'),
                    )
                );
                if (file_exists($menuPath)) {
                    $menuInfo = include($menuPath);
                    if (isset($menuInfo)) {
                        $out[$mod] = array_merge($out[$mod], $menuInfo);
                    }
                }
            }
        }
    }
    uasort($out, function ($a, $b) {
        if (is_array($a) && is_array($b) && isset($a['title']) && isset($b['title'])) {
            if ($a['title'] == $b['title']) {
                return 0;
            }
            return ($a['title'] < $b['title']) ? -1 : 1;
        } else {
            return 0;
        }
    });
    return $out;
}
?>