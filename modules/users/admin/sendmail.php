<?php
/**
 * @project     DarsiPro CMS
 * @package     Mails send to users
 * @url         https://darsi.pro
 */


include_once R.'admin/inc/adm_boot.php';



$pageTitle = __('Mass mailing');
$pageNav = $pageTitle;
$pageNavr = '';


$users_groups = \ACL::get_group_info();
$count_usr = $DB->select('users', DB_ALL, array(
    'group' => 'status',
    'fields' => array(
        'COUNT(*) as cnt',
        'status'
    ),
));
foreach ($users_groups as $id => $gr) {
    $users_groups[$id]['cnt'] = 0;
    foreach ($count_usr as $key => $val) {
        if ($id == $val['status']) {
            $users_groups[$id]['cnt'] = $val['cnt'];
            break;
        }
    }
}
if (isset($users_groups[0])) unset($users_groups[0]);

$all_users_cnt = 0;
foreach ($count_usr as $val) {
    $all_users_cnt += $val['cnt'];
}



if (isset($_POST['send'])) {
    if (!empty($_POST['template_html'])
    && !empty($_POST['template_text'])
    && !empty($_POST['subject'])
    && !empty($_POST['groups'])
    && count($_POST['groups']) > 0) {

        $status_ids = array();
        foreach ($_POST['groups'] as $group) {
            $status_ids[] = intval($group);
        }
        $status_ids = array_unique($status_ids);
        $status_ids = implode(', ', $status_ids);


        $mail_list = $DB->select('users', DB_ALL, array(
            'cond' => array(
                '`status` IN (' . $status_ids . ')',
            ),
        ));



        if (count($mail_list) > 0) {
            $from = (!empty($_POST['from'])) ? trim($_POST['from']) : \Config::read('admin_email');
            $subject = trim($_POST['subject']);


            $mailer = new \DrsMail();
            $mailer->setFrom($from);
            $mailer->setSubject($subject);
            $mailer->setContentHtml($_POST['template_html']);
            $mailer->setContentText($_POST['template_text']);

            $n = 0;
            $start_time = microtime(true);
            foreach ($mail_list as $result) {
                $mailer->setTo($result['email']);
                if ($mailer->sendMail()) {
                    $n++;
                }
            }

            if (empty($error)) {
                $_SESSION['message'][] = __('Mails are sent') . ': ' . $n;
                $_SESSION['message'][] = sprintf(__('Time spent'),round(microtime(true) - $start_time, 4) . ' сек.');
            }
        } else {
            $_SESSION['message'][] = '<span style="color:red;">' . __('Users not found') . '</span>';
        }
    } else {
        $_SESSION['message'][] = '<span style="color:red;">' . __('Needed fields are empty') . '</span>';
    }

    //redirect('/admin/users/sendmail.php');
}



include_once R.'admin/template/header.php';
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



<div class="row">
    <div class="col s12">
    <blockquote>
        <span><?php echo __('Available emails') ?>:</span> <?php echo $all_users_cnt; ?><br />
        <span><?php echo __('Max email length') ?>:</span> 10000 <?php echo __('Symbols') ?><br /><br />

        <h5 class="light"><?php echo __('In the mail body available below markers') ?>:</h5>
        <ul>

            <li><b>%(to)</b> - <?php echo __('To') ?></li>
            <li><b>%(from)</b> - <?php echo __('From') ?></li>
            <li><b>%(subject)</b> - <?php echo __('Subject') ?></li>
            <li><b>%(content_html)</b> - <?php echo __('Content html version') ?></li>
            <li><b>%(content_text)</b> - <?php echo __('Content text version') ?></li>
            <li><b>%(site_title)</b> - <?php echo __('Site title') ?></li>
            <li><b>%(site_desc)</b> - <?php echo __('Site description') ?></li>
            <li><b>%(site_url)</b> - <?php echo __('Site url') ?></li>

        </ul>
    </blockquote>
    </div>
</div>






<form action="" method="POST">
<div class="row">
    <!--<div class="add-cat-butt" onClick="openPopup('sec');"><div class="add"></div>Список подписчиков</div>-->
    <div class="row">
        <div class="col s12">
            <h5 class="light"><?php echo __('Mass mailing') ?></h5>
            <div class="input-field col s12 row">
                <label class="row" style="position:static">
                    <?php echo __('Send to groups') ?>
                </label>
                <div class="row">
                        <?php foreach ($users_groups as $id => $group):  $chb_id = md5(rand(0, 9999) . $id); ?>
                            <span class="b15lp">
                                <input id="<?php echo $chb_id; ?>" type="checkbox" name="groups[<?php echo (int)$id; ?>]" value="<?php echo (int)$id; ?>" checked="checked" /><label for="<?php echo $chb_id; ?>"><?php echo h($group['title']) . ' (' . $group['cnt'] . ')'; ?></label>
                            </span>
                        <?php endforeach; ?>
                </div>
            </div>
            <div class="input-field col s6">
                <input id="subject" length="120" type="text" name="subject" value="<?php echo (isset($_POST['subject'])) ? $_POST['subject'] : '' ?>" />
                <label for="subject">
                    <?php echo __('Subject') ?>
                </label>
            </div>
            <div class="input-field col s6">
                <input id="from" length="120" type="text" name="from" value="<?php
                    echo (\Config::read('admin_email')) ? \Config::read('admin_email') : ''; ?>" />
                <label for="from">
                    <?php echo __('Sender\'s email') ?>
                </label>
            </div>
            <div class="input-field col s12">
                <label>
                    <?php echo __('HTML version message') ?>
                </label><br><br>
                <textarea wrap="off" id="tmpl" class="materialize-textarea" name="template_html"><?php echo (isset($_POST['template_html'])) ? $_POST['template_html'] : '' ?></textarea>
            </div>
            <div class="input-field col s12">
                <textarea name="template_text" length="10000" class="materialize-textarea"><?php echo (isset($_POST['template_text'])) ? $_POST['template_text'] : '' ?></textarea>
                <label>
                    <?php echo __('Plain text version message') ?>
                </label>
            </div>
            <div class="input-field col s12">
                <input class="btn" type="submit" name="send" value="<?php echo __('Send') ?>" />
            </div>
        </div>
    </div>
</div>
</form>

<?php

include_once R.'admin/template/footer.php';
?>

