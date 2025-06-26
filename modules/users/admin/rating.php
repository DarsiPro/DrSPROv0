<?php
/**
* @project    DarsiPro CMS
* @package    Setting rating
* @url        https://darsi.pro
*/


include_once R.'admin/inc/adm_boot.php';


if (isset($_POST['send'])) {
    $errors = array();
    //Проводим валидациюданных
    for ($i = 0; $i < 11; $i++) {
        if (empty($_POST['rat'.$i]) || strlen($_POST['rat'.$i]) < 1) $errors['rat'.$i] = __('Very short value');
        if ($i > 0) {
            if (empty($_POST['cond'.$i]) || strlen($_POST['cond'.$i]) < 1) $errors['cond'.$i] = __('Very short value');
            elseif (!is_numeric($_POST['cond'.$i])) $errors['cond'.$i] = __('Value must be numeric');
        }
    }

    if (empty($errors)) {
        $TempSet['rat0'] = $_POST['rat0'];
        $TempSet['cond1'] = $_POST['cond1'];
        $TempSet['rat1'] = $_POST['rat1'];
        $TempSet['cond2'] = $_POST['cond2'];
        $TempSet['rat2'] = $_POST['rat2'];
        $TempSet['cond3'] = $_POST['cond3'];
        $TempSet['rat3'] = $_POST['rat3'];
        $TempSet['cond4'] = $_POST['cond4'];
        $TempSet['rat4'] = $_POST['rat4'];
        $TempSet['cond5'] = $_POST['cond5'];
        $TempSet['rat5'] = $_POST['rat5'];
        $TempSet['cond6'] = $_POST['cond6'];
        $TempSet['rat6'] = $_POST['rat6'];
        $TempSet['cond7'] = $_POST['cond7'];
        $TempSet['rat7'] = $_POST['rat7'];
        $TempSet['cond8'] = $_POST['cond8'];
        $TempSet['rat8'] = $_POST['rat8'];
        $TempSet['cond9'] = $_POST['cond9'];
        $TempSet['rat9'] = $_POST['rat9'];
        $TempSet['cond10'] = $_POST['cond10'];
        $TempSet['rat10'] = $_POST['rat10'];

        $cfg = \Config::read('all', 'users');
        $cfg['stars'] = $TempSet;
        \Config::write($cfg, 'users');

        redirect("/admin/users/rating.php");
    }

}


$pageTitle = __('Users rank');

include_once R.'admin/template/header.php';
$result = $params = \Config::read('users.stars');
?>




<form method="POST" action="rating.php">
<div class="row">
    <div class="row">
        <div class="col">
            <h4 class="light"><?php echo $pageTitle ?></h4>
            <div class="input-field col s12">
                <input id="rat0" type="text" name="rat0" value="<?php echo (!empty($result['rat0'])) ? $result['rat0'] : ''; ?>" class="validate
                <?php echo (!empty($errors['rat0'])) ? ' invalid"><small class="red-text error-info">'.$errors['rat0'].'</small>' : '">'; ?>
                <label for="rat0">
                    <?php echo __('Not rank') ?>
                    <small><?php echo __('Not rank info') ?></small>
                </label>
            </div>


            <?php for ($i = 1; $i < 11; $i++): ?>
            <div class="input-field col s12">
                <h6 class="row light">
                    <?php echo __('Rank №').' '.$i; ?>
                </h6>
                <div class="row">
                    <div class="input-field col s6">
                        <input id="cond<?php echo $i ?>" type="text" name="cond<?php echo $i ?>" value="<?php echo (!empty($result['cond'.$i])) ? intval($result['cond'.$i]) : 10*$i; ?>" class="validate
                        <?php echo (!empty($errors['cond'.$i])) ? ' invalid"><small class="red-text error-info">'.$errors['cond'.$i].'</small>' : '">'; ?>
                        <label for="cond<?php echo $i ?>">
                            <?php echo __('Messages cnt') ?>
                        </label>
                    </div>
                    <div class="input-field col s6">
                        <input id="rat<?php echo $i ?>" type="text" name="rat<?php echo $i ?>" value="<?php echo (!empty($result['rat'.$i])) ? h($result['rat'.$i]) : ''; ?>" class="validate
                        <?php echo (!empty($errors['rat'.$i])) ? ' invalid"><small class="red-text error-info">'.$errors['rat'.$i].'</small>' : '">'; ?>
                        <label for="rat<?php echo $i ?>">
                            <?php echo __('Rank') ?>
                        </label>
                    </div>
                </div>
            </div>
            <?php endfor ?>


            <div class="input-field col s12">
                <input class="btn" type="submit" name="send" value="<?php echo __('Save') ?>" />
            </div>
        </div>
    </div>
</div>
</form>



<?php
include_once R.'admin/template/footer.php';
?>