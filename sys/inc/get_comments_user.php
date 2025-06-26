<?php
if (!is_numeric($id))
    return $this->showMessage(__('Value must be numeric'));

$id = (int)$id;
/* COMMENT BLOCK */
$total = $this->Model->getCountComments($id);
$per_page = 25;

/* pages nav */
list($pages, $page) = pagination($total, $per_page, $this->getModuleURL('comments/' . ($id ? $id : '')));
$this->_globalize(array('comments_pagination' => $pages));

$cond = array();
if ($id) {
    $cond['user_id'] = $id;
}
$params = array(
    'page'  => $page,
    'limit' => $per_page,
    'order' => 'date DESC',
);


$title = __('All comments');
if ($id && intval($id) > 0) {
    $user = $this->Model->getById(intval($id));
    if ($user)
        $title = __('User comments') . ' "' . h($user->getName()) . '"';
}
$this->page_title = $title . ' - ' . $this->page_title;

$commentsModel = OrmManager::getModelInstance('Comments');
$comments = $commentsModel->getCollection($cond, $params);
if ($comments && is_array($comments)) {
    foreach ($comments as $comment) {
        if ($comment) {
            $module = $comment->getModule();

            // COMMENT ADMIN BAR
            $ip = ($comment->getIp()) ? $comment->getIp() : 'Unknown';
            $moder_panel = '';
            if (ACL::turnUser(array($module, 'edit_comments'))) {
                $moder_panel .= get_link('', '/' . $module . '/edit_comment_form/' . $comment->getId(), array('class' => 'drs-edit', 'title' => __('Edit')));
            }

            if (ACL::turnUser(array($module, 'delete_comments'))) {
                $moder_panel .= get_link('', '/' . $module . '/delete_comment/' . $comment->getId(), array('class' => 'drs-delete', 'title' => __('Delete'), 'onClick' => "return confirm('" . __('Are you sure?') . "')"));
            }

            if (!empty($moder_panel)) {
                $moder_panel .= '<a target="_blank" href="https://apps.db.ripe.net/search/query.html?searchtext=' . h($ip) . '" class="drs-ip" title="IP: ' . h($ip) . '"></a>';
            }

            $comment->setAvatar('<img class="ava" src="' . getAvatar($comment->getUser_id()) . '" alt="'.__('User avatar').'" title="' . h($comment->getName()) . '" />');


            if ($comment->getUser_id()) {
                $comment->setName_a(get_link(h($comment->getName()), getProfileUrl((int) $comment->getUser_id(), true)));
                $comment->setUser_url(getProfileUrl((int) $comment->getUser_id()));
            } else {
                $comment->setName_a(h($comment->getName()));
            }
            $comment->setName(h($comment->getName()));


            $comment->setModer_panel($moder_panel);
            $comment->setMessage(PrintText::print_page($comment->getMessage(), ($user ? $user->getStatus() : false)));

            if ($comment->getEditdate() != '0000-00-00 00:00:00') {
                $comment->setEditdate(sprintf(__('Comment editing in time') , DrsDate($comment->getEditdate())));
            } else {
                $comment->setEditdate('');
            }

            $comment->setEntry_url(get_url('/' . $module . '/view/' . $comment->getEntity_id()));
        }
    }
}
$this->comments = $this->render('viewcomment.html', array('entities' => $comments, 'commentsr' => &$comments));

$navi = array();
$navi['add_link'] = (ACL::turnUser(array($this->module, 'add_materials'))) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
$navi['module_url'] = get_url($this->getModuleURL());
$navi['category_url'] = get_url($this->getModuleURL('comments/' . ($id ? $id : '')));
$navi['category_name'] = $title;
$navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
                    . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . $title;
$this->_globalize($navi);

return $this->_view('');