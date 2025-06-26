<?php

function cmpTitle($a, $b) {
    $a = isset($a['title']) ? $a['title'] : '';
    $b = isset($b['title']) ? $b['title'] : '';
    if ($a === $b) return 0;
    return ($a < $b) ? -1 : 1;
}

$markers = array(
    'editor_head' => 'HTML-код, добавляемый в &lt;HEAD&gt;',
    'editor_body' => 'HTML-код, добавляемый в &lt;BODY&gt;',
    'editor_buttons' => 'HTML-код панели кнопок',
    'editor_text' => 'HTML-код поля ввода',
    'editor_forum_text' => 'HTML-код поля ввода для форума',
    'editor_forum_quote' => 'HTML-код кнопки "цитировать" для форума',
    'editor_forum_name' => 'HTML-код кнопки добавления имени пользователя',
);

$editor_set = array();

include 'config.php';

if (isset($_POST['ac'])) {
    $editor = array('title' => isset($_POST['title']) ? trim($_POST['title']) : '');
    foreach ($markers as $marker => $value) {
        $editor[$marker] = isset($_POST[$marker]) ? trim($_POST[$marker]) : '';
    }
    
    switch (strtolower(trim($_POST['ac']))) {
        case 'set':
            $number = isset($_POST['number']) ? intval(trim($_POST['number'])) : null;
            if (isset($number) && $editor_set && is_array($editor_set)) {
                foreach ($editor_set as $index => $editor) {
                    $editor['default'] = ($index == $number);
                    $editor_set[$index] = $editor;
                }
            }
            break;
        case 'new':
            $editor_set[] = $editor;
            break;
        case 'edit':
            $number = isset($_POST['number']) ? intval(trim($_POST['number'])) : null;
            if (isset($number)) {
                if (isset($editor_set[$number])) $editor['default'] = $editor_set[$number]['default'];
                $editor_set[$number] = $editor;
            } else {
                $editor_set[] = $editor;
            }
            break;
        case 'del':
            $number = isset($_POST['number']) ? intval(trim($_POST['number'])) : null;
            if (isset($number)) {
                unset($editor_set[$number]);
            }
            break;
        default:
    }
    usort($editor_set, "cmpTitle");
    $fopen = @fopen(dirname(__FILE__) . '/config.php', 'w');
    if ($fopen) {
        fputs($fopen, '<?php ' . "\n" . '$editor_set = ' . var_export($editor_set, true) . ";\n" . '?>');
        fclose($fopen);
    }
}

$popups_content = '<div class="modal modal-fixed-footer" id="addEditor">
        <div class="modal-content">
            <form name="addForm" method="POST" action="">
                <div class="row">
                    <div class="items">
                        <div class="input-field col s12">
                            <input type="text" id="title" name="title" value="" />
                            <label for="title">' . __('Title') . '</label>
                            <input type="hidden" name="ac" value="new" />
                        </div>';

foreach ($markers as $marker => $value) {
    $popups_content .= '<div class="item">
        Маркер {{ ' . $marker . ' }} - 
        ' . $value . ':<br />';
    $popups_content .= ($marker == 'editor_text' || $marker == 'editor_forum_text' || $marker == 'editor_forum_quote' || $marker == 'editor_forum_name') ? 
        '<input type="text" name="' . $marker . '" style="width:95%" />' :
        '<textarea name="' . $marker . '" cols="30" rows="3" style="width:95%" /></textarea>';
    $popups_content .= '</div>';
}
    
$popups_content .= '
                </div>
            </div><br><br>
        </div>
        <div class="modal-footer">
                <a href="#!" class="modal-action waves-effect waves-green btn" onclick="document.addForm.submit();">Сохранить</a><a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat" onclick="document.addForm.reset();">Отмена</a>
        </div>
    </form>
</div>';


$output = '
    <div class="list">
        <div class="modal-trigger waves-effect waves-light btn" href="#addEditor"><div class="add"></div>Добавить редактор</div>';

if ($editor_set && is_array($editor_set) && count($editor_set)) {
    $output .= '
        <form name="deleteEditor" action="" method="POST">
            <input type="hidden" name="ac" value="del" />
            <input type="hidden" name="number" value="" />
        </form>
        <form name="setEditor" action="" method="POST">
            <input type="hidden" name="ac" value="set" />
            <input type="hidden" name="number" value="" />
        </form>
        <ul class="collection">';
    foreach ($editor_set as $index => $editor) {
        $output .= '<li href="#!" class="collection-item">
                        <span style="font-weight: bold">' . (isset($editor['title']) && strlen($editor['title']) > 0 ? h($editor['title']) : 'Редактор ' . $index) . '</span>
                        <span class="secondary-content">
                            <a class="modal-trigger" href="#editEditor' . $index . '"><i class="mdi-action-settings small"></i></a>
                            <a onclick="document.forms[\'setEditor\'].number.value=' . $index . ';document.forms[\'setEditor\'].submit();" href="javascript://"><i class="' . ((isset($editor['default']) && $editor['default']) ? 'mdi-action-favorite' : 'mdi-action-favorite-outline') . ' small"></i></a>
                            <a onclick="if (_confirm()) {document.forms[\'deleteEditor\'].number.value=' . $index . ';document.forms[\'deleteEditor\'].submit();};" href="javascript://"><i class="mdi-action-delete small"></i></a>
                        </span>
                    </a>';
     
                    
        $popups_content .= '<div class="modal modal-fixed-footer" id="editEditor' . $index . '">
        <div class="modal-content">
            <form id="editForm' . $index . '" name="editForm' . $index . '" method="POST" action="">
                <div class="row">
                    <div class="items">
                        <div class="input-field col s12">
                            <input type="text" id="title'.$index.'" name="title" value="' . (isset($editor['title']) ? htmlspecialchars($editor['title']) : '') . '" />
                            <label for="title'.$index.'">' . __('Title') . '</label>
                            <input type="hidden" name="ac" value="edit" />
                            <input type="hidden" name="number" value="' . $index . '" />
                        </div>';

        foreach ($markers as $marker => $value) {
            $popups_content .= '<div class="input-field col s12">';
            $popups_content .= ($marker == 'editor_text' || $marker == 'editor_forum_text' || $marker == 'editor_forum_quote' || $marker == 'editor_forum_name') ? 
                '<input type="text" id="' . $marker.$index . '" name="' . $marker . '" value="' . (isset($editor[$marker]) ? htmlspecialchars($editor[$marker]) : '') . '" />' :
                '<textarea id="' . $marker.$index . '" name="' . $marker . '" class="materialize-textarea" />' . (isset($editor[$marker]) ? htmlspecialchars($editor[$marker]) : '') . '</textarea>';
            $popups_content .= '<label for="'.$marker.$index.'">Маркер {{ ' . $marker . ' }} - 
                ' . $value . ':</label></div>';
        }
    
        $popups_content .= '
                        </div>
                    </div><br><br>
                </div>
                <div class="modal-footer">
                     <a href="#!" class="modal-action waves-effect waves-green btn" onclick="document.editForm' . $index . '.submit();">Сохранить</a><a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat" onclick="document.editForm' . $index . '.reset();">Отмена</a>
                </div>
            </form>
        </div>';
    }
}
$output .= '</ul>';
$output = $popups_content . $output;

?>