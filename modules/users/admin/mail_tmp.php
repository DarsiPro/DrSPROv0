<?php
/**
* @project    DarsiPro CMS
* @package    Editor templates for mail
* @url        https://darsi.pro
*/



include_once R.'admin/inc/adm_boot.php';
$pageTitle = __('Editing mail templates');
$Register = \Register::getInstance();

$dir = ROOT . '/data/mail/';

$file_html = $dir . 'main.html'; // формируем ссылку на html шаблон
$file_plain = $dir . 'main.text'; // формируем ссылку на plain text

if (isset($_POST['template_html']) and isset($_POST['template_html'])) {
    if (@file_put_contents($file_html, $_POST['template_html']) and
        @file_put_contents($file_plain, $_POST['template_text'])) {
        $status = true;
    } else {
        $status = false;
    }
}
// читаем содержимое файла
$template_html = @file_get_contents($file_html);
$template_text = @file_get_contents($file_plain);


$pageNav = $pageTitle;
$pageNavr = '';
include_once R.'admin/template/header.php';

if (isset($status) and $status) {
    echo '<script>$(document).ready(function() {Materialize.toast(\'' . __('Template edited') . '\', 4000);});</script>';
} else if (isset($status) and !$status) {
    echo '<script>$(document).ready(function() {Materialize.toast(\'' . __('Some error occurred') . '\', 4000);});</script>';
}
?>

<script type="text/javascript" src="<?php echo WWW_ROOT; ?>/admin/js/codemirror/codemirror.js"></script>
<script type="text/javascript" src="<?php echo WWW_ROOT; ?>/admin/js/codemirror/mode/javascript/javascript.js"></script>
<script type="text/javascript" src="<?php echo WWW_ROOT; ?>/admin/js/codemirror/mode/xml/xml.js"></script>
<script type="text/javascript" src="<?php echo WWW_ROOT; ?>/admin/js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script type="text/javascript" src="<?php echo WWW_ROOT; ?>/admin/js/codemirror/mode/css/css.js"></script>

<link rel="StyleSheet" type="text/css" href="<?php echo WWW_ROOT; ?>/admin/js/codemirror/codemirror.css" />
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

<form method="POST" action="mail_tmp.php" enctype="multipart/form-data">
<div class="row"><div class="row">
    <div class="col s12">
        <div class="input-field col s12">
            <label>
                <?php echo __('HTML version message') ?>
            </label><br><br>
            <textarea wrap="off" id="tmpl" class="materialize-textarea" name="template_html"><?php echo $template_html ?></textarea>
        </div>
        <div class="input-field col s12">
            <textarea name="template_text" length="10000" class="materialize-textarea"><?php echo $template_text ?></textarea>
            <label>
                <?php echo __('Plain text version message') ?>
            </label>
        </div>


        <div class="fixed-action-btn" style="bottom: 45px; right: 24px;">
            <button class="btn-floating btn-large deep-orange form_save tooltipped" data-position="left" data-tooltip="<?php echo __('Save') ?>"  type="submit">
                <i class="large mdi-content-save"></i>
            </button>
        </div>

    </div>
</div></div>
</form>


<?php include_once R.'admin/template/footer.php'; ?>