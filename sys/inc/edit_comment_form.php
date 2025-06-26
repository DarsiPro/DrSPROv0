<?php
if (!empty($id) && !is_numeric($id))
    return $this->showMessage(__('Value must be numeric'));
// проверка прав

if (!ACL::turnUser(array($this->module, 'edit_comments'))
    && (!ACL::turnUser(array($this->module, 'edit_my_comments')))) {

    return $this->showMessage(__('Permission denied'),getReferer(),'error', true);
}

$id = (!empty($id)) ? (int)$id : 0;
if ($id < 1) return $this->showMessage(__('Unknown error'),getReferer(),'error', true);


$commentsModel = OrmManager::getModelInstance('Comments');
if (!$commentsModel) return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);
$comment = $commentsModel->getById($id);
if (!$comment) return $this->showMessage(__('Comment not found'),getReferer(),'error', true);

if (count($comment) > 0) {
    $comment = $this->AddFields->mergeSelect(array($comment), 'comments')[0];
}

if (strtotime($comment->getEditdate()) > strtotime($comment->getDate()))
    $lasttime = strtotime($comment->getEditdate());
 else
    $lasttime = strtotime($comment->getDate());
$raw_time_mess = $lasttime - time() + Config::read('raw_time_mess');
if ($raw_time_mess <= 0) $raw_time_mess = false;

// дополнительная проверка прав
if (!ACL::turnUser(array($this->module, 'edit_comments'))
    && (ACL::turnUser(array($this->module, 'edit_my_comments'))
    && (Config::read('raw_time_mess') == 0 or $raw_time_mess)) === false) {

    return $this->showMessage(__('Permission denied'),getReferer(),'error', true);
}


// Categories tree
$entity = $this->Model->getById($comment->getEntity_id());
if ($entity && $entity->getCategory_id()) {
    $this->categories = $this->_getCatsTree($entity->getCategory_id());
} else {
    $this->categories = $this->_getCatsTree();
}


$comment->setDisabled(($comment->getUser_id()) ? ' disabled="disabled"' : '');


// Если при заполнении формы были допущены ошибки
if (isset($_SESSION['editCommentForm'])) {
    $errors   = $_SESSION['editCommentForm']['errors'];
    $message  = $_SESSION['editCommentForm']['message'];
    $name     = $_SESSION['editCommentForm']['name'];
    unset($_SESSION['editCommentForm']);
} else {
    $errors = '';
    $message = $comment->getMessage();
    $name    = $comment->getName();
}


$comment->setAction(get_url($this->getModuleURL('/update_comment/' . $id)));
$comment->setErrors($errors);
$comment->setName(h($name));
$comment->setMessage(h($message));

// nav block
$navi = array();
$navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
                    . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator')
                    . __('Comment editing');
$this->_globalize($navi);

$source = $this->render('editcommentform.html', array(
    'context' => $comment,
    'form' => &$comment,
));
$this->comments = '';

return $this->_view($source);
