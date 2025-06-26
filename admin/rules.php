<?php

/**
* @project    DarsiPro CMS
* @package    Rules groups editor
* @url        https://darsi.pro
*/



include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';


$pageTitle = __('Rules editor');


clearstatcache();
$acl_groups = ACL::get_group_info();
$acl_rules = ACL::getRules();


/* SAVE GROUPS RULES */
if (isset($_POST['send'])) {
    if (!empty($acl_rules)) {
        $new_acl_rules = $acl_rules;
        foreach ($acl_rules as $mod => $rules) {
            foreach ($rules as $rule => $roles) {
                // На всякий случай удаляем повторяющиеся значения
                if (isset($new_acl_rules[$mod][$rule]['groups']))
                    $new_acl_rules[$mod][$rule]['groups'] = array_unique($new_acl_rules[$mod][$rule]['groups']);
                
                foreach ($acl_groups as $id => $params) {
                    // Меняем права группы
                    if (!empty($_POST[$mod][$rule . '_' . $id])) {
                    // Выдаем право
                        // Если еще нет разрешений, создаем список разрешенных групп
                        if (!isset($new_acl_rules[$mod][$rule]['groups']))
                            $new_acl_rules[$mod][$rule]['groups'] = array();
                        // Если в существующих разрешениях еще нет разрешения для этой группы, добавляем его
                        if (!in_array($id, $new_acl_rules[$mod][$rule]['groups'])) {
                            $new_acl_rules[$mod][$rule]['groups'][] = $id;
                        }
                    // Забираем право
                    } else {
                        // Если список разрешенных групп для права существует и группа для запрета в него входит, то удаляем её из этого списка
                        if (isset($new_acl_rules[$mod][$rule]['groups']) && ($offkey = array_search($id, $new_acl_rules[$mod][$rule]['groups'])) !== false) {
                            unset($new_acl_rules[$mod][$rule]['groups'][$offkey]);
                        }
                    }
                }
            }
            
        }
        
        // Сохраняем права модулей(функция сама определит в какой файл что нужно сохранить)
        $errors = ACL::save_rules_full($new_acl_rules);
        if (!empty($errors))
            $_SESSION['message'] = array_merge($_SESSION['message'], $errors);
        else
            $_SESSION['message'][] = __('Saved');
        
        redirect('/admin/rules.php');
    }
}

include_once R.'admin/template/header.php';
?>


<div class="row">
    <div class="row b15tm">
        <h5><?php echo $pageTitle ?></h5>
    </div>
    <form action="rules.php" method="POST">
        <ul class="collapsible z-depth-0" data-collapsible="expandable">
        <?php foreach ($acl_groups as $id => $gr): ?><!-- Список групп -->
            <li>
                <div class="collapsible-header">
                    <i class="mdi-action-account-child"></i>
                    <?php echo h($gr['title']); ?>
                    <i class="mdi-navigation-arrow-drop-down right"></i>
                </div>
                <div class="collapsible-body">
                    <ul class="collapsible z-depth-0  no-margin" data-collapsible="expandable" style="border-left: 6px solid #008DC0;">
                    <?php foreach ($acl_rules as $mod => $_rules): ?><!-- Список модулей -->
                        <?php if (count($_rules) > 0): ?>
                        <li>
                            <div class="collapsible-header">
                                <?php echo __($mod,false,$mod); ?><!-- Название модуля -->
                            </div>
                            <div class="collapsible-body">
                                <div class="collection no-margin" style="border-left: 10px solid #ddd;">
                                  <?php foreach ($_rules as $title => $rules): ?><!-- Список прав -->
                                      <?php if (isset($rules['groups'])): ?>
                                         <div class="collection-item">
                                            <?php  $ch_id = $mod . '_' . $id . '_' . $title; ?>
                                            <input
                                               name="<?php echo $mod.'['.$title.'_'.$id.']' ?>"
                                               type="checkbox"
                                               value="1"
                                               <?php if (ACL::turn(array($mod, $title, 'groups'),false, $id)) echo 'checked="checked"' ?>
                                               id="<?php  echo $ch_id; ?>"
                                            />
                                            <label for="<?php  echo $ch_id; ?>"><?php echo __($title,false,$mod); ?></label><!-- Название права -->
                                         </div>
                                      <?php endif; ?>
                                  <?php endforeach; ?><!-- //Список прав -->
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                    <?php endforeach; ?><!-- //Список модулей -->
                    </ul>
                </div>
            </li>
        <?php endforeach; ?><!-- //Список групп -->
        </ul>
        <div class="col s12 b10tm">
            <input class="btn" name="send" type="submit" value="<?php echo __('Save') ?>" />
        </div>
    </form>
</div>


<?php include_once R . 'admin/template/footer.php'; ?>