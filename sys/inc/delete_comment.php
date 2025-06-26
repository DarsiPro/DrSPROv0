<?php
//turn access

ACL::turnUser(array($this->module, 'delete_comments'),true);
if (!empty($id) && !is_numeric($id))
    return $this->showMessage(__('Value must be numeric'));
$id = (!empty($id)) ? (int)$id : 0;
if ($id < 1) return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);

$commentsModel = OrmManager::getModelInstance('Comments');
if ($commentsModel) {
    $comment = $commentsModel->getById($id);
    if ($comment) {
        $entityID = $comment->getEntity_id();
        $comment->delete();

        $entity = $this->Model->getById($entityID);
        if ($entity) {
            $entity->setComments($entity->getComments() - 1);
            $entity->save();

            if ($this->isLogging) Logination::write('delete comment for ' . $this->module, $this->module . ' id(' . $entityID . ')');
            return $this->showMessage(__('Comment is deleted'), entryUrl($entity, $this->module),'ok');
        }
    }
}
return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);
?>