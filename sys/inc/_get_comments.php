<?php

function mkTree($comments, $parent = 0, $new = array(), $i = 0) {
    if ($comments) {
        foreach ($comments as $comment) {
            if ($comment->getParent_id() == $parent) {
                $comment->setLvl($i);
                $new[] = $comment;
                $new = mkTree($comments, $comment->getId(), $new, $i+1);
            }
        }
        return $new;
    }
}

$id = (int)$entity->getId();
if (empty($id) || $id < 1) $html = true;

$commentsModel = OrmManager::getModelInstance('Comments');

if (empty($html) && $commentsModel) {
    // $commentsModel->bindModel('Users');
    
    /* pages nav */
    $where = array('entity_id' => $id, 'module' => $this->module);
    if (!ACL::turnUser(array('__other__', 'can_see_hidden')))
        $where['premoder'] = 'confirmed';


    $order_way = (Config::read('comments_order', $this->module)) ? 'DESC' : 'ASC';
    $params = array(
        'order' => 'date ' . $order_way,
    );
    $commentsModel->bindModel('author');
    $comments = $commentsModel->getCollection($where, $params);
    $comments = mkTree($comments);

    if (count($comments) > 0) {
        $comments = $this->AddFields->mergeSelect($comments, 'comments');
    }

    if ($comments) {
        foreach ($comments as $comment) {
            if ($comment) {
                // COMMENT ADMIN BAR
                $ip = ($comment->getIp()) ? $comment->getIp() : 'Unknown';
                $moder_panel = '';

                if (strtotime($comment->getEditdate()) > strtotime($comment->getDate()))
                    $lasttime = strtotime($comment->getEditdate());
                else
                    $lasttime = strtotime($comment->getDate());
                $raw_time_mess = $lasttime - time() + Config::read('raw_time_mess');
                if ($raw_time_mess <= 0) $raw_time_mess = false;

                if (ACL::turnUser(array($this->module, 'edit_comments'))
                    || (!empty($_SESSION['user']['id']) && $comment->getUser_id() == $_SESSION['user']['id']
                    && ACL::turnUser(array($this->module, 'edit_my_comments'))
                    && (Config::read('raw_time_mess') == 0 or $raw_time_mess))) {

                    $moder_panel .= get_link('', $this->getModuleURL('edit_comment_form/' . $comment->getId()), array('class' => 'drs-edit', 'title' => __('Edit')));
                }

                if (ACL::turnUser(array($this->module, 'delete_comments'))
                    || (!empty($_SESSION['user']['id']) && $comment->getUser_id() == $_SESSION['user']['id']
                    && ACL::turnUser(array($this->module, 'delete_my_comments'))
                    && (Config::read('raw_time_mess') == 0 or $raw_time_mess))) {

                    $moder_panel .= get_link('', $this->getModuleURL('delete_comment/' . $comment->getId()), array('class' => 'drs-delete', 'title' => __('Delete'), 'onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu(this)}; return false"));
                }

                $comment->setRaw_time_mess($raw_time_mess);

                if (!empty($moder_panel)) {
                    $moder_panel .= '<a target="_blank" href="https://apps.db.ripe.net/search/query.html?searchtext=' . h($ip) . '" class="drs-ip" title="IP: ' . h($ip) . '"></a>';
                }

                $comment->setAvatar('<img class="ava" src="' . getAvatar($comment->getUser_id()) . '" alt="User avatar" title="' . h($comment->getName()) . '" />');


                if ($comment->getUser_id()) {
                    $comment->setName_a(get_link(h($comment->getName()), getProfileUrl((int)$comment->getUser_id(), true)));
                    $comment->setUser_url(getProfileUrl((int)$comment->getUser_id()));
                } else {
                    $comment->setName_a(h($comment->getName()));
                }
                $comment->setName(h($comment->getName()));


                $comment->setModer_panel($moder_panel); // Is deprecated. It will be removed in DarsiPro 7
                $comment->setMessage(PrintText::print_page($comment->getMessage(), false, false, false, true));

                if ($comment->getEditdate()!='0000-00-00 00:00:00') {
                    $comment->setEditdate($comment->getEditdate());
                } else {
                    $comment->setEditdate('');
                }
            }
        }
    }
    $html = $this->render('viewcomment.html', array('entities' => $comments,'commentsr' => &$comments));


} else {
    $html = '';
}
