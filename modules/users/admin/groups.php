<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/


include_once R.'admin/inc/adm_boot.php';

$pageTitle = __('Group editor');
$pageNav = $pageTitle;
$pageNavr = '<a href="#Add_group" class="modal-trigger">' . __('Add group') . '</a>&nbsp;|&nbsp;<a href="../../admin/rules.php">' . __('Rules editor') . '</a>';



$acl_groups = \ACL::get_group_info();

//create tmp array with groups and cnt users in them.
$errors = array();
$groups = array();
$popups = '';


if (!empty($acl_groups)) {
    $groups = $acl_groups;
    foreach ($acl_groups as $key => $value) {
        $groups[$key] = array();
        $groups[$key]['title'] = $value['title'];
        $groups[$key]['color'] = $value['color'];
        $groups[$key]['cnt_users'] = $DB->select('users', DB_COUNT, array('cond' => array('status' => $key)));
    }
}

$allowed_colors = array('#000000', '#EF1821', '#368BEB', '#959385', '#FBCA0B', '#00AA2B', '#9B703F', '#FAAA3C');

//move users into other group
if (!empty($_GET['ac']) && $_GET['ac'] == 'move') {
    if (isset($_POST['id']) && is_numeric($_POST['id']) && (int)$_POST['id'] !== 0) {
        $from = (int)$_POST['id'];
        if (!empty($_POST['to']) && is_numeric($_POST['to'])) {
            if (array_key_exists($_POST['to'], $acl_groups)) {
                $DB->save('users', array('status' => $_POST['to']), array('status' => $from));
            }
        }
    }

//edit group
} else if (!empty($_GET['ac']) && $_GET['ac'] == 'edit') {
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $id = (int)$_POST['id'];
        if (!empty($_POST['title'])) {
            if (!in_array($_POST['color'], $allowed_colors)) $errors[] = __('Disallow color');
            if (mb_strlen($_POST['title']) > 100 || mb_strlen($_POST['title']) < 2) {
                $errors[] = __('Very short "Group name"');
            }

            if (!preg_match('#^[\w\d-_a-zа-я0-9 ]+$#ui', trim($_POST['title']))) {
                $errors[] = __('Wrong chars in field "Group name"');
            }
            if (empty($errors)) {
                if (key_exists($id, $acl_groups)) {
                    $acl_groups[$id] = array('title' => h($_POST['title']), 'color' => h($_POST['color']));
                    \ACL::save_groups($acl_groups);
                }
            }
        } else {
            $errors[] = __('"Group name" is exists');
        }
    }

//delete group
} else if (!empty($_GET['ac']) && $_GET['ac'] == 'delete') {
    if (isset($_GET['id']) && is_numeric($_GET['id']) && (int)$_GET['id'] !== 0 && (int)$_GET['id'] !== 1) {
        $id = (int)$_GET['id'];
        if ($groups[$_GET['id']]['cnt_users'] > 0) {
            $errors[] = __('You must move users! Group not exists');
        } else {
            unset($acl_groups[$_GET['id']]);
            \ACL::save_groups($acl_groups);
        }
    }

//add group
} else if (!empty($_GET['ac']) && $_GET['ac'] == 'add') {
    if (!empty($_POST['title']) && !empty($_POST['color'])) {
        if (!in_array($_POST['color'], $allowed_colors)) $errors[] = __('Disallow color');
        if (mb_strlen($_POST['title']) > 100 || mb_strlen($_POST['title']) < 2) {
            $errors[] = __('Very short "Group name"');
        }
        if (!preg_match('#^[\w\d-_a-zа-я0-9 ]+$#ui', $_POST['title'])) {
            $errors[] = __('Wrong chars in field "Group name"');
        }
        if (empty($errors)) {
            $acl_groups[] = array('title' => h($_POST['title']), 'color' => h($_POST['color']));
            \ACL::save_groups($acl_groups);
        }
    } else {
        $errors[] = __('"Group name" is exists');
    }



}

if(!empty($errors)) {
    $_SESSION['message'] = array_merge($_SESSION['message'], $errors);
    redirect('/admin/users/groups.php');
}

include_once R.'admin/template/header.php';

?>



    <div class="modal" id="Add_group" style="overflow:visible">
        <form action="groups.php?ac=add" method="POST">
        <div class="modal-content">
            <h5 class="light"><?php echo __('Adding group') ?></h5>
            <div>
                <div class="input-field col s6">
                    <input id="title" type="text" name="title" />
                    <label for="title">
                        <?php echo __('Group name') ?>
                    </label>
                </div>
                <div class="input-field col s6">
                    <select name="color">
                        <option style="color:#000000;" value="#000000"><?php echo __('Black') ?></option>
                        <option style="color:#EF1821;" value="#EF1821"><?php echo __('Red') ?></option>
                        <option style="color:#368BEB;" value="#368BEB"><?php echo __('Blue') ?></option>
                        <option style="color:#959385;" value="#959385"><?php echo __('Gray') ?></option>
                        <option style="color:#FBCA0B;" value="#FBCA0B"><?php echo __('Yellow') ?></option>
                        <option style="color:#00AA2B;" value="#00AA2B"><?php echo __('Green') ?></option>
                        <option style="color:#9B703F;" value="#9B703F"><?php echo __('Brown') ?></option>
                        <option style="color:#FAAA3C;" value="#FAAA3C"><?php echo __('Orange') ?></option>
                    </select>
                    <label>
                        <?php echo __('Color group') ?>
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="submit" value="<?php echo __('Save') ?>" name="send" class="btn" />
            <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
        </div>
        </form>
    </div>




    <?php if (!empty($groups)): ?>
        <?php foreach ($groups as $key => $value): ?>
            <?php if ($key !== 0): ?>
                <!-- FOR EDIT -->
                <div class="modal" id="<?php echo h($key) ?>_Edit" style="overflow:visible">
                    <form action="groups.php?ac=edit" method="POST">
                    <div class="modal-content">
                        <h5 class="light"><?php echo __('Editing group') ?></h5>
                        <div>
                            <div class="input-field col s6">
                                <input type="hidden" name="id" value="<?php echo $key ?>" />
                                <input id="title_<?php echo h($key) ?>" type="text" name="title"  value="<?php echo $value['title'] ?>" />
                                <label for="title_<?php echo h($key) ?>">
                                    <?php echo __('Group name') ?>
                                </label>
                            </div>
                            <div class="input-field col s6">
                                <select name="color">
                                    <option style="color:#000000;" value="#000000" <?php if($value['color'] == '#000000') echo 'selected="selected"' ?>><?php echo __('Black') ?></option>
                                    <option style="color:#EF1821;" value="#EF1821" <?php if($value['color'] == '#EF1821') echo 'selected="selected"' ?>><?php echo __('Red') ?></option>
                                    <option style="color:#368BEB;" value="#368BEB" <?php if($value['color'] == '#368BEB') echo 'selected="selected"' ?>><?php echo __('Blue') ?></option>
                                    <option style="color:#959385;" value="#959385" <?php if($value['color'] == '#959385') echo 'selected="selected"' ?>><?php echo __('Gray') ?></option>
                                    <option style="color:#FBCA0B;" value="#FBCA0B" <?php if($value['color'] == '#FBCA0B') echo 'selected="selected"' ?>><?php echo __('Yellow') ?></option>
                                    <option style="color:#00AA2B;" value="#00AA2B" <?php if($value['color'] == '#00AA2B') echo 'selected="selected"' ?>><?php echo __('Green') ?></option>
                                    <option style="color:#9B703F;" value="#9B703F" <?php if($value['color'] == '#9B703F') echo 'selected="selected"' ?>><?php echo __('Brown') ?></option>
                                    <option style="color:#FAAA3C;" value="#FAAA3C" <?php if($value['color'] == '#FAAA3C') echo 'selected="selected"' ?>><?php echo __('Orange') ?></option>
                                </select>
                                <label>
                                    <?php echo __('Color group') ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="submit" value="<?php echo __('Save') ?>" name="send" class="btn" />
                        <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
                    </div>
                    </form>
                </div>




                <!-- FOR MOVE -->
                <div class="modal" id="<?php echo h($key) ?>_Move" style="overflow:visible">
                    <form action="groups.php?ac=move" method="POST">
                    <div class="modal-content">
                        <h5 class="light"><?php echo __('Moving users') ?></h5>
                        <div class="input-field">
                            <input type="hidden" name="id" value="<?php echo $key ?>" />
                            <?php
                            $select = '<select name="to">';
                            if (!empty($groups)) {
                                foreach($groups as $sk => $sv) {
                                    if ($sk != $key) {
                                        $select .= '<option value="' . $sk . '">' . h($sv['title']) . '</option>';
                                    }
                                }
                            }
                            $select .= '</select>';
                            ?>
                            <?php echo $select; ?>
                            <label>
                                <?php echo __('Moving to') ?>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="submit" value="<?php echo __('Save') ?>" name="send" class="btn" />
                        <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
                    </div>
                    </form>
                </div>

            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>




    <div class="row">
        <table>
        <thead>
            <th>ID</th>
            <th><?php echo __('Group') ?></th>
            <th><?php echo __('Users cnt') ?></th>
            <th width="30%"><?php echo __('Activity') ?></th>
        </thead>
        <tbody>


    <?php

    if (!empty($groups)) {
        foreach ($groups as $key => $value) {
            if ($key !== 0) {
    ?>
            <tr>
                <td><?php echo h($key); ?></td>
                <td><font color="<?php echo h($value['color']); ?>"><?php echo h($value['title']); ?></font></td>
                <td><?php echo h($value['cnt_users']); ?></td>
                <td>
                    <a class="btn modal-trigger" title="<?php echo __('Edit') ?>" href="#<?php echo h($key) ?>_Edit"><i class="mdi-action-settings"></i></a>
                    <a class="btn modal-trigger" title="<?php echo __('Move') ?>" href="#<?php echo h($key) ?>_Move"><i class="mdi-action-swap-vert"></i></a>
                    <?php if ($key !== 0 && $key !== 1): ?>
                    <a class="btn red" title="<?php echo __('Delete') ?>" href="groups.php?ac=delete&id=<?php echo h($key) ?>" onClick="return confirm('<?php echo __('Are you sure?') ?>')"><i class="mdi-action-delete"></i></a>
                    <?php endif; ?>

                </td>
            </tr>




    <?php    } else { ?>

            <tr>
                <td><?php echo h($key); ?></td>
                <td><?php echo h($value['title']); ?></td>
                <td> - </td>
                <td>
                    -
                </td>
            </tr>

    <?php
            }
        }
    } else {
    ?>
            <tr>
                <td colspan="4"><?php echo __('Not found groups') ?></td>
            </tr>

    <?php
    }

    ?>
        <tbody>
    </table>
    </div>
    </form>

<script>
$(document).ready(function() {
    $('.modal-trigger').leanModal();
    $('select').material_select();
});
</script>

<?php
include_once R.'admin/template/footer.php';
?>