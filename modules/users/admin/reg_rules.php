<?php
/**
* @project    DarsiPro CMS
* @package    Setting rules on registration
* @url        https://darsi.pro
*/



include_once R.'admin/inc/adm_boot.php';
$pageTitle = __('Registration rules');
$pageNav = $pageTitle;
$pageNavr = '';
include_once R.'admin/template/header.php';
$tash = R.'data/reg_rules.msg';

if (isset($_POST['send'])) {
    if (!empty($_POST['message']) and file_exists($tash)) {
        file_put_contents($tash, $_POST['message']);
    } else {
        echo '<span style="color:red;">' . __('Field "Rules" not exists') . '</span>';
    }
}

if (file_exists($tash)) {
    $current_rules = file_get_contents($tash);
} else {
    $current_rules = '';
}
?>


<div class="row">
    <div class="col s12">
        <blockquote>
            <?php echo __('Info reg rules') ?>
        </blockquote>

        <div class="row">
            <form action="" method="POST">
                <div class="col s12">
                    <div class="input-fields col s12">
                        <label for="message">
                            <?php echo __('Registration rules') ?>
                        </label>
                        <textarea id="message" name="message" class="materialize-textarea"><?php echo (!empty($current_rules)) ? $current_rules : ''; ?></textarea>
                    </div>
                    <div class="input-fields col s12 center">
                        <input class="btn" type="submit" name="send" value="<?php echo __('Save') ?>" />
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
include_once R.'admin/template/footer.php';
?>