<?php
if (!empty($id) && !is_numeric($id))
    return $this->showMessage(__('Value must be numeric'));
// проверка прав

if (!ACL::turnUser(array($this->module, 'edit_comments'))
    && (!ACL::turnUser(array($this->module, 'edit_my_comments')))) {

    return $this->showMessage(__('Permission denied'),getReferer(),'error', true);
}

$id = (!empty($id)) ? (int)$id : 0;
if ($id < 1) return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);


$commentsModel = OrmManager::getModelInstance('Comments');
if (!$commentsModel) return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);
$comment = $commentsModel->getById($id);
if (!$comment) return $this->showMessage(__('Comment not found'),getReferer(),'error', true);

$material = $this->Model->getById($comment->getEntity_id());

if (strtotime($comment->getEditdate()) > strtotime($comment->getDate()))
    $lasttime = strtotime($comment->getEditdate());
 else
    $lasttime = strtotime($comment->getDate());
$raw_time_mess = $lasttime - time() + Config::read('raw_time_mess');
if ($raw_time_mess <= 0) $raw_time_mess = false;

// дополнительная проверка прав
if (!ACL::turnUser(array($this->module, 'edit_comments'))
    && (!empty($_SESSION['user']['id']) && $post->getUser_id() == $_SESSION['user']['id']
    && ACL::turnUser(array($this->module, 'edit_my_comments'))
    && (Config::read('raw_time_mess') == 0 or $raw_time_mess)) === false) {

    return $this->showMessage(__('Permission denied'),getReferer(),'error', true);
}


/* cut and trim values */
if ($comment->getUser_id() > 0) {
    $name = $comment->getName();
} else {
    $name = mb_substr($_POST['login'], 0, 70);
    $name = trim($name);
}


$mail = '';
$message = (!empty($_POST['message'])) ? $_POST['message'] : '';
$message = trim($message);


$error = '';

// Check additional fields if an exists.
// This must be doing after define $error variable.
$_addFields = $this->AddFields->checkFields('comments');
if (is_string($_addFields))
    $error .= $_addFields;



$max_lenght = Config::read('comment_lenght', $this->module);
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

/* if an error */
if (!empty($error)) {
    $_SESSION['editCommentForm'] = array();
    $_SESSION['editCommentForm']['errors'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'
        . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
    $_SESSION['editCommentForm']['message'] = $message;
    $_SESSION['editCommentForm']['name'] = $name;
    return $this->showMessage($_SESSION['editCommentForm']['errors'], getReferer(),'error', true);
}


//remove cache
if (!isset($this->Cache)) $this->Cache = new Cache;
$this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module, 'record_id_' . $comment->getEntity_id()));


// Update comment
$data = array(
    'message'  => $message,
    'editdate' => new Expr('NOW()')
);
if ($name) $data['name'] = $name;

// Save additional fields
$data = $this->AddFields->set($_addFields, $data);

$comment->set($data);
$comment->save();

if ($this->isLogging) Logination::write('editing comment for ' . $this->module, $this->module . ' id(' . $comment->getEntity_id() . '), comment id(' . $id . ')');
return $this->showMessage(__('Operation is successful'), entryUrl($material, $this->module),'ok');
?>