<?php
/**
* @project    DarsiPro CMS
* @package    Comments list
* @url        https://darsi.pro
*/

include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';

$Register = Register::getInstance();


$allowed_actions = array('edit', 'delete', 'index', 'premoder');
if (empty($_GET['m']) || !Config::read($_GET['m'].".std_admin_pages.comments_list")) {
    
    redirect('/admin/');
}
$module = $_GET['m'];
$Register['module'] = $module;

$action = (!empty($_GET['ac'])) ? $_GET['ac'] : 'index';
if (empty($action) && !in_array($action, $allowed_actions)) $action = 'index';


$Controll = new MaterialsList;
list($output, $pages) = $Controll->{$action}($module);






class MaterialsList {
    public $pageTitle;
    
    public function __construct()
    {
        $this->pageTitle = __('Comments list');
    }
    

    public function index($module) 
    {
        $output = '';
        $model = OrmManager::getModelInstance('Comments');
        
        $where = (!empty($_GET['premoder'])) ? array('premoder' => 'nochecked') : array();
        $where[] = "`module` = '" . $module . "'";
        
        $perpage = 20;
        
        $total = $model->getTotal(array('cond' => $where));
        list ($pages, $page) = pagination($total, $perpage, '/admin/comments_list.php?m=' . $module);
        
        
        $model->bindModel('author');
        $model->bindModel('parent_entity');
        $materials = $model->getCollection($where, array(
            'page' => $page,
            'limit' => $perpage,
            'order' => 'date DESC',
        ));
        
        $output .= '
        <table class="hoverable">
            <thead>
                <th>'. __('Material') .'</th>
                <th>'. __('Text of comment') .'</th>
                <th>'. __('Date') .'</th>
                <th>'. __('Author') .'</th>
                <th width="20%">'. __('Activity') .'</th>
            </thead>
            <tbody>';
            
            
        
        if (empty($materials)) {
            $output = '<tr><td colspan="5"><b>' . __('Materials not found') . '</b></td></tr>';
        } else {

            foreach ($materials as $mat) {
                
                
                
                $output .= '
                <tr>';
                    $output .= '
                    <td>';
                        $output .= '<a href="' . get_url('/admin/materials_list.php?m=' . $module . '&ac=edit&id=' . $mat->getParent_entity()->getId()) . '">' 
                        . h($mat->getParent_entity()->getTitle()) . '</a>';
                    $output .= '
                    </td>
                    <td>';
                        $output .= PrintText::getAnnounce($mat->getMessage(), null, 300, $mat);
                    $output .= '
                    </td>
                    <td>';
                        $output .= $mat->getDate();
                    $output .= '
                    </td>
                    <td>';
                    if (is_object($mat->getAuthor())) {
                        $output .= h($mat->getAuthor()->getName()) . '('.$mat->getAuthor()->getId().')';
                    } else {
                        $output .= __('Guest');
                    }
                    $output .= '
                    </td>
                    <td><div>';
                    if (!empty($_GET['premoder'])) {
                        $output .= '
                        <a class="btn green" href="' . get_url('/admin/comments_list.php?m=' . $module . '&ac=premoder&status=confirmed&id=' . $mat->getId()) . '" class="on"><i class="mdi-action-done small"></i></a>
                        <a class="btn red" href="' . get_url('/admin/comments_list.php?m=' . $module . '&ac=premoder&status=rejected&id=' . $mat->getId()) . '" class="off"><i class="mdi-navigation-close small"></i></a>';
                    } else {
                        $output .= '
                        <a class="btn green" href="' . get_url('/admin/comments_list.php?m=' . $module . '&ac=edit&id=' . $mat->getId()) . '"><i class="mdi-action-settings small"></i></a>
                        <a class="btn red" href="' . get_url('/admin/comments_list.php?m=' . $module . '&ac=delete&id=' . $mat->getId()) . '" onClick="return _confirm();"><i class="mdi-action-delete small"></i></a>';
                    }
                    $output .= '
                        </div>
                    </td>';
                    
                $output .= '
                </tr>';
            }

        }
        
        $output .= '
            </tbody>
        </table>';
        
        return array($output, $pages);
    }
    
    
    function premoder($module){
        $Model = OrmManager::getModelInstance('Comments');
        $entity = $Model->getById(intval($_GET['id']));
        if (!empty($entity)) {
            
            $status = $_GET['status'];
            if (!in_array($status, array('rejected', 'confirmed'))) $status = 'nochecked';
            
            $entity->setPremoder($status);
            $entity->save();
            $_SESSION['message'][] = __('Saved');
            
            
            //clean cache
            $Cache = new Cache;
            $Cache->clean(
                CACHE_MATCHING_TAG, 
                array(
                    'module_' . $module,
                    'record_id_' . $entity->getUser_id()
                )
            );
        } else {
            $_SESSION['message'][] = __('Some error occurred');
        }
        redirect(getReferer(), true);
    }
    
    
    public function delete($module) {
        
        $model = OrmManager::getModelInstance('Comments');
        $id = intval($_GET['id']);
        $entity = $model->getById($id);
        
        if (!empty($entity)) {
            $entity->delete();
            $_SESSION['message'][] = __('Material has been delete');
        }
        
        redirect(getReferer(), true);
    }
    
    
    public function edit($module) {
        $this->pageTitle .= ' - ' . __('Comment editing');
    
        $output = '';
        $model = OrmManager::getModelInstance('Comments');

        
        $id = intval($_GET['id']);
        $entity = $model->getById($id);
        
        
        
        if (!empty($_POST)) {
            $entity->setMessage($_POST['message']);
            
            
            $entity->save();
            $_SESSION['message'][] = __('Operation is successful');
            
            redirect('/admin/comments_list.php?m=' . $module);
        }
        
        $output .= '
        <div class="col s12">
            <div class="input-field">
                <textarea id="message" name="message" class="materialize-textarea">'.h($entity->getMessage()).'</textarea>
                <label for="message">
                    ' . __('Text of comment') . '
                </label>
            </div>
            <div class="input-field">
                <input class="btn" type="submit" name="send" value="' . __('Save') . '" />
            </div>
        </div>';
        
        
        return array($output, '');
    }
}


$pageTitle = $Controll->pageTitle;
$pageNav = $Controll->pageTitle;
$pageNavr = '<ul class="pagination">'.$pages.'</ul>';
include_once ROOT . '/admin/template/header.php';
?>


<form method="POST" action="" enctype="multipart/form-data">
<div class="row">
    <div class="col s12">
        <?php echo $output; ?>
    </div>
</div>
</form>


<?php include_once 'template/footer.php'; ?>
