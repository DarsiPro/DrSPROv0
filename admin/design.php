<?php
/**
* @project    DarsiPro CMS
* @package    Template redactor
* @url        https://darsi.pro
*/

include_once '../sys/boot.php';
include_once R.'admin/inc/adm_boot.php';


$pageTitle = __('Design - templates');
$pageNav = $pageTitle;
$pageNavr = '<a href="set_default_dis.php" onClick="return confirm(\'' . __('Return to default template, confirm text') . '\')">' . __('Return to default template') . '</a>&nbsp;|&nbsp;<a href="backup_dis.php" onClick="return confirm(\'' . __('Return to default template, confirm text') . '\')">' . __('Save current state of template') . '</a>';

$name_tpl = '';
$name_stpl = '';

$allowedFiles = array(
    'default' => array(
        'main.html'             => __('Layout'),
        'addcommentform.html'   => __('Add comment form'),
        'editcommentform.html'  => __('Edit comment form'),
        'viewcomment.html'      => __('View comment'),
        'captcha.html'          => __('Captcha'),
        'infomessage.html'      => __('Infomessage'),
        'infomessagegrand.html' => __('Infomessagegrand'),
        'list.html'             => __('List of materials'),
    ),
);


$Register = Register::getInstance();


/* ADD TEMPLATE */
if (!empty($_GET['ac']) && $_GET['ac'] === 'add_template') {
    // Модуль.
    $module = preg_replace('#[^a-z0-9_\-]#', '', (!empty($_POST['module'])) ? $_POST['module'] : '');
    $_GET['m'] = $module;
    // Название файла
	$filename = preg_replace('#[^a-z0-9_.\-]#', '', (!empty($_POST['title'])) ? $_POST['title'] : '');
    if (strpos($filename, '.') === false) $filename .= '.html';
    $_GET['t'] = $filename;
    
	$code = (!empty($_POST['code'])) ? $_POST['code'] : '';
	
	//if (empty($filename) || empty($code) || empty($module)) redirect('/admin/design.php?m=default&t=main.html');
	if (empty($filename) || empty($code) || empty($module)) redirect('/admin/design.php');
	
	// Путь до папки создаваемого шаблона
	$path = R.'template/' . Config::read('template') . '/html/'.$module.'/';
    // Путь до создаваемого шаблона
	$path2 = R.'template/' . Config::read('template') . '/html/'.$module.'/' . $filename;
	// Если такой файл уже существует
	if (file_exists($path2))
		$_SESSION['message'][] = __('This template is existed');
	// Если не существует файл, то создаем его и папку для него, если её нет.
    else {
        if (!file_exists($path))
            mkdir($path, 0755);
	
		file_put_contents($path2, $code);
		$_SESSION['message'][] = __('Template is created');
	}
}
/* END ADD TEMPLATE */


// Получение данных


// path formating "TEMPLATE_ROOT/design/$module/$filename" or "TEMPLATE_ROOT/design/$filename"




// path formating "TEMPLATE_ROOT/$type/$module/$filename" or "TEMPLATE_ROOT/$type/$filename"
// папка нахождения шаблона (1)
$module = '';
if (empty($_GET['d']) || !is_string($_GET['d'])) $_GET['d'] = 'html';
$type = trim($_GET['d']);
if ($type == 'html') {
    // папка нахождения шаблона (2)
    if (empty($_GET['m']) || !is_string($_GET['m'])) $_GET['m'] = 'default';
    $module = trim($_GET['m']);
    $Register['module'] = $module;
}
// название файла в этой папке
//if (empty($_GET['t']) || !is_string($_GET['t'])) $_GET['t'] = 'main.html';

if (empty($_GET['t']) || !is_string($_GET['t'])) $_GET['t'] = '';



$filename = trim($_GET['t']);




/* LIST OF TEMPLATES */
clearstatcache();
$modInstaller = new ModuleInstaller();

$custom_tpl = array();
// Получаем пути до папок с шаблонами
$dirs_tmp = glob(R.'template/' . Config::read('template') . '/html/*', GLOB_ONLYDIR);
if (!empty($dirs_tmp)) {
    // Обходим каждую папку
	foreach ($dirs_tmp as $dir) {
		$m_name = basename($dir);
        // Получаем список необходимых для работы модуля шаблонов
        $need_parts = $modInstaller->getTemplateParts($m_name);
        if (!empty($need_parts)) {
            $allowedFiles[$m_name] = $need_parts;
        }
        
        // Проходим по каждому файлу в папке
        if ($mod_templates = opendir(R.'template/' . Config::read('template') . '/html/'.$m_name.'/')) {
            while (false !== ($tmp_name = readdir($mod_templates))) {
                // Если этот файл не указан в обязательных шаблонах модуля, если это папка модуля. или это файлы не из папки модуля
                if (strpos($tmp_name,'.') !== 0 && strpos($tmp_name,'.stand') === false
                    && (!isset($allowedFiles[$m_name]) || !array_key_exists($tmp_name, $allowedFiles[$m_name])))
                {
                    if (!isset($custom_tpl[$m_name]) || !is_array($custom_tpl[$m_name]))
                        $custom_tpl[$m_name] = array();
                    $custom_tpl[$m_name][] = $tmp_name;
                }
            }
            closedir($mod_templates);
        }
	}
}

/* CSS STYLES */
clearstatcache();
$styles = array();
if ($mod_templates = opendir(R.'template/' . Config::read('template') . '/css/')) {
    while (false !== ($tmp_name = readdir($mod_templates))) {
        if (strpos($tmp_name,'.css') && strpos($tmp_name,'.stand') === false)
            $styles[] = $tmp_name;
    }
}

/* JS */
clearstatcache();
$scripts = array();
if ($mod_templates = opendir(R.'template/' . Config::read('template') . '/js/')) {
    while (false !== ($tmp_name = readdir($mod_templates))) {
        if (strpos($tmp_name,'.js') && strpos($tmp_name,'.stand') === false)
            $scripts[] = $tmp_name;
    }
}

if(empty($filename)){
    include_once R.'admin/template/tmpls.php';
    exit;
}
        




/* SAVE CODE */
if(isset($_POST['send']) && isset($_POST['templ'])) {

    $template_file = R.'/template/' . Config::read('template') . '/' . $type .'/' . (($type == 'html') ? $module . '/' : '') . $filename;
    
    if (!file_exists($template_file . '.stand') && file_exists($template_file)) {
        copy($template_file, $template_file . '.stand');
    }
    $file = fopen($template_file, 'w+');
	
	if(fputs($file, $_POST['templ'])) {
		$_SESSION['message'][] = __('Template is saved');
	} else {
		$_SESSION['message'][] = __('Template is not saved');
	}
	fclose($file);
}


/* SHOW CODE */
$path = R.'template/' . Config::read('template') . '/' . $type .'/' . (($type == 'html') ? $module . '/' : '') . $filename;



if (!file_exists($path)) {
    $path = ROOT .'/template/' . Config::read('template') . '/html/default/' . $filename;
    
    if (!file_exists($path)) {
        $_SESSION['message'][] = __('Requested file is not found');
        redirect('/admin/design.php');
    }
}

$template = file_get_contents($path);


include_once R.'admin/template/header.php';





//echo '<form action="' . $_SERVER['REQUEST_URI'] . '" method="POST">';

?>








        <!-- The padding wrapper may be omitted -->
        <div class="with-padding">
            <span class="button-group large-margin-bottom">
				<a href="/admin/settings.php?m=__sys__" class="button icon-gear green-active"><?php echo __('Common settings'); ?></a>
				<a href="/admin/design.php" class="button icon-palette green-active active"><?php echo $pageTitle; ?></a>
				
				<a href="javascript:void(0)" class="button icon-thumbs green-active">Icons</a>
			</span>
			
                
            
                <!--  Подключаем редактор кода -->
                <link rel="stylesheet" href="/admin/js/codemirror/codemirror.css">
                <script src="/admin/js/codemirror/codemirror.js"></script>
                <script src="/admin/js/codemirror/tmpl.js?<?php echo rand();?>"></script>
                
                
                
                
                <style>
.tmpl-enlarged {
    display: flex;
    flex-direction: column;
    gap: .5rem;
    transition: padding .25s,width .25s,height .25s;
    position: fixed;
    padding: 1rem;
    border-radius: .5rem;
    width: calc(100% - 2rem);
    left: 1rem;
    right: 1rem;
    bottom: 1rem;
    box-shadow: 0 0 1rem 4rem #0008;
    z-index: 11;
    box-sizing: border-box;
    border: 1px solid var(--contentBgBorder)
}

.tmpl-enlarged {
    background-color: white;
}

.tmpl-enlarged {
    top: calc(1rem + 24px);
    height: calc(100vh - 2rem - 24px);
}

.tmpl-enlarged .CodeMirror {
    
    
}

.tmpl-enlarged .CodeMirror-scroll {
    height: calc(100vh - 20rem - 24px);
}

.111tmpl-enlarged .u-codemirror-editor-wrapper {
    width: 100%!important;
    flex-grow: 1;
    position: relative
}

.ttmpl-codes-toggler {
    display: none;
    font-weight: 700
}




.uz.like-sidebar {
    position: fixed;
    width: 35.1rem;
    left: 0;
    bottom: 1rem;
    height: auto;
    background: white;
    overflow: auto;
    transition: left .25s;
    box-sizing: border-box;
    margin: 0;
    padding: 1rem 2rem;
    z-index: 22;
    border: 1px solid #ccc;
    border-left: none;
    border-radius: 0 .5rem .5rem 0;
    top: calc(1rem + 24px);
}

.uz.codes-collapsed.like-sidebar {
    left: -35rem;
    overflow: initial
}

.uz.like-sidebar .ttmpl-codes-toggler {
    display: block;
    position: fixed;
    width: 10rem;
    left: 31rem;
    top: calc(50vh - 2rem);
    box-sizing: border-box;
    margin: 0;
    transform: rotate(270deg);
    line-height: 2rem;
    cursor: pointer;
    transition: left .25s;
    border-radius: 0 0 .5rem .5rem;
}

.uz.codes-collapsed.like-sidebar .ttmpl-codes-toggler {
    left: -4.2rem
}
                
                
                
                
                
                
                
                
                
                
                
                
                    
                    
                </style>
                
                
                
                <form name="tmplForm" class="large-margin-bottom">
                
                
                <div class="columns">
                    <div class="s5 s12-tablet">
                        <div id="tmplDiv" class="scrollable lite-box-shadow" data-scroll-options='{"showOnHover":false}'>
                            <?php include_once R.'admin/template/tmpls_htm.php';?>
                        </div>
                    </div>
                    
                    
                    <div class="s7 s12-tablet">
                        <h2 class="thin underline no-margin"><?php echo $name_tpl; ?></h2>
                        <h3 class="thin margin-top margin-bottom"><?php echo $name_stpl; ?></h3>
                        <p id="eMessage" class="message align-center anthracite">Измените шаблон и нажмите кнопку "Сохранить"</p>
                    </div>
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    <div class="new-row s12 large-box-shadow">
                        
                            <textarea name="tmpl" style="direction:ltr;height:500px;width:100%;zoom:1" wrap="off" 7rows="35" id="tmpl"><?php print h($template); ?></textarea>
                            
                       
                    </div>
                    
                </div>
                <input type="button" name="save" class="button green-gradient glossy float-right" value="Сохранить">
                 </form>
            </div>
                
                
                
                
                
                
                
                <ul class="uz">
                    <h5 class="light"><?php echo __('Global markers') ?></h5>
        <ul>
            <li><b>{{ content }}</b> - <?php echo __('Base content page') ?></li>
            <li><b>{{ title }}</b> - <?php echo __('Title for page') ?></li>
            <li><b>{{ description }}</b> - <?php echo __('Meta-Description') ?></li>
            <li><b>{{ drs_wday }}</b> - <?php echo __('Day mini') ?></li>
            <li><b>{{ drs_date }}</b> - <?php echo __('Date') ?></li>
            <li><b>{{ drs_time }}</b> - <?php echo __('Time') ?></li>
            <li><b>{{ user.name }}</b> - <?php echo __('Nick of current user') ?></li>
            <li><b>{{ user.group }}</b> - <?php echo __('Group of current user') ?></li>
            <li><b>{{ categories }}</b> - <?php echo __('categories list in section') ?></li>
            <li><b>{{ counter }}</b> - <?php echo __('counter') ?></li>
            <li><b>{{ drs_year }}</b> - <?php echo __('Year') ?></li>
            <li><b>{{ powered_by }}</b> - <?php echo __('About powered by') ?></li>
            <li><b>{{ comments }}</b> - <?php echo __('About comments marker') ?></li>
            <li><b>{{ personal_page_link }}</b> - <?php echo __('About personal_page_link marker') ?></li>
        </ul>
                </ul>
                
                
                
                
    
    



<script type="text/javascript">
    // выделение выбраного шаблона в списке
    setTimeout("scrollT()",2000);function scrollT(){var it = document.getElementById('stem').offsetTop-35;var e=document.getElementById('tmplDiv');if (it<0){it=0;}if (it>e.scrollHeight-e.offsetHeight){it=e.scrollHeight-e.offsetHeight+5;}e.scrollTop=it;}
    
    var tmplwords = {
	'FileManager':'Файловый менеджер',
	'Undo':'Отменить',
    'Redo':'Восстановить',
    'LineNumbers':'Нумерация строк',
    'Adjust':'Отрегулировать код',
    'ChangeTheme':'Изменить цветовую схему',
    'HighlightOff':'Отключить подсветку синтаксиса'
    };
    $(window).on("load", function() {editor = new TmplHighlight('tmpl',{mode:'text/html','translate':tmplwords});});
</script>
































<div class="row">
    
    
    
        

    <!-- EDIT FORM -->
    <div class="col s12 m9">
        <a class="btn right modal-trigger" href="#addform"><i class="mdi-action-backup"></i> <?php echo __('Add template') ?></a>
        <h4 class="light"><?php echo __('Template editor').' - '.$filename; ?></h4>

        <div class="row b10tm">
            <textarea title="<?php echo __('Template code') ?>" wrap="off" id="111tmpl" name="111templ"><?php print h($template); ?></textarea>
            <div class="center b10tm">
                <input class="btn" type="submit" name="send" value="<?php echo __('Save') ?>" />
            </div>
        </div>
    </div>

</div>



<!-- ADD FORM -->

<div id="addform" class="111modal modal-fixed-footer">
    <form action="design.php?ac=add_template" method="POST">
    <div class="111modal-content">
        <h4><?php echo __('Adding template') ?></h4>
        <p>
            <div class="input-field col s2">
                <input id="module_name" type="text" name="module" placeholder="<?php echo __('Module path: info') ?>" value="<?php echo $module ?>" required/>
                <label for="module_name"><?php echo __('Module') ?></label>
            </div>
            <div class="input-field col s10">
                <input id="file_title" type="text" name="title" placeholder="<?php echo __('File name: info') ?>" required/>
                <label for="file_title"><?php echo __('Name') ?></label>
            </div>
            <div class="input-field col s12">
                <?php echo __('Template code') ?>
                <textarea name="code" wrap="off" id="new_tmpl"></textarea>
            </div>
        </p>
    </div>
    <div class="111modal-footer">
        <a href="#!" class="111modal-action 111modal-close btn-flat">ОТМЕНИТЬ</a>
        <input type="submit" value="<?php echo __('Add') ?>" name="send" class="btn" />
    </div>
    </form>
</div>



<script type="text/javascript" src="1js/codemirror/codemirror.js"></script>
<script type="text/javascript" src="1js/codemirror/mode/javascript/javascript.js"></script>
<script type="text/javascript" src="1js/codemirror/mode/xml/xml.js"></script>
<script type="text/javascript" src="1js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script type="text/javascript" src="1js/codemirror/mode/css/css.js"></script>

<link rel="StyleSheet" type="text/css" href="11js/codemirror/codemirror.css" />
<script type="text/javascript">
/*

$(document).ready(function(){
    // Редактирование
    var editor = CodeMirror.fromTextArea(document.getElementById("tmpl"), {
        lineNumbers: true,
        matchBrackets: true,
        indentUnit: 4,
        mode: "text/<?php //echo ($type == 'js') ? 'javascript' : $type; ?>"
    });
    editor.setSize('100%', '100%');
    
    // Добавление
    var editor2 = CodeMirror.fromTextArea(document.getElementById("new_tmpl"), {
        lineNumbers: true,
        matchBrackets: true,
        indentUnit: 4,
        mode: "text/html"
    });
    editor2.setSize('100%', '100%');
    
    
    // For modals
    $('.modal-trigger').leanModal();
});*/
</script>


<div class="row">
    <div class="col s12">
        
    </div>
</div>

<?php include_once 'template/footer.php'; ?>

