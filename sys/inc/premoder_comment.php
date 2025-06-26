<?php
//turn access
\ACL::turnUser(array($this->module, 'view_list'),true);
\ACL::turnUser(array('__other__', 'can_premoder'),true);
if (!empty($id) && !is_numeric($id))
    return $this->showMessage(__('Value must be numeric'));
$id = (!empty($id)) ? (int)$id : 0;
if ($id < 1) return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);

if (!in_array((string)$type, $this->premoder_types))
    return $this->showMessage(__('Some error occurred'));

$commentsModel = OrmManager::getModelInstance('Comments');
if ($commentsModel) {
    $comment = $commentsModel->getById($id);
    if ($comment) {
        $entityID = $comment->getEntity_id();
        $comment->setPremoder((string)$type);
        $comment->save();

        $entity = $this->Model->getById($entityID);
        if ($entity) {
            return $this->showMessage(__('Operation is successful'), entryUrl($entity, $this->module),'ok');
        }
    }
}
return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);
?>