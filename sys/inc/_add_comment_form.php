<?php
//turn access

ACL::turnUser(array($this->module, 'add_comments'),true);

$id = (int)$id;
if ($id < 1) {
    $html = '';
} else {
    $markers = array();

    // Additional fields
    $_addFields = $this->AddFields->getInputs(array(), 'comments');
    foreach ($_addFields as $k => $field) {
        $markers[strtolower($k)] = $field;
    }

    $name = (!empty($_SESSION['user']['name'])) ? h($_SESSION['user']['name']) : '';
    $message = '';
    $info = '';

    $markers['action'] = get_url($this->getModuleURL('/add_comment/' . $id));

    //$kcaptcha = get_img('/sys/inc/kcaptcha/kc.php?'.session_name().'='.session_id());
    $kcaptcha = '';
    if (!ACL::turnUser(array('__other__', 'no_captcha'))) {
        $kcaptcha = getCaptcha();
    }

    $markers['disabled'] = (!empty($_SESSION['user']['name'])) ? ' disabled="disabled"' : '';
    $markers['add_comment_captcha'] = $kcaptcha;
    $markers['add_comment_name'] = $name;
    $markers['add_comment_message'] = $message;
    $html = $this->render('addcommentform.html', array('context' => $markers,'data' => &$markers));
    $html = $info . $html . "\n";
}
