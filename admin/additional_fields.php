<?php
/**
* @project    DarsiPro CMS
* @package    Additional Fields (Admin Part)
* @url        https://darsi.pro
*/



include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';


// check support
if (
    empty($_GET['m'])
    || !Config::read($_GET['m'].".std_admin_pages.additional_fields")
    and !in_array($_GET['m'], Config::read('tables_with_addfields'))
) {
    $_SESSION["message"][] = __('Some error occurred');
    redirect('/admin/');
}


$Register = Register::getInstance();
$Register['module'] = $_GET['m'];

$pageTitle = __($_GET['m']) . ' - ' . __('Additional fields');

// Know action
if (!isset($_GET['ac'])) $_GET['ac'] = 'index';
$permis = array('add', 'del', 'index', 'edit');
if (!in_array($_GET['ac'], $permis)) $_GET['ac'] = 'index';

switch($_GET['ac']) {
    case 'del':
        $content = DrsDelete();
        break;
    case 'add':
        $content = DrsAdd();
        break;
    case 'edit':
        $content = DrsEdit();
        break;
    default:
}




if ($_GET['ac'] == 'index'):
    $where = array('module' => $_GET['m']);
    $fields = $DB->select('add_fields', DB_ALL, array('cond' => $where));
    $AddFields = new DrsAddFields;
    if (count($fields) > 0)
        $inputs = $AddFields->getInputs($fields, $_GET['m']);




    $pageNav = $pageTitle;
    $pageNavr = '<a href="#addCat" class="btn right modal-trigger"><i class="mdi-content-add left"></i>'.__('Add').'</a>';
    //echo $head
    include_once ROOT . '/admin/template/header.php';
?>

    <div class="modal modal-fixed-footer" id="addCat">
        <form action="additional_fields.php?m=<?php echo $_GET['m'] ?>&ac=add" method="POST">
        <div class="modal-content">
            <h4><?php echo __('Adding field') ?></h4>
            
            
            <div class="input-field col s12">
                <select name="type" class="select_type_field">
                    <option value="text">TEXT</option>
                    <option value="checkbox">CHECKBOX</option>
                    <option value="select">SELECT</option>
                </select>
                <label><?php echo __('Type of field') ?></label>
            </div>
            <div class="input-field col s12">
                <input id="label" type="text" name="label" value="" required/>
                <label for="label"><?php echo __('Visible name of field') ?></label>
                <small class="right"><?php echo __('Will be displayed in errors') ?></small>
            </div>
            <div class="input-field col s12 field_option_of_size">
                <input id="size" type="text" name="size" value="70" placeholder="<?php echo __('Max length of saving data') ?>"/>
                <label for="size"><?php echo __('Max length') ?><label>
            </div>
            <div class="field_option_of_params col s12" style="display:none">
                <h6><?php echo __('Params') ?>:</h6>
                <small><?php echo __('Read more in the doc') ?></small>
                <div class="adding_field">
                    <div class="input-field col s6">
                        <input type="text" name="title0" value="" placeholder="<?php echo __('Name') ?>"/>
                    </div>
                    <div class="input-field col s6">
                        <input type="text" name="value0" value="" placeholder="<?php echo __('Value') ?>"/>
                    </div>
                </div>
                <div class="center">
                    <a class="btn" href="#!" onclick="addselect(this);"><i class="mdi-content-add left"></i><?php echo __('Add param') ?></a>
                </div>
            </div>
            <div class="input-field col s12 field_option_of_pattern">
                <input id="pattern" type="text" name="pattern" value="" />
                <label for="pattern"><?php echo __('Pattern for value') ?><label>
            </div>
            <div class="input-field col s12 b15bm">
                <input id="required" type="checkbox" name="required" value="1"/>
                <label for="required"><?php echo __('Required field') ?></label>
            </div>
            
            
            
        </div>
        <div class="modal-footer">
            <input type="submit" value="<?php echo __('Save') ?>" name="send" class="btn" />
            <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
        </div>
        </form>
    </div>





<?php 
if (!empty($fields)): ?>
<?php foreach($fields as $field): ?>
    <?php
        $params = (!empty($field['params'])) ? unserialize($field['params']) : array();
        $values = (!empty($params['values'])) ? $params['values'] : '-';

        $required = (!empty($params['required'])) 
        ? '<span style="color:red;">' . __('Yes') . '</span>' 
        : '<span style="color:blue;">' . __('No') . '</span>';
    ?>
    
    <div class="modal modal-fixed-footer" id="edit_<?php echo $field['id'] ?>">
        <form action="additional_fields.php?m=<?php echo $_GET['m'] ?>&ac=edit&id=<?php echo $field['id'] ?>" method="POST">
        <div class="modal-content">
            <h4><?php echo __('Editing field') ?></h4>
            <div class="input-field col s12">
                <select name="type" class="select_type_field">
                    <option value="text"<?php if ($field['type'] == 'text') echo ' selected'; ?>>TEXT</option>
                    <option value="checkbox"<?php if ($field['type'] == 'checkbox') echo ' selected'; ?>>CHECKBOX</option>
                    <option value="select"<?php if ($field['type'] == 'select') echo ' selected'; ?>>SELECT</option>
                </select>
                <label><?php echo __('Type of field') ?></label>
            </div>
            <div class="input-field col s12">
                <input id="label" type="text" name="label" value="<?php echo h($field['label']) ?>" required/>
                <label for="label"><?php echo __('Visible name of field') ?></label>
                <small class="right"><?php echo __('Will be displayed in errors') ?></small>
            </div>
            <div class="input-field col s12 field_option_of_size"<?php if($field['type'] == 'checkbox' or $field['type'] == 'select') echo ' style="display:none;"' ?>>
                <input id="size" type="text" name="size" value="<?php echo (!empty($field['size'])) ? h($field['size']) : ''; ?>" placeholder="<?php echo __('Max length of saving data') ?>"/>
                <label for="size"><?php echo __('Max length') ?><label>
            </div>
            <div class="field_option_of_params col s12"<?php if($field['type'] == 'checkbox' or $field['type'] == 'text') echo ' style="display:none;"' ?>>
                <h6><?php echo __('Params') ?>:</h6>
                <small><?php echo __('Read more in the doc') ?></small>
                
                <?php if (!empty($values) && $values != "-"): ?>
                    <?php $n = 0; ?>
                    <?php foreach($values as $value => $title): ?>
                        <div class="adding_field">
                            <div class="input-field col s6">
                                <input type="text" name="title<?php echo $n ?>" value="<?php echo $title ?>" placeholder="<?php echo __('Name') ?>"/>
                            </div>
                            <div class="input-field col s6">
                                <input type="text" name="value<?php echo $n ?>" value="<?php echo $value ?>" placeholder="<?php echo __('Value') ?>"/>
                            </div>
                        </div>
                        <?php $n++; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="center">
                    <a class="btn" href="#!" onclick="addselect(this);"><i class="mdi-content-add left"></i><?php echo __('Add param') ?></a>
                </div>
            </div>
            <div class="input-field col s12 field_option_of_pattern"<?php if($field['type'] == 'checkbox' or $field['type'] == 'select') echo ' style="display:none;"' ?>>
                <input id="pattern" type="text" name="pattern" value="<?php echo (!empty($params['pattern'])) ? h($params['pattern']) : ''; ?>" />
                <label for="pattern"><?php echo __('Pattern for value') ?><label>
            </div>
            <div class="input-field col s12 b15bm">
                <input id="required<?php echo $field['id'] ?>" type="checkbox" name="required" value="1"<?php if(!empty($params['required'])) echo ' checked="checked"' ?>/>
                <label for="required<?php echo $field['id'] ?>"><?php echo __('Required field') ?></label>
            </div>
            
            
            
        </div>
        <div class="modal-footer">
            <input type="submit" value="<?php echo __('Save') ?>" name="send" class="btn" />
            <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
        </div>
        </form>
    </div>




<?php endforeach; ?>
<?php endif; ?>




<script>
function addselect(el) {
    var n,el;
    
    $(el).closest('.field_option_of_params').find('.adding_field').each(function() {
        n = $(this).find("input").attr('name').substr(5);
    });
    
    n++;
    
    $(el).parent("div").before('<div class="adding_field">\
                    <div class="input-field col s6">\
                        <input type="text" name="title' + n + '" value="" placeholder="<?php echo __('Name') ?>"/>\
                    </div>\
                    <div class="input-field col s6">\
                        <input type="text" name="value' + n + '" value="" placeholder="<?php echo __('Value') ?>"/>\
                    </div>\
                </div>')
}


$(document).ready(function(){
    // the "href" attribute of .modal-trigger must specify the modal ID that wants to be triggered
    $('.modal-trigger').leanModal();
    $('select').material_select();
    
    
    
    $("ul.dropdown-content.select-dropdown").on("click","li", function(){
        type = $(this).find("span").text();

        option_of_size = $(this).closest(".modal").find(".field_option_of_size");
        option_of_params = $(this).closest(".modal").find(".field_option_of_params");
        option_of_pattern = $(this).closest(".modal").find(".field_option_of_pattern");
        switch (type) {
            case 'TEXT':
                option_of_params.hide();
                option_of_size.show();
                option_of_pattern.show();
                break;
            case 'CHECKBOX':
                option_of_params.hide();
                option_of_size.hide();
                option_of_pattern.hide();
                break;
            case 'SELECT':
                option_of_params.show();
                if (!option_of_params.find('.adding_field')[0])
                    option_of_params.find(".center")
                    .before('<div class="adding_field">\
                                                <div class="input-field col s6">\
                                                    <input type="text" name="title0" value="" placeholder="<?php echo __('Name') ?>"/>\
                                                </div>\
                                                <div class="input-field col s6">\
                                                    <input type="text" name="value0" value="" placeholder="<?php echo __('Value') ?>"/>\
                                                </div>\
                                            </div>');
                option_of_size.hide();
                option_of_pattern.hide();
                break;
        }
    });
});
</script>


<div class="row b15tm">
    <div class="col s12">
        <table class="hoverable">
            <thead>
                <tr>
                    <th><?php echo __('Type of field') ?></th>
                    <th><?php echo __('Visible name of field') ?></th>
                    <th><?php echo __('Max length') ?></th>
                    <th><?php echo __('Params') ?></th>
                    <th><?php echo __('Required field') ?></th>
                    <th><?php echo __('Marker of field') ?></th>
                    <th><?php echo __('Activity') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($fields)): ?>
                <?php foreach($fields as $field): ?>
                    <?php
                        $params = (!empty($field['params'])) ? unserialize($field['params']) : array();
                        $values = (!empty($params['values'])) ? $params['values'] : '-';
                        $field_marker = 'add_field_' . $field['field_id'];

                        $required = (!empty($params['required'])) 
                        ? '<span style="color:red;">' . __('Yes') . '</span>' 
                        : '<span style="color:blue;">' . __('No') . '</span>';
                    ?>



                    <tr>
                        <td><?php echo h($field['type']); ?></td>
                        <td><?php echo h($field['label']); ?></td>
                        <td><?php echo (!empty($field['size'])) ? h($field['size']) : '-'; ?></td>
                        <td>
                        <?php if (!empty($values) && $values != "-"): ?>
                            <?php foreach($values as $value => $title): ?>
                                <?php echo $title ?> : <?php echo $value ?>
                                <br>
                            <?php endforeach; ?>
                        <?php elseif (!empty($params['pattern']) && $params['pattern'] != "-"): ?>
                                <?php echo $params['pattern'] ?>
                        <?php endif; ?>
                        </td>
                        <td><?php echo $required; ?></td>
                        <td><?php echo h(strtolower($field_marker)); ?></td>
                        <td>
                            <a class="btn-floating grey modal-trigger" title="<?php echo __('Edit') ?>" href="#edit_<?php echo $field['id'] ?>"><i class="small mdi-action-settings"></i></a>
                            <a class="btn-floating red modal-trigger" title="<?php echo __('Delete') ?>" onClick="return confirm('<?php echo __('Are you sure?') ?>');" href="additional_fields.php?m=<?php echo $_GET['m'] ?>&ac=del&id=<?php echo $field['id'] ?>"><i class="small mdi-action-delete"></i></a>
                        </td>
                    </tr>

                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="7"><?php echo __('Additional fields not found') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>



<?php
function DrsEdit() {
    global $DB;


    if (empty($_GET['id'])) redirect('/admin/additional_fields.php?m=' . $_GET['m']);
    $id = intval($_GET['id']);
    if ($id < 1) redirect('/admin/additional_fields.php?m=' . $_GET['m']);


    if (isset($_POST['send'])) {
        $error = array();
        $allow_types = array('text', 'checkbox', 'select');

        //type of field
        $type = (!empty($_POST['type']) && in_array(trim($_POST['type']), $allow_types)) ? trim($_POST['type']) : 'text';
        if (empty($_POST['label'])) 
            $error[] = __('Empty field "visible name"');
        if (empty($_POST['size']) && $type != 'checkbox' && $type != 'select') 
            $error[] = __('Empty field "max length"');
        if (!empty($_POST['size']) && !is_numeric($_POST['size'])) 
            $error[] = __('Wrong chars in "max length"');


        //params
        $params = array();
        if (!empty($_POST['required'])) $params['required'] = 1;
        if (!empty($_POST['pattern']) && $type == 'text') $params['pattern'] = $_POST['pattern'];
        switch ($type) {
            case 'select':
                if (isset($_POST['value0']) && isset($_POST['title0'])) {
                    $n = 0;
                    $options = array();
                    while (isset($_POST['value'.$n]) && isset($_POST['title'.$n])) {
                        $options[$_POST['value'.$n]] = $_POST['title'.$n];
                        $n++;
                    }

                    $params['values'] = $options;

                } else
                    $params['values'] = array("1" => __('Yes'), "0" => __('No'));

                break;
            default: unset($params['values']); break;
        }
        $params = serialize($params);


        //label
        $label = (!empty($_POST['label'])) ? trim($_POST['label']) : 'Add. field';

        //size
        $size = (!empty($_POST['size'])) ? intval($_POST['size']) : 70;

        if (!empty($error)) {
            $_SESSION['message'] = array_merge($_SESSION['message'],$error);
            redirect('/admin/additional_fields.php?m=' . $_GET['m']);
        }
        $data = array(
            'id' => $id,
            'type' => $type,
            'label' => $label,
            'size' => $size,
            'params' => $params,

        );
        $DB->save('add_fields', $data);

        //clean cache
        $Cache = new Cache;
        $Cache->clean(CACHE_MATCHING_ANY_TAG, array('module_' . $_GET['m']));
        redirect('/admin/additional_fields.php?m=' . $_GET['m']);
    }
}



function DrsAdd() {
    global $DB;


    if (isset($_POST['send'])) {
        $error = array();
        $allow_types = array('text', 'checkbox', 'select');


        
        //type of field
        $type = (!empty($_POST['type']) && in_array(trim($_POST['type']), $allow_types)) ? trim($_POST['type']) : 'text';
        if (empty($_POST['label'])) 
            $error[] = __('Empty field "visible name"');
        if (empty($_POST['size']) && $type != 'checkbox' && $type != 'select') 
            $error[] = __('Empty field "max length"');
        if (!empty($_POST['size']) && !is_numeric($_POST['size'])) 
            $error[] = __('Wrong chars in "max length"');


        //params
        $params = array();
        if (!empty($_POST['required'])) $params['required'] = 1;
        if (!empty($_POST['pattern']) && $type == 'text') $params['pattern'] = $_POST['pattern'];
        switch ($type) {
            case 'select':
                if (isset($_POST['value0']) && isset($_POST['title0'])) {
                    $n = 0;
                    $options = array();
                    while (isset($_POST['value'.$n]) && isset($_POST['title'.$n])) {
                        $options[$_POST['value'.$n]] = $_POST['title'.$n];
                        $n++;
                    }

                    $params['values'] = $options;

                } else
                    $params['values'] = array("1" => __('Yes'), "0" => __('No'));

                break;
            default: unset($params['values']); break;
        }
        $params = serialize($params);


        //label
        $label = (!empty($_POST['label'])) ? trim($_POST['label']) : 'Add. field';

        //size
        $size = (!empty($_POST['size'])) ? intval($_POST['size']) : 70;


        if (!empty($error)) {
            $_SESSION['message'] = array_merge($_SESSION['message'], $error);
            redirect('/admin/additional_fields.php?m=' . $_GET['m']);
        }

        $new_id = $DB->query("SELECT MIN(field_id)+1 FROM " . $DB->getFullTableName('add_fields') . " AS t1 WHERE `module` = '".$_GET['m']."' and (SELECT COUNT(*) FROM " . $DB->getFullTableName('add_fields') . " AS t2 WHERE  `module` = '".$_GET['m']."' and t2.field_id=t1.field_id+1)=0;");

        if (isset($new_id) and isset($new_id[0]) and isset($new_id[0]['MIN(field_id)+1']))
            $new_id = intval($new_id[0]['MIN(field_id)+1']);
        else
            $new_id = 1;

        $data = array(
            'field_id' => $new_id,
            'module' => $_GET['m'],
            'type' => $type,
            'label' => $label,
            'size' => $size,
            'params' => $params,
        );
        
        $DB->save('add_fields', $data);

        $DB->query("ALTER TABLE `" . $DB->getFullTableName($_GET['m']) . "` ADD `add_field_".$new_id."` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci;");

        //clean cache
        $Cache = new Cache;
        $Cache->clean(CACHE_MATCHING_ANY_TAG, array('module_' . $_GET['m']));
        redirect('/admin/additional_fields.php?m=' . $_GET['m']);
    }
}



function DrsDelete() {
    global $DB;

    if (empty($_GET['id'])) redirect('/admin/additional_fields.php?m=' . $_GET['m']);
    $id = intval($_GET['id']);
    if ($id < 1) redirect('/admin/additional_fields.php?m=' . $_GET['m']);

    $where = array('id' => $_GET['id']);
    $field = $DB->select('add_fields', DB_ALL, array('cond' => $where));

    if (!$field or !is_array($field[0]))
        redirect('/admin/additional_fields.php?m=' . $_GET['m']);

    $DB->query("ALTER TABLE `" . $DB->getFullTableName($_GET['m']) . "` DROP `add_field_".$field[0]['field_id']."`;");

    $DB->query("DELETE FROM `" . $DB->getFullTableName('add_fields') 
    . "` WHERE `id` = '" . $id . "' LIMIT 1");

    redirect('/admin/additional_fields.php?m=' . $_GET['m']);
}



include_once 'template/footer.php';
?>