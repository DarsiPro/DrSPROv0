<?php
//turn access

ACL::turnUser(array($this->module, 'add_comments'),true);
if (!empty($id) && !is_numeric($id))
    return $this->showMessage(__('Value must be numeric'));
$id = (int)$id;
if ($id < 1) return $this->showMessage(__('Unknown error'),getReferer(),'error', true);


$target_new = $this->Model->getById($id);
if (!$target_new) return $this->showMessage(__('Unknown error'),entryUrl($target_new, $this->module));
if (!$target_new->getCommented()) return $this->showMessage(__('Comments are denied here'),entryUrl($target_new, $this->module));

/* cut and trim values */
if ($_SESSION['user']['id'] != 0) {
    $name = $_SESSION['user']['name'];
} else {
    if (isset($_POST['login'])) {
        $name = mb_substr($_POST['login'], 0, 70);
        $name = trim($name);
    } else {
        $name = '';
    }
}

if (isset($_POST['reply']) and Config::read('comments_tree'))
    $reply = intval($_POST['reply']);
else
    $reply = 0;

$mail = '';
$message = trim($_POST['message']);
$ip      = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
$keystring = (isset($_POST['captcha_keystring'])) ? trim($_POST['captcha_keystring']) : '';


// Check fields
$error = '';

// Check additional fields if an exists.
// This must be doing after define $error variable.
$_addFields = $this->AddFields->checkFields('comments');
if (is_string($_addFields))
    $error .= $_addFields;



$max_lenght = Config::read($this->module, 'comment_lenght');
if ($max_lenght <= 0)
    $max_lenght = 500;
$min_lenght = 2;

if (empty($name))
    $error .= '<li>' . sprintf(__('Empty field "param"'), __('Login')) . '</li>' . "\n";
elseif (!Validate::cha_val($name, V_TITLE))
    $error .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Login')) . '</li>' . "\n";
if (empty($message))
    $error .= '<li>' . sprintf(__('Empty field "param"'), __('Text of comment')) . '</li>' . "\n";
elseif (mb_strlen($message) > $max_lenght)
    $error .= '<li>' . sprintf(__('Very big "param"'), __('Text of comment'), $max_length) . '</li>' . "\n";
elseif (mb_strlen($message) < $min_lenght)
    $error .= '<li>' . sprintf(__('Very small "param"'), __('Text of comment'), $min_length) . '</li>' . "\n";


// Check captcha if need exists
if (!ACL::turnUser(array('__other__', 'no_captcha'))) {
    if (empty($keystring))
        $error .= '<li>' . sprintf(__('Empty field "param"'), __('Captcha')) . '</li>' . "\n";


    // Проверяем поле "код"
    if (!empty($keystring)) {
        // Проверяем поле "код" на недопустимые символы
        if (!Validate::cha_val($keystring, V_CAPTCHA))
            $error = $error.'<li>' . sprintf(__('Wrong chars in field "param"'), __('Captcha')) . '</li>'."\n";
        if (!isset($_SESSION['captcha_keystring'])) {
            if (file_exists(ROOT . '/sys/logs/captcha_keystring_' . session_id() . '-' . date("Y-m-d") . '.dat')) {
                $_SESSION['captcha_keystring'] = file_get_contents(ROOT . '/sys/logs/captcha_keystring_' . session_id() . '-' . date("Y-m-d") . '.dat');
                @_unlink(ROOT . '/sys/logs/captcha_keystring_' . session_id() . '-' . date("Y-m-d") . '.dat');
            }
        }
        if (!isset($_SESSION['captcha_keystring']) || $_SESSION['captcha_keystring'] != $keystring)
            $error = $error.'<li>' . __('Wrong protection code') . '</li>'."\n";
    }
    unset($_SESSION['captcha_keystring']);
}


/* if an errors */
if (!empty($error)) {
    $_SESSION['addCommentForm'] = array();
    $_SESSION['addCommentForm']['errors'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' .
        "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
    $_SESSION['addCommentForm']['name'] = $name;
    $_SESSION['addCommentForm']['message'] = $message;
    return $this->showMessage($_SESSION['addCommentForm']['errors'], entryUrl($target_new, $this->module));
}


$id_user = $_SESSION['user']['id'];
/* SPAM DEFENCE */
if (isset($_SESSION['unix_last_post']) and (time()-$_SESSION['unix_last_post'] < 10)) {
    return $this->showMessage(__('You can not add messages so often'),entryUrl($target_new, $this->module));
} else {
    $_SESSION['unix_last_post'] = time();
}


/* remove cache */
if (!isset($this->Cache)) $this->Cache = new Cache;
$this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module, 'record_id_' . $id));


$commentsModel = OrmManager::getModelInstance('Comments');
if (!$commentsModel) return $this->showMessage(__('Some error occurred'),entryUrl($target_new, $this->module));

if (Config::read('comments_tree') and $reply != 0) {
    $parent_comment = $commentsModel->getFirst(array(
            'id' => $reply,
        ), array());
    if (!$parent_comment)
        return $this->showMessage(__('Parent comment not found'),entryUrl($target_new, $this->module));
}

$prev_comm = $commentsModel->getFirst(array(
        'entity_id' => $id,
    ), array(
        'order' => 'date DESC, id DESC',
));

$gluing = true;
if ($prev_comm) {
    if (strtotime($prev_comm->getEditdate()) > strtotime($prev_comm->getDate()))
        $lasttime = strtotime($prev_comm->getEditdate());
    else
        $lasttime = strtotime($prev_comm->getDate());
    $gluing = $lasttime > time() - Config::read('raw_time_mess');
    $prev_post_author = $prev_comm->getUser_id();
    if (empty($prev_post_author))
        $gluing = false;
    if ((mb_strlen($prev_comm->getMessage() . $message)) > $max_lenght)
        $gluing = false;
    if ($prev_post_author != $id_user || empty($id_user))
        $gluing = false;
    if (Config::read('comments_tree') and $prev_comm->getParent_id() != $reply)
        $gluing = false;
} else {
    $gluing = false;
}


if ($gluing === true) {
    $message = $prev_comm->getMessage() . "\n\n" . sprintf(__('Added in time'), DrsOffsetDate(strtotime($prev_comm->getDate()))) . "\n\n" . $message;

    $prev_comm->setMessage($message);
    $prev_comm->setEditdate(new Expr('NOW()'));
    $prev_comm->save();

    if ($this->isLogging) Logination::write('adding comment to ' . $this->module, $this->module . ' id(*gluing)');
    return $this->showMessage(__('Comment is added'),entryUrl($target_new, $this->module),'ok');
} else {
    /* save data */
    $data = array(
        'entity_id'   => $id,
        'parent_id'   => $reply,
        'name'     => $name,
        'message'  => $message,
        'ip'       => $ip,
        'user_id'  => $id_user,
        'date'     => new Expr('NOW()'),
        'mail'     => $mail,
        'module'   => $this->module,
        'editdate' => '0000-00-00 00:00:00',
        'premoder'     => 'confirmed',
    );

    // Save additional fields
    $data = $this->AddFields->set($_addFields, $data);

    if (ACL::turnUser(array($this->module, 'comments_require_premoder'))) {
        $data['premoder'] = 'nochecked';
    }

    $className = OrmManager::getEntityName('Comments');
    $entityComm = new $className($data);
    if ($entityComm) {
        $entityComm->save();

        $entity = $this->Model->getById($id);
        if ($entity) {
            $entity->setComments($entity->getComments() + 1);
            $entity->save();

            if ($this->isLogging) Logination::write('adding comment to ' . $this->module, $this->module . ' id(' . $id . ')');

            if (ACL::turnUser(array($this->module, 'comments_require_premoder')))
                $msg = $this->showMessage(__('Comment will be available after validation'), entryUrl($target_new, $this->module), 'grand');
            else
                $msg = $this->showMessage(__('Comment is added'), entryUrl($target_new, $this->module), 'ok');
            return $msg;
        }
    }
}
return $this->showMessage(__('Some error occurred'),entryUrl($target_new, $this->module));
?>