<?php
/**
* @project    DarsiPro CMS
* @package    Plugins manager
* @url        https://darsi.pro
*/

include_once '../sys/boot.php';
include_once './inc/adm_boot.php';

class PluginsAdmin {

    static $source_url;

    public function __construct()
    {
        self::$source_url = 'http://api.darsi.pro/plugins';
    }

    // главная страница менеджера
    function index() {
        $pageTitle = __('Plugins manager');
        $pageNav = $pageTitle;
        $pageNavr = '<a class="waves-effect modal-trigger btn" href="#sec">' . __('Include of the archive') . '</a>';
        include_once ROOT . '/admin/template/header.php';
        $content = '';

        if (!extension_loaded( "openssl" )) {
            $content .= '<blockquote>'.__('Requires php_openssl').'</blockquote>';
        }

        $content .= '

            <style>
                #plugins li {
                    min-height: 84px;
                    height: auto;
                    max-height: 150px;
                    padding-left: 93px
                }
                #plugins li img {
                    width: 64px;
                    height: 64px;
                }
                #plugins li.on {
                    border-left: 15px solid #4caf50;
                }
                #plugins .secondary-content {
                    font-size: 1.5em;
                }
            </style>

            <div id="sec" class="modal modal-fixed-footer">
                <form id="locsend" method="post" action="plugins.php?ac=local" onSubmit="locsend(this); return false" enctype="multipart/form-data">
                    <div class="modal-content">
                        <h4>' . __('Include of the archive (Only ZIP)') . '</h4>
                        <div class="input-field col s12">
                            <input id="pl_url" type="text" name="pl_url" placeholder="http://site.com/path/to/plugin.zip" />
                            <label for="pl_url">' . __('Load plugin with remote server') . '</label>
                        </div>
                        <div class="file-field input-field col s12">
                        <input class="file-path" placeholder="' . __('Load plugin as local file') . '" type="text"/>
                        <div class="btn">
                            <span>ФАЙЛ</span>
                            <input  type="file" accept="application/zip" name="pl_file" onChange="if (pl_file.value.substring(pl_file.value.lastIndexOf(\'.\')+1, pl_file.value.length).toLowerCase() != \'zip\')
                                { Materialize.toast(\'File type should be ZIP\', 4000); return; }" />
                        </div>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <a href="#!" class="modal-action waves-effect modal-close btn-flat">'.__('Cancel').'</a>
                        <input type="submit" value="' . __('Include') . '" name="send" class="btn" />
                    </div>
                </form>
            </div>

            <div id="popup" class="modal modal-fixed-footer">
                <div class="modal-content">
                    <h4>' . __('Include') . '</h4>
                    <div id="po_cont">
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-action waves-effect modal-close btn-flat">'.__('Cancel').'</a>
                </div>
            </div>

            <ul id="chswitch" class="tabs">
                <li class="catalog tab col s3"><a href="#" class="active">' . __('Catalog') . '</a></li>
                <li class="installed tab col s3"><a class="active" href="#">' . __('Included') . '</a></li>
            </ul>
            <div id="main" style="position:relative; padding-bottom: 100px">
                <ul id="plugins" class="collection">
                </ul>
            </div>

            <script>

                function load() {
                    var preloader = \'<div class="progress"><div class="indeterminate"></div></div>\';
                    var timer = setTimeout(function(){
                        $(\'#plugins\').html(preloader)
                    }, 500);
                    type = \'catalog\';
                    if ($(\'#chswitch .installed\').hasClass(\'act\')) {
                        type = \'installed\';
                    }
                    $.ajax({
                        url: "/admin/plugins.php",
                        data: "ac=list&type="+type,
                        cache: false,
                        success: function(html){
                            clearTimeout(timer);
                            $("#plugins").html(html);
                        },
                        error: function(response) {
                            clearTimeout(timer);
                            Materialize.toast(response, 4000);
                        }
                    });
                }

                function clear() {
                    $(\'#chswitch .catalog\').removeClass(\'act\');
                    $(\'#chswitch .installed\').removeClass(\'act\');
                }
                $(\'#chswitch .catalog\').click(function() {
                    clear();
                    $(\'#chswitch .catalog\').addClass(\'act\');
                    load();
                });
                $(\'#chswitch .installed\').click(function() {
                    clear();
                    $(\'#chswitch .installed\').addClass(\'act\');
                    load();
                });

                $(\'#chswitch .catalog\').click();

                function openPopupNew(e) {
                    $(\'#po_cont\').html("<div class=\"items\">' . __('Please, wait...') . '</div>");
                    $(\'#popup\').openModal();
                    jQuery.ajax({
                        url:     $(e).attr("href"),
                        type:     "POST",
                        dataType: "html",
                        data: jQuery(e).serialize(), 
                        success: function(response) {
                            $(\'.modal-content\').html(response);
                            load();
                        },
                        error: function(response) {
                            $(\'.modal-content\').html("' . __('Some error occurred') . '");
                        }
                    }); 
                }

                function asend(e) {
                    jQuery.ajax({
                        url:     $(e).attr("href"),
                        type:     "POST",
                        dataType: "html",
                        data: jQuery(e).serialize(), 
                        success: function(response) {
                            load();
                        },
                        error: function(response) {
                            $(\'#po_cont\').html("<div class=\"items\">' . __('Some error occurred') . '</div>");
                            $(\'#popup\').openModal();
                        }
                    }); 
                }

                function locsend(e) {
                    $(\'#sec\').closeModal();
                    
                    $(\'#po_cont\').html("<div class=\"items\">' . __('Please, wait...') . '</div>");
                    $(\'#popup\').openModal();

                    form = document.forms.locsend;
                    formData = new FormData(form);
                    xhr = new XMLHttpRequest();
                    xhr.open("POST", $(e).attr("action"));

                    xhr.onreadystatechange = function() {
                        if (xhr.readyState == 4) {
                                data = xhr.responseText;
                                $(\'#po_cont .items\').html(data);
                                load();
                        }
                    };
                    xhr.send(formData);

                }
                
                $(document).ready(function(){
                    // the "href" attribute of .modal-trigger must specify the modal ID that wants to be triggered
                    $(\'.modal-trigger\').leanModal();
                });

            </script>
        ';

        echo $content;
        include_once ROOT . '/admin/template/footer.php';
    }


    // показ списка плагинов
    // $type - выбор вкладки для отображения
    function pl_list($type) {
        $content = '';

        if ($type == 'catalog') {
            $Cache = new Cache;
            $Cache->lifeTime = 6000;
            if ($Cache->check('adm_pl_list')) {
                $source_list_raw = $Cache->read('adm_pl_list');
                $source_list = json_decode($source_list_raw, true);
                $plugins = $source_list['plugins'];
            } else {
                $source_list_raw = file_get_contents(self::$source_url);
                $source_list = json_decode($source_list_raw, true);
                $plugins = $source_list['plugins'];
                if (count($plugins) < 1) {
                    echo $content .= '<div class="warning">' . __('Catalog plugins not found') . '</div>';
                    return;
                } else
                    $Cache->write($source_list_raw, 'adm_pl_list', array());
            }
        } elseif ($type == 'installed') {
            $plugins = glob(ROOT . '/plugins/*');
            if (!empty($plugins) && count($plugins) > 0) {
                foreach ($plugins as $k => $pl) {
                    if (!is_dir($pl)) unset($plugins[$k]);
                }
            }
        } else {
            return;
        }

        $allow_install = true;
        if (!extension_loaded( "openssl" )) $allow_install = false;

        foreach ($plugins as $result) {
            if ($type == 'installed') {
                $local_path = $result;
                $dir = strtolower(substr($result, strripos($result, '/')+1));
            } else {
                $dir = strtolower($result['name']);
                $local_path = R . 'plugins/' . $dir;
            }
            
            $buttoms = '<div class="secondary-content">';
            $class = '';
            $icon = '';
            if (file_exists($local_path . '/config.json')) {
                $result = json_decode(file_get_contents($local_path . '/config.json'), 1);
                if (!empty($result['more']))
                    $buttoms .= '<a href="'.h($result['more']).'"><i class="mdi-action-language"></i></a>';
                if (!empty($result['active'])) {
                    $settigs_file_path = R . 'plugins/' . $dir . '/settings.php';
                    if (file_exists($settigs_file_path))
                        $buttoms .= '<a title="' . __('Edit') . '" href="plugins.php?ac=edit&dir='.$dir.'"><i class="mdi-action-settings"></i></a>';
                    $buttoms .= '<a title="' . __('Off') . '" href="plugins.php?ac=off&dir='.$dir.'" onclick="asend(this);return false"><i class="mdi-content-clear"></i></a>';
                    $class = 'on';
                } else {
                    $buttoms .= '<a title="' . __('On') . '" href="plugins.php?ac=on&dir='.$dir.'" onclick="asend(this);return false"><i class="mdi-action-done"></i></a>';
                    $class = 'off';
                }
                if (!empty($result['icon'])) {
                    $icon_data = base64_encode(file_get_contents($local_path.'/'.$result['icon']));
                    $size = getimagesize($local_path.'/'.$result['icon']);
                    $icon = "<img class=\"circle\" src=\"data: ".$size['mime'].";base64,".$icon_data."\">";
                }
            } else {
                if ($type == 'installed') {
                    continue;
                } else {
                    $path_file = sprintf($source_list['path_file'], strtolower($result['name']));
                    if (!empty($result['more']))
                        $buttoms .= '<a href="'.h($result['more']).'"><i class="mdi-action-language"></i></a>';
                    if ($allow_install)
                        $buttoms .= '<a href="plugins.php?ac=install&url='.$path_file.'&dir='.$dir.'" title="' . __('Include') . '" onclick="openPopupNew(this);return false"><i class="mdi-action-get-app"></i></a>';
                    if (!empty($result['icon'])) {
                        $path_icon = sprintf($source_list['path_icon'], strtolower($result['name'])).$result['icon'];
                        $icon = "<img class=\"circle\" src=\"".h($path_icon)."\">";
                    }
                }
            }
            $buttoms .= '</div>';

            $content .= '<li class="collection-item avatar '.$class.'">';
            $content .= $icon;
            $content .= "<span class=\"card-title black-text\">".h($result['title'])."</span>";
            $content .= "<p class=\"desc\">".h((empty($result['desc']) ? __('Description is not') : $result['desc'])).'</p>';
            $content .= $buttoms;

            $content .= '</li>';
        }

        echo $content;
    }


    // включение и выключение плагина
    // $to - boolear, $dir - название папки плагина
    function switchPlugin($to, $dir) {
        $to = (int)(bool)$to;
        if (empty($dir)) redirect('/');
        $pach = ROOT . '/plugins/' . $dir;
        $conf_pach = $pach . '/config.json';

        $config = (file_exists($conf_pach)) ? json_decode(file_get_contents($conf_pach), 1) : array();
        $config['active'] = $to;
        file_put_contents($conf_pach, json_encode($config, JSON_UNESCAPED_UNICODE));

        $Cache = new Cache;
        $Cache->remove('adm_pl_settings');

        redirect('../admin/plugins.php');
    }


    // вывод настроек плагина
    // $dir - название папки плагина
    function editPlugin($dir) {
        if (empty($dir)) redirect('/admin/plugins.php');
        if (!preg_match('#^[\w\d_-]+$#i', $dir)) redirect('/admin/plugins.php');

        $this->settigs_file_path = ROOT . '/plugins/' . $dir . '/settings.php';
        if (!file_exists($this->settigs_file_path)){
            echo __('No settings for this plugin');
            return;
        }

        $config = json_decode(file_get_contents(ROOT . '/plugins/' . $dir . '/config.json'), 1);

        $pageTitle = __('Manage plugins');
        $pageNav = '<a href="'.WWW_ROOT.'/admin/plugins.php">' . __('Return to manage plugins') . '</a>';
        $pageNavr = __('Manage plugin') . ' ' . h($config['title']);
        include_once ROOT . '/admin/template/header.php';
        include_once $this->settigs_file_path;
        print (!empty($output)) ? $output : '';
        include_once ROOT . '/admin/template/footer.php';
    }


    // установка плагина из загруженного архива
    function local() {
        // local plugin archive
        if (!empty($_FILES['pl_file']['name'])) {

            // download plugin to tmp folder
            $Plugins = new Plugins;
            $filename = $Plugins->localUpload('pl_file');
            if (!$filename) {
                echo $Plugins->getErrors();
                return;
            }
            echo $this->install($filename);
        } else if (!empty($_POST['pl_url'])) {
            echo $this->pl_url($_POST['pl_url']);
        }
    }


    // установка по url адресу
    function pl_url($url, $dir) {

        // download plugin to tmp folder
        $Plugins = new Plugins;
        $filename = $Plugins->foreignUpload($url);
        if (!$filename) {
            echo $Plugins->getErrors();
            return;
        }

        echo $this->install($filename, $dir);
    }


    // установка и включение плагина
    // $filename - файл в /sys/tmp/
    function install($filename, $dir = False) {

        // install plugin
        $Plugins = new Plugins;
        $result = $Plugins->install($filename, $dir);
        if (!$result) {
            echo $Plugins->getErrors();
            return;
        }

        $files = $Plugins->getFiles();
        $message = __('Plugin is install');
        $message .= '<strong>' . __('New files') . '</strong><ul class="wps-list">';
        foreach ($files as $file) {
            $message .= '<li>' . $file . '</li>';
        }
        $message .= '</ul>';

        $Cache = new Cache;
        $Cache->remove('adm_pl_settings');

        echo $message;
    }

}

$PluginsAdmin = new PluginsAdmin();

if (!isset($_GET['ac'])) $_GET['ac'] = 'index';
$actions = array('index', 'list', 'install', 'on', 'off', 'edit', 'local');

switch ($_GET['ac']) {
    case 'list':
        $PluginsAdmin->pl_list($_GET['type']);
        break;
    case 'install':
        $PluginsAdmin->pl_url($_GET['url'],$_GET['dir']);
        break;
    case 'on':
        $PluginsAdmin->switchPlugin(1, $_GET['dir']);
        break;
    case 'off':
        $PluginsAdmin->switchPlugin(0, $_GET['dir']);
        break;
    case 'edit':
        $PluginsAdmin->editPlugin($_GET['dir']);
        break;
    case 'local':
        $PluginsAdmin->local();
        break;
    default:
        $PluginsAdmin->index();
}

?>