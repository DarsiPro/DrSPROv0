<?php
/**
* @project    DarsiPro CMS
* @package    Materials list
* @url        https://darsi.pro
*/


include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';
$pageTitle = __('Materials list');
$Register = Register::getInstance();


$allowed_actions = array('index','premoder','delete');
if (empty($_GET['m']) || !Config::read($_GET['m'].".std_admin_pages.materials_list")) {
    $_SESSION["message"][] = __('Some error occurred');
    redirect('/admin/');
}
$module = $_GET['m'];
$Register['module'] = $module;

$action = (!empty($_GET['ac'])) ? $_GET['ac'] : 'index';


if (/*empty($action) && */!in_array($action, $allowed_actions)) $action = 'index';





//print_r($action);







$Controll = new MaterialsList;
list($output, $pages) = $Controll->{$action}($module);






class MaterialsList {
    public $pageTitle;
    
    public function __construct() {
        $this->pageTitle = __('Materials list');
    }

    public function index($module) {
        $output = '';
        $model = OrmManager::getModelInstance($module);
        
        $where = (!empty($_GET['premoder'])) ? array('premoder' => 'nochecked') : array();

        $total = $model->getTotal(array('cond' => $where));

        // количество материалов на странице
        $perPage = 20;

        list($pages, $page) = pagination($total, $perPage, WWW_ROOT.'/admin/materials_list.php?m=' . $module);

        $model->bindModel('categories');
        $model->bindModel('author');
        $materials = $model->getCollection($where, array(
            'page' => $page,
            'limit' => $perPage,
        ));

        
        
        
        
        
        
        $output .= '<span class="button-group"> 
        <a href="' . get_url('/admin/materials_list.php?m=' . $module) . '" class="button green-active active">'. __('Materials list') .'</a> 
        <a href="' . get_url('/admin/category.php?m=' . $module) . '" class="button green-active">'. __('Categories') .'</a> 
        </span>
        
        <span class="button-group float-right"> 
        
        <a class="add button icon-plus-round green-active large-margin-bottom" href="' . get_url('/news/add_form/') . '">'. __('Add') .'</a> 
        </span>
        
        
        ';
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        if (empty($materials)) {
            $output .= '<div class="collection-item"><b>' . __('Materials not found') . '</b></div>';
        } else {

            $output .= '
                <table class="table">
                    <thead>
                        <tr>
                            
                            
                            <th class="checkbox-cell" scope="col"><input class="checkbox" type="checkbox" name="check-all" id="check-all"></th>
                            <th class="checkbox-cell" scope="col"></th>
                            
                            <th data-field="image">' . __('Image') . '</th>
                            <th data-field="title">' . __('Title') . '</th>
                            <th data-field="category">' . __('Category') . '</th>
                            <th data-field="status">' . __('Status') . '</th>
                            <th data-field="author">' . __('Author') . '</th>
                            <th data-field="date">' . __('Date') . '</th>
                            <th data-field="views">' . __('Views') . '</th>
                            <th data-field="activity">' . __('Activity') . '</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($materials as $mat) {
                $category = $mat->getCategories();

                $output .= '<tr>';
                
                
                $output .= '<th scope="row"><input type="checkbox" name="check" value="' . $mat->getId() . '" class="checkbox"></th>';
                $output .= '<th><span class="list-sort"></span></th>';
                
                
                $output .= '<td><img style="position: relative; z-index: 1; max-width: 50px; max-height: 34px;" class="" src="https://gipermarket.demo.moguta.ru/uploads/product/000/16/top-honey_2018-08-02_18-49-55.jpg"></td>';
                
                
                

                $output .= '<td><a class="truncate" href="' . get_url(entryUrl($mat, $module)) . '"  title="' . h($mat->getTitle()) . '">' . h($mat->getTitle()) . '</a></td>';

                $output .= '<td>';
                foreach($category as $n => $cat)
                {
                    $output .= ($n !== 0 ? ', ' : '').'<a class="truncate" href="' . get_url($module . '/category/' . $mat->getCategory_id()) . '"  title="' . h($cat->getTitle()) . '">' . h($cat->getTitle()) . '</a></td>';
                }
                $output .= '</td>';

                $status_icon = 'mdi-alert-error';
                if ( ($mat->getAvailable() or $mat->getAvailable()===null) and $mat->getPremoder()=='confirmed' )
                {
                    $status_icon = 'icon-eye blue';
                }
                elseif ( (!$mat->getAvailable() or $mat->getAvailable()===null) and $mat->getPremoder()=='confirmed' )
                {
                    $status_icon = 'icon-eye';
                }
                elseif ( $mat->getPremoder()=='nochecked' )
                {
                    $status_icon = 'mdi-action-schedule';
                }
                elseif ( $mat->getPremoder()=='rejected' )
                {
                    $status_icon = 'mdi-navigation-close';
                }
                $output .= '<td><i class="' . $status_icon . ' small"></i></td>';

                $output .= '<td><a class="truncate" href="' . getProfileUrl($mat->getAuthor()->getId()) . '" title="' . h($mat->getAuthor()->getName()) . '">' . h($mat->getAuthor()->getName()) . '</a></td>';

                $output .= '<td>' . $mat->getDate() . '</td>';

                $output .= '<td>' . $mat->getViews() . '</td>';

                $output .= '<td class="align-right low-padding">';
                $output .= '<span class="ds_action button-group children-tooltip">';
                
                
                if (!empty($_GET['premoder']))
                {
                    $output .= '
                    
                    <a class="button" href="' . get_url('/admin/materials_list.php?m=' . $module . '&ac=premoder&status=confirmed&id=' . $mat->getId()) . '" class="on"><i class="icon-tick"></i></a>
                    <a class="button" href="' . get_url('/admin/materials_list.php?m=' . $module . '&ac=premoder&status=rejected&id=' . $mat->getId()) . '" class="off"><i class="icon-cross"></i></a>';
                }
                else
                {
                    $output .= '
                    
                    <a class="button" href="' . get_url('/admin/materials_list.php?m=' . $module . '&ac=edit&id=' . $mat->getId()) . '"><i class="icon-gear"></i></a>
                    
                    <a class="button" href="' . get_url($module . '/edit_form/' . $mat->getId()) . '"><i class="icon-gear"></i></a>
                    <a class="button" href="' . get_url('/admin/materials_list.php?m=' . $module . '&ac=delete&id=' . $mat->getId()) . '" onClick="return _confirm();"><i class="icon-trash"></i></a>';
                }
                $output .= '</span></td>';

                $output .= '</tr>';
            }

            $output .= '</tbody>
                    </table>';

        }
        
        return array($output, $pages);
    }
    
    
    
    /*
    public function add($module) {
        
        
        return array($this->buildForm(), 1);
        //return array(buildForm(11, 22), 1);
        
        
        
        
        
    }
    
    
    public function edit($module,$id) {
        $output = '';
        $model = OrmManager::getModelInstance($module);
        $id = RST::get('id', 'integer');
        $entity = $model->getById($id);
        
        
        
        
        
        //print_r($entity);
        
        
        
        if (!empty($entity)) {
            

            
            return array($this->buildForm($module, $entity), 1);
            
            //return array($output, 1);
         
         
         
         //   $entity->delete();
         //   $_SESSION['message'][] = __('Material has been delete');
        }
        
        //redirect(getReferer(), true);
    }
    
    
    
    
    
    
    
    function buildForm($module, $e=false) {
        $out = '';
        
        $title = $ck_commented = $ck_available = '';
        
        if ($e) {
            $title = $e->getTitle();
            
            
            $ck_commented = $e->getCommented() ? ' checked':'';
            $ck_available = $e->getAvailable() ? ' checked':'';
            
            
            
            $sectionsModel = \OrmManager::getModelInstance($module . 'Categories');
            
            //print_r($sectionsModel);
            //print_r(Module::template);
            
            
            
            //$categories = \Module::check_categories($sectionsModel->getCollection());
            //$cats_selector = $this->_buildSelector($categories, false);
            
            

            
            
        }
        

        
        $out .= '
        <label for="title" class="label relative leb_title" style="top:-10px">' . __('Title') . ':</label>
        
        
        <div class="large-margin-bottom">
            <div class="float-right align-right" style="width: 200px;">
                <label for="commented" class="button icon-chat green-active with-tooltip" title="' . __('Allow using comments') . '">
                    <input type="checkbox" name="commented" id="commented" value="1"'.$ck_commented.'>
                </label>
                <label for="available" class="button icon-light-bulb green-active with-tooltip" title="' . __('Show/hide') . '">
                    <input type="checkbox" name="available" id="available" value="1"'.$ck_available.'>
                </label>
            </div>
            <div style="margin-right:200px;">
                <input class="input full-width with-tooltip tooltip-right" name="title" id="title" type="text" maxlength="128" value="'. $title .'">
            </div>
        </div>
        
        <p class="button-height inline-label">
            <label for="cats_selector" class="label">' . __('Category') . '</label>
            <select name="cats_selector" class="select">
                <option value="1" selected="selected">- Первая категория</option>
            </select>
            
            
            
            
        </p>
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        ';
        
        
        
        
        
        
        
        return $out;
    }
    
    */
    
    
    
    
    
    function premoder($module){
        $Model = OrmManager::getModelInstance($module);
        $entity = $Model->getById(intval($_GET['id']));
        if (!empty($entity)) {
            
            $status = $_GET['status'];
            if (!in_array($status, array('rejected', 'confirmed'))) $status = 'nochecked';
            
            $entity->setPremoder($status);
            $entity->save();
            $_SESSION['message'][] = __('Saved');
            
            
            //clean cache
            $Cache = new Cache;
            $Cache->clean(CACHE_MATCHING_ANY_TAG, array('module_' . $module));
        } else {
            $_SESSION['message'][] = __('Some error occurred');
        }
        redirect('/admin/materials_list.php?m=' . $module . '&premoder=1');
    }
    
    
    public function delete($module) {
        
        $model = OrmManager::getModelInstance($_GET['m']);
        $id = intval($_GET['id']);
        $entity = $model->getById($id);
        
        if (!empty($entity)) {
            $entity->delete();
            $_SESSION['message'][] = __('Material has been delete');
        }
        
        redirect(getReferer(), true);
    }
}




$pageNav = $Controll->pageTitle;
$pageNavr = $pages;
include_once ROOT . '/admin/template/header.php';
?>

<!-- Main title -->
	<hgroup id="main-title" class="thin">
		<h1><?php echo $pageTitle; ?></h1>
		<h2><?php echo date('M');?> <strong><?php echo date('j');?></strong></h2>
	</hgroup>
    <div class="with-padding">
        <form method="POST" action="" enctype="multipart/form-data">
            
            <?php echo $output;?>
            
        </form>
    </div>












<?php include_once 'template/footer.php'; ?>
