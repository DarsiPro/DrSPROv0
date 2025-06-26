<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/


include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';

// Clean snippets Cache
$cache = new Cache;
$cache->prefix = 'block';
$cache->cacheDir = ROOT . '/sys/cache/blocks/';
$cache->clean();



$pageTitle = $pageNav = __('Snippets');
$pageNavr = '';
if (isset($_GET['a']) && $_GET['a'] == 'ed') {
    $pageNavr = '<a href="snippets.php">' . __('Add snippet') . '</a>';
}



if (isset($_GET['a']) && $_GET['a'] == 'ed') {

    $id = (!empty($_GET['id'])) ? intval($_GET['id']) : '';
    if($id > 0 && isset($_POST['save']) && isset($_POST['text_edit'])) {
        $sql = $DB->save('snippets', array(
            'body' => $_POST['text_edit'],
            'id' => $id,
        ));
        
        redirect('/admin/snippets.php?a=ed&id=' . $id);
    }
    
    if(isset($_GET['delete'])) {
        $sql = $DB->query("DELETE FROM `" . $DB->getFullTableName('snippets') . "` WHERE id='" . $id . "'");
        $_SESSION['message'][] = __('Snippet deleted');
        redirect('/admin/snippets.php?a=ed');
    }
    if (!empty($id)) {
        $sql = $DB->select('snippets', DB_FIRST, array('cond' => array('id' => $id)));
        if(count($sql) > 0) {
            $content = h($sql[0]['body']);
            $name = h($sql[0]['name']);
         }
    } else {
        $content = __('Select snippet');
    }

    include_once ROOT . '/admin/template/header.php';
?>


    <div class="row">
        <div class="col s12">
        <blockquote>
            <?php echo __('Snippets info'); ?>
        </blockquote>
        </div>
    </div>


    <div class="row">
        <div class="col s12 m3">
            <div class="collection">
            <?php
            $sql = $DB->select('snippets', DB_ALL);
                foreach ($sql as $record) {
                    echo '<a class="collection-item'.((isset($name) && $record['name'] == $name) ? ' active': '').'" href="snippets.php?a=ed&id='
                     . ($record['id']) . '">'
                     . h($record['name']) . '</a>';
                }
            ?>
            </div>
        </div>
        <div class="col s12 m9">
            
            <form action="<?php echo $_SERVER['REQUEST_URI']?>" method="post">
                    <h5><?php echo __('Editing snippets') ?></h5>
                    <?php if (!isset($name)) { ?>
                        <blockquote>
                            <?php echo $content; ?>
                        </blockquote>
                    <?php } else { ?>
                        <div class="input-field col s12">
                            <input id="my_title" name="my_title" type="text" disabled value="<?php echo $name;?>" />
                            <label for="my_title"><?php echo __('Snippet name') ?></label>
                        </div>
                            
                        <div class="input-field col s12">
                            <textarea id="snippet" name="text_edit"><?php echo $content;?></textarea>
                        </div>
                        <div class="input-field col s12">
                            <button class="btn waves-effect waves-light" type="submit" name="save">
                                <?php echo __('Save') ?>
                                <i class="mdi-content-send right"></i>
                            </button>
                            <a class="btn-flat" href="snippets.php?a=ed&id=<?php echo $id ?>&delete=y" onClick="return confirm('<?php echo __('Are you sure?') ?>')">УДАЛИТЬ<i class="mdi-action-delete left"></i></a>
                        </div>
                    <?php } ?>
            </form>
        </div>
    </div>

<?php

} else {
    
    if (isset($_POST['send'])) {
        if (empty($_POST['my_title']) || mb_strlen($_POST['my_text']) < 3 || empty($_POST['my_title']))
            $_SESSION['message'][] = 'Заполните все поля';
        if (empty($_SESSION['message'])) {
            $countchank = $DB->select('snippets', DB_COUNT, array('cond' => array('name' => $_POST['my_title'])));
            if ($countchank == 0) {
                $sql = $DB->save('snippets', array(
                    'name' => $_POST['my_title'],
                    'body' => $_POST['my_text'],
                ));
                
                $_SESSION['message'][] = sprintf(__('Snippet created'), h($_POST['my_title']));
                redirect('/admin/snippets.php?a=ed&id=' . $DB->getLastInsertId());
            } else {
                $_SESSION['message'][] = __('This snippet name exists');
            }
        }
    }
    include_once ROOT . '/admin/template/header.php';
    ?>

    <div class="row">
        <div class="col s12">
        <blockquote>
            <?php echo __('Snippets info'); ?>
        </blockquote>
        </div>
    </div>



    <div class="row">
        <div class="col s12 m3">
            <div class="collection">
            <?php
            $sql = $DB->select('snippets', DB_ALL);
                foreach ($sql as $record) {
                    echo '<a class="collection-item" href="snippets.php?a=ed&id='
                     . ($record['id']) . '">'
                     . h($record['name']) . '</a>';
                }
            ?>
            </div>
        </div>
        <div class="col s12 m9">
            <form action="snippets.php" method="post">
                    <h5><?php echo __('Adding snippet') ?></h5>
                    <div class="input-field col s12">
                        <input id="my_title" name="my_title" type="text" value="<?php if (!empty($_POST['my_title'])) echo h($_POST['my_title']) ?>" />
                        <label for="my_title"><?php echo __('Snippet name') ?></label>
                    </div>
                    <div class="input-field col s12">
                        <textarea id="snippet" name="my_text"><?php if (!empty($_POST['my_text'])) echo h($_POST['my_text']) ?></textarea>
                    </div>
                    <div class="input-field col s12">
                        <button class="btn waves-effect waves-light" type="submit" name="send">
                            <?php echo __('Add') ?>
                            <i class="mdi-content-send right"></i>
                        </button>
                    </div>
            </form>
        </div>
    </div>

<?php }

if (!empty($_GET['id']) || !isset($_GET['a']) || $_GET['a'] !== 'ed') {
 ?>


<script type="text/javascript" src="js/codemirror/codemirror.js"></script>
<!--
<script type="text/javascript" src="js/codemirror/mode/xml/xml.js"></script>
<script type="text/javascript" src="js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script type="text/javascript" src="js/codemirror/mode/css/css.js"></script>
-->
<script type="text/javascript" src="js/codemirror/mode/php/php.js"></script>
<script type="text/javascript" src="js/codemirror/mode/clike/clike.js"></script>

<link rel="StyleSheet" type="text/css" href="js/codemirror/codemirror.css" />
<script type="text/javascript">
$(document).ready(function(){
    // Редактирование
    var editor = CodeMirror.fromTextArea(document.getElementById("snippet"), {
        lineNumbers: true,
        matchBrackets: true,
        indentUnit: 4,
        //mode: "application/x-httpd-php"
        mode: "text/x-php"
    });
    editor.setSize('100%', '100%');
});
</script>


<?php
}

include_once 'template/footer.php';
?>