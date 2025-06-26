<?php
/**
* @project    DarsiPro CMS
* @package    Authors list (Admin Part)
* @url        https://darsi.pro
*/


include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';



$pageTitle = $page_title = __('Dev. Team');
$pageNav = $page_title;

$pageNavr = '<a href="#wanthere" class="right" onclick="$(\'#wanthere\').openModal()">' . __('I want to be here') . '</a>';
include_once ROOT . '/admin/template/header.php';
?>

<div id="wanthere" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h4><?php echo __('title landing for developers') ?></h4>
        <p>
            <?php echo __('landing for developers') ?>
        </p>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-action modal-close waves-effect btn-flat"><?php echo __('Cancel') ?></a>
    </div>
</div>


<div class="row">
    <div class="col s12">
        <div class="divider"></div>
        <div class="section">
            <h5><?php echo __('Idea by') ?></h5>
            <p>Andrey</p>
        </div>
        <div class="divider"></div>
        <div class="section">
            <h5><?php echo __('Programmers') ?></h5>
            <p>Andrey</p>
        </div>
        <div class="divider"></div>
        <div class="section">
            <h5><?php echo __('Testers and audit') ?></h5>
            <p>Andrey</p>
        </div>
        <div class="divider"></div>
        <div class="section">
            <h5><?php echo __('Marketing') ?></h5>
            <p>Andrey</p>
        </div>
        <div class="divider"></div>
        <div class="section">
            <h5><?php echo __('Design and Templates') ?></h5>
            <p>Andrey</p>
        </div>
        <div class="divider"></div>
        <div class="section">
            <h5><?php echo __('Specialists by Security') ?></h5>
            <p>Andrey</p>
        </div>
        <div class="divider"></div>
        <div class="section">
            <h5><?php echo __('Additional Software') ?></h5>
            <p>Andrey</p>
        </div>
    </div>
</div>

<?php
include_once 'template/footer.php';
?>