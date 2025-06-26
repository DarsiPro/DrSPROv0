<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/

include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';





$pageTitle = __('Menu editor');
$popups = '';

$menu_conf_file = ROOT . '/sys/settings/menu.dat';

    
if (!empty($_GET['ac']) && $_GET['ac'] === 'add') {
    $data = array();
    $data['title'] = (!empty($_POST['title'])) ? trim($_POST['title']) : '';
    $data['url'] = (!empty($_POST['url'])) ? trim($_POST['url']) : '';
    $data['prefix'] = (!empty($_POST['prefix'])) ? trim($_POST['prefix']) : '';
    $data['sufix'] = (!empty($_POST['sufix'])) ? trim($_POST['sufix']) : '';
    $data['newwin'] = (!empty($_POST['newwin'])) ? trim($_POST['newwin']) : '';
    
    if (!empty($data['title']) && !empty($data['url'])) {
        if (file_exists($menu_conf_file)) {
            $menu = unserialize(file_get_contents($menu_conf_file));
        } else {
            $menu = array();
        }
        $data['id'] = getMenuPointId($menu) + 1;
        $menu[] = $data;
        file_put_contents($menu_conf_file, serialize($menu));
        redirect('/admin/menu_editor.php');
    } else {
        $_SESSION['message'][] = __('Some error occurred');
        redirect('/admin/menu_editor.php');
    }
} else if (!empty($_GET['ac']) && $_GET['ac'] === 'edit' && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id < 1) {
        $_SESSION['message'][] = __('Some error occurred');
        redirect('/admin/menu_editor.php');
    }
    $data = array();
    $data['id'] = $id;
    $data['title'] = (!empty($_POST['title'])) ? trim($_POST['title']) : '';
    $data['url'] = (!empty($_POST['url'])) ? trim($_POST['url']) : '';
    $data['prefix'] = (!empty($_POST['prefix'])) ? trim($_POST['prefix']) : '';
    $data['sufix'] = (!empty($_POST['sufix'])) ? trim($_POST['sufix']) : '';
    $data['newwin'] = (!empty($_POST['newwin'])) ? trim($_POST['newwin']) : '';
    
    if (!empty($data['title']) && !empty($data['url']) && !empty($data['id'])) {
        $menu = unserialize(file_get_contents($menu_conf_file));
        $menu = saveMenu($id, $data, $menu);
        file_put_contents($menu_conf_file, serialize($menu));
        redirect('/admin/menu_editor.php');
    } else {
        $_SESSION['message'][] = __('Some error occurred');
        redirect('/admin/menu_editor.php');
    }
}
    

function saveMenu($id, $data, $menu) {
    if (!empty($menu) && count($menu) > 0) {
        foreach ($menu as $key => $value) {
            if (!empty($value['id']) && $value['id'] == $id) {
                $menu[$key] = $data;
                if (isset($value['sub'])) $menu[$key]['sub'] = $value['sub'];
                break;
            }
            
            if (!empty($value['sub']) && count($value['sub']) > 0) {
                $menu[$key]['sub'] = saveMenu($id, $data, $value['sub']);
            }
        }
    }
    
    
    return $menu;
}
    
    
function getMenuPointId($menu) {
    $n = 0;
    if (empty($menu)) return 0;
    foreach ($menu as $k => $v) {
        if (empty($v['id'])) continue;
        if ($n < $v['id']) $n = $v['id'];
        if (!empty($v['sub']) && is_array($v['sub'])) {
            $ns = getMenuPointId($v['sub']);
            if ($n < $ns) $n = $ns;
        }
    }
    
    
    return $n;
}
    
function parseNode($data) {
    $output = array();
    $n = 0;
    
    if (!empty($data) && is_array($data)) {	
        foreach ($data as $key => $value) {
            if (empty($value['url']) || empty($value['title']) || empty($value['id'])) {
                continue;
                $n++;
            }
            
            
            $output[$n] = array(
                'id' => trim($value['id']),
                'url' => trim($value['url']),
                'title' => trim($value['title']),
                'prefix' => (!empty($value['prefix'])) ? trim($value['prefix']) : '',
                'sufix' => (!empty($value['sufix'])) ? trim($value['sufix']) : '',
                'newwin' => (!empty($value['newwin'])) ? trim($value['newwin']) : '',
            );
            
            
            if (!empty($value['sub']) && is_array($value['sub'])) {
                $output[$n]['sub'] = parseNode($value['sub']);
            }
            
            $n++;
        }
    }
    

    return $output;
}


function buildMenu($node) {
    $out = '';
    $n = 0;
    global $popups;
    
    if (!empty($node) && is_array($node)) {
        foreach ($node as $key => $value) {
            if (empty($value['url']) || empty($value['title']) || empty($value['id'])) continue;
            
            $value['prefix'] = (!empty($value['prefix'])) ? trim($value['prefix']) : '';
            $value['sufix'] = (!empty($value['sufix'])) ? trim($value['sufix']) : '';
            $value['newwin'] = (!empty($value['newwin'])) ? 1 : 0;
            
            
            
            
            $out .= '<li class="collection-item">' . "\n";
            
            
            // MENU ITEM
            $out .= '<div>'.h($value['title']).
                    '<input type="hidden" name="id" value="' . $value['id'] . '" />' . "\n" .
                    '<input type="hidden" name="url" value="' . h($value['url']) . '" />' . "\n" .
                    '<input type="hidden" name="title" value="' . h($value['title']) . '" />' . "\n" .
                    '<input type="hidden" name="prefix" value="' . h($value['prefix']) . '" />' . "\n".
                    '<input type="hidden" name="sufix" value="' . h($value['sufix']) . '" />' . "\n" .
                    '<input type="hidden" name="newwin" value="' . h($value['newwin']) . '" />' . "\n" .
                    '<div class="right">'.
                        '<a class="right modal-trigger mdi-action-settings" title="'.__('Edit').'" href="#edit' . $value['id'] . '"></a>' . "\n" .
                        '<a class="right mdi-action-delete" title="'.__('Delete').'" onClick="deletePoint(this);"></a>' . "\n" .
                    '</div></div>' . "\n";
            
            
            // MENU ITEM EDIT FORM
            $checked = (!empty($value['newwin'])) ? 'selected="selected"' : '';
            
            $popups .= '<div id="edit' . $value['id'] . '" class="modal modal-fixed-footer">
                    <form action="menu_editor.php?ac=edit&id=' . $value['id'] . '" method="POST">
                    <div class="modal-content">
                        <h4>' . __('Editing point') . '</h4>
                        <div class="input-field col s5">
                            <input id="title' . $value['id'] . '" type="text" name="title" value="' . h($value['title']) . '" required/>
                            <label for="title' . $value['id'] . '">' . __('Point name') . '</label>
                        </div>
                        <div class="input-field col s7">
                            <input id="url' . $value['id'] . '" type="text" name="url" value="' . h($value['url']) . '" required/>
                            <label for="url' . $value['id'] . '">' . __('URL') . '</label>
                        </div>
                        <div class="input-field col s6">
                            <input id="prefix' . $value['id'] . '" type="text" name="prefix" value="' . h($value['prefix']) . '" />
                            <label for="prefix' . $value['id'] . '">' . __('Prefix') . '</label>
                        </div>
                        <div class="input-field col s6">
                            <input id="sufix' . $value['id'] . '" type="text" name="sufix" value="' . h($value['sufix']) . '" />
                            <label for="sufix' . $value['id'] . '">' . __('Suffix') . '</label>
                        </div>
                        <div class="input-field col s12">
                            <input id="newwin' . $value['id'] . '" type="checkbox" value="1" name="newwin' . $value['id'] . '" ' . $checked . ' />
                            <label for="newwin' . $value['id'] . '">' . __('In new window') . '</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
                        <input type="submit" value="' . __('Save') . '" name="send" class="btn" />
                    </div>
                </form>
            </div>';
            
    

            // MENU SUB ITEMS FOR THIS ITEM
            $out .= '<ul class="sortable">' . "\n";
            if (!empty($value['sub']) && is_array($value['sub'])) {
                $out .= buildMenu($value['sub']) . "\n";
            }
            $out .= '</ul>' . "\n";
            
            $out .= '</li>';
            $n++;
        }
    }
    
    return $out;
}



if (isset($_POST['data']) && is_array($_POST['data'])) {
    $array_menu = parseNode($_POST['data']);

    if (isset($array_menu) && is_array($array_menu)) {
        file_put_contents($menu_conf_file, serialize($array_menu));
    }
    die();
}



$menu = array();
if (file_exists($menu_conf_file)) {
    $menu = unserialize(file_get_contents($menu_conf_file));
}

include_once ROOT . '/admin/template/header.php';
?>



<script src="js/jquery-ui.min.js"></script>
<script type="text/javascript">
$(function(){
    $('ul.sortable').sortable({
        items:"li",
        appendTo:"ul",
        connectWith: "ul",
        placeholder:"plholder",
        update:function(){
            //alert(ui.item);
        },
    });
    
    
    // the "href" attribute of .modal-trigger must specify the modal ID that wants to be triggered
    $('.modal-trigger').leanModal();
});

function deletePoint(obj) {
    var node = $(obj).closest("li");
    node.remove();
    return true;
}

function sortList(id, mlist) {
    var points = id.find(">li");
    //var children = id.find(">li>ul");
    
    points.each(function(key){
        var point = points[key];
        point = $(point);
        mlist[key] = {};
        mlist[key]['url'] = point.find("div").find("input[name=url]").val();
        mlist[key]['title'] = point.find("div").find("input[name=title]").val();
        mlist[key]['prefix'] = point.find("div").find("input[name=prefix]").val();
        mlist[key]['sufix'] = point.find("div").find("input[name=sufix]").val();
        mlist[key]['newwin'] = point.find("div").find("input[name=newwin]").val();
        mlist[key]['id'] = point.find("div").find("input[name=id]").val();
        
        mlist[key]['sub'] = {};
        mlist[key]['sub'] = sortList(point.find("ul"), mlist[key]['sub']);
    });
    return mlist;
}

function form1(el) {
    var button = el;
    $(button).attr("disabled","disabled");
    $(button).next('.preloader-wrapper').addClass("active");
    var list = {};
    list = sortList($('#sort'), list);
    $.post('menu_editor.php', {data:list}, function(){
        $(button).removeAttr("disabled");
        $(button).next('.preloader-wrapper').removeClass("active");
    });
}
</script>




<div class="row">
    <div class="col s12">
        
        <h4 class="light"><?php echo __('Menu editor') ?></h4>
        
        
        <blockquote>
            <?php echo __('Information for menu editor') ?>
        </blockquote>
        
        <div class="input-field col s12">
            <input id="marker" type="text" value="{{ mainmenu }}" disabled />
            <label for="marker"><?php echo __('Marker for insert') ?></label>
        </div>
        
        <div class="col s12">
            <ul id="sort" class="sortable collection">
                <?php  echo buildMenu($menu); ?>
            </ul>
        </div>
        <div class="input-field col s12">
            <input class="btn left" type="submit" value="<?php echo __('Save') ?>" onClick="form1(this);" />
            <div class="preloader-wrapper small left b15lm">
                <div class="spinner-layer spinner-blue-only">
                    <div class="circle-clipper left">
                        <div class="circle"></div>
                    </div><div class="gap-patch">
                        <div class="circle"></div>
                    </div><div class="circle-clipper right">
                        <div class="circle"></div>
                    </div>
                </div>
            </div>
            <a href="#addCat" class="modal-trigger btn right"><i class="mdi-content-add left"></i><?php echo __('Add point') ?></a>
        </div>

        <?php echo $popups; ?>
    
    </div>
</div>





<div id="addCat" class="modal modal-fixed-footer">
    <form action="menu_editor.php?ac=add" method="POST">
        <div class="modal-content">
            <h4><?php echo __('Adding point') ?></h4>
            
            <div class="input-field col s5">
                <input id="title" type="text" name="title" required/>
                <label for="title"><?php echo __('Point name') ?></label>
            </div>
            <div class="input-field col s7">
                <input id="url" type="text" name="url" required/>
                <label for="url"><?php echo __('URL') ?></label>
            </div>
            <div class="input-field col s6">
                <input id="prefix" type="text" name="prefix" />
                <label for="prefix"><?php echo __('Prefix') ?></label>
            </div>
            <div class="input-field col s6">
                <input id="sufix" type="text" name="sufix"/>
                <label for="sufix"><?php echo __('Suffix') ?></label>
            </div>
            <div class="input-field col s12">
                <input id="newwin" type="checkbox" name="newwin" value="1"/>
                <label for="newwin"><?php echo __('In new window') ?></label>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-action modal-close btn-flat">ОТМЕНИТЬ</a>
            <input type="submit" value="<?php echo __('Add') ?>" name="send" class="btn" />
        </div>
    </form>
</div>




<?php include_once 'template/footer.php';