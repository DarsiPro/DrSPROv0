<?php
/**
* @project    DarsiPro CMS
* @package    Editor templates for errors
* @url        https://darsi.pro
*/

include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';
$pageTitle = __('Editing error pages');
$Register = Register::getInstance();


if (isset($_GET['tmp']))
    $name = $_GET['tmp'];
else
    $name = 'default';
    
switch ($name) {
    case 'hack':
        $pageTitle = __('error template for hacker');
        break;
    case '404':
        $pageTitle = __('error template for 404');
        break;
    case '403':
        $pageTitle = __('error template for 403');
        break;
    case 'ban':
        $pageTitle = __('error template for ban');
        break;
    default:
        $name = 'default';
        $pageTitle = __('error template for default');
        break;
    
}

if (isset($_POST['template']))
    @file_put_contents(ROOT . '/data/errors/' . $name . '.html', $_POST['template']);

$template = @file_get_contents(ROOT . '/data/errors/' . $name . '.html');

include_once ROOT . '/admin/template/header.php';
?>

<div class="row">
    <div class="col s12">
    <blockquote>
        <h5 class="light"><?php echo __('Markers for error pages') ?>:</h5>
        <ul>
            <li><b>{{ error.site_domain }}</b> - <?php echo __('Domain') ?></li>
            <li><b>{{ error.site_title }}</b> - <?php echo __('Name your site') ?></li>
            <li><b>{{ error.code }}</b> - <?php echo __('Error code') ?></li>
        </ul>
    </blockquote>
    </div>
</div>

<div class="row">
    <div class="col s12 m3">
        <div class="collection">
            <a class="collection-item<?php if ($name == 'hack') echo ' active';?>" href="errors_tmp.php?tmp=hack"><?php echo __('error template for hacker'); ?></a>
            <a class="collection-item<?php if ($name == '403') echo ' active';?>" href="errors_tmp.php?tmp=403"><?php echo __('error template for 403'); ?></a>
            <a class="collection-item<?php if ($name == '404') echo ' active';?>" href="errors_tmp.php?tmp=404"><?php echo __('error template for 404'); ?></a>
            <a class="collection-item<?php if ($name == 'ban') echo ' active';?>" href="errors_tmp.php?tmp=ban"><?php echo __('error template for ban'); ?></a>
            <a class="collection-item<?php if ($name == 'default') echo ' active';?>" href="errors_tmp.php?tmp=default"><?php echo __('error template for default'); ?></a>
        </div>
    </div>
    <div class="col s12 m9">
        <form action="<?php echo $_SERVER['REQUEST_URI'];?>" method="POST">
                <h5><?php echo $pageTitle ?></h5>
                <div class="input-field col s12">
                    <textarea name="template" id="tmpl"><?php print h($template); ?></textarea>
                </div>
                <div class="input-field col s12">
                    <button class="btn waves-effect waves-light" type="submit" name="send">
                        <?php echo __('Save') ?>
                        <i class="mdi-content-send right"></i>
                    </button>
                </div>
        </form>
    </div>
</div>


<script type="text/javascript" src="js/codemirror/codemirror.js"></script>
<script type="text/javascript" src="js/codemirror/mode/javascript/javascript.js"></script>
<script type="text/javascript" src="js/codemirror/mode/xml/xml.js"></script>
<script type="text/javascript" src="js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script type="text/javascript" src="js/codemirror/mode/css/css.js"></script>

<link rel="StyleSheet" type="text/css" href="js/codemirror/codemirror.css" />
<script type="text/javascript">
$(document).ready(function(){
    var editor = CodeMirror.fromTextArea(document.getElementById("tmpl"), {
        lineNumbers: true,
        matchBrackets: true,
        indentUnit: 4,
        mode: "text/html"
    });
    editor.setSize('100%', '100%');
});
</script>


<?php include_once 'template/footer.php'; ?>