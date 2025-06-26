<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/


include_once R.'admin/inc/adm_boot.php';



class PagesAdminController {

    public $Model;


    public function __construct()
    {
        $this->Model = \OrmManager::getModelInstance('Pages');
    }


    public function move_node($params)
    {
        if (intval($params['id']) < 2) return json_encode(array('status' => '0'));
        if (intval($params['ref']) < 1) $params['ref'] = 1;


        if ($params['copy']) {
            $parent = $this->Model->getById($params['ref']);
            $entity = $this->Model->getById($params['id']);
            $path = ('.' === $entity->getPath()) ? null : $entity->getPath();
            $tree = $this->Model->getCollection(array("`path` LIKE '" . $path . $entity->getId() . ".%'"));

            if (!empty($tree)) $tree = $this->buildPagesTree($tree);
            else $tree = array();

            $entity->setSub($tree);
            $tree = array($entity);

            $new_id = $this->copyNode($tree, $parent);
            return json_encode(array('status' => 1, 'id' => $new_id));

        } else {
            $this->Model->replace($params['id'], intval($params['ref']));
            return json_encode(array('status' => 1, 'id' => $params['id']));
        }

        return json_encode(array('status' => '0'));
    }


    private function copyNode($tree, $parent)
    {
        foreach($tree as $k => $v) {
            $path = ('.' === $parent->getPath()) ? null : $parent->getPath();
            $data = clone $v;

            $data->setId(false);
            $data->setParent_id($parent->getId());
            $data->setPath($path . $parent->getId() . '.');

            $data->save();
            $id = $data->getId();


            $sub = $v->getSub();
            if (!empty($sub)) {
                foreach($sub as $child) {
                    $this->copyNode($child, $data);
                }
            }
        }
        return !empty($id) ? $id : false;
    }


    public function rename_node($params)
    {
        if (intval($params['id']) < 2 || empty($params['title'])) return json_encode(array('status' => '0'));

        $entity = $this->Model->getById($params['id']);
        $entity->setName($params['title']);
        $entity->save();
        return json_encode(array('status' => 1));
    }


    public function remove_node($params)
    {
        if (intval($params['id']) < 2) return json_encode(array('status' => '0'));

        $this->Model->delete($params['id']);
        return json_encode(array('status' => 1));
    }


    /**
    "operation" : "create_node",
    "id" : data.rslt.parent.attr("id").replace("node_",""),
    "position" : data.rslt.position,
    "title" : data.rslt.name,
    "type" : data.rslt.obj.attr("rel")
     */
    public function create_node($params)
    {
        if (intval($params['id']) < 1 || empty($params['title'])) return json_encode(array('status' => '0'));

        $parent = $this->Model->getById($params['id']);
        if (!empty($parent)) {

            $path = ('.' === $parent->getPath()) ? null : $parent->getPath();
            $template = ($parent->getTemplate()) ? $parent->getTemplate() : '';

            $data = array(
                'path' => $path . $parent->getId() . '.',
                'name' => $params['title'],
                'parent_id' => $params['id'],
                'template' => $template,
            );

            $new_entity = new \PagesModule\ORM\PagesEntity($data);
            $new_entity->save();
            if ($new_entity->getId()) return json_encode(array(
                'status' => 1,
                'id' => $new_entity->getId(),
            ));
        }

        return json_encode(array('status' => '0'));
    }


    /**
     *
     */
    public function get_children($params)
    {
        $out = array();
        if (!isset($params['id'])) return json_encode($out);


        if (0 != $params['id'])  {
            $parent = $this->Model->getById($params['id']);
            $path = ('.' === $parent->getPath()) ? null : $parent->getPath();
            $tree = $this->Model->getCollection(array("`path` LIKE '" . $path . $parent->getId() . ".%'"));



            if (!empty($tree)) {
                $tree = $this->buildPagesTree($tree);

                foreach($tree as $k => $v){
                    $out[] = array(
                        "attr" => array(
                            "id" => "node_".$v->getId(),
                            "rel" => (false != $v->getSub()) ? "drive" : "default",
                        ),
                        "data" => $v->getName(),
                        "state" => (false != $v->getSub() || $params['id'] == 0) ? "closed" : ""
                    );
                }
            }
        } else {
            $root = array(
                "attr" => array(
                    "id" => "node_1",
                    "rel" => "drive",
                ),
                "data" => 'root',
                "state" => "closed"
            );
            $out = array($root);
        }
        return json_encode($out);
    }



    /**
     * Get array with tree ierarhy
     */
    private function buildPagesTree($pages, $tree = array())
    {
        if (!empty($tree)) {
            foreach ($tree as $tk => $tv) {


                $sub = array();
                foreach ($pages as $pk => $pv) {


                    $path = $tv->getPath();
                    if ('.' === $path) $path = '';
                    if ($pv->getPath() === $path . $tv->getId() . '.') {
                        unset($pages [$pk]);
                        $sub[] = $pv;
                    }
                }
                if (!empty($sub)) $sub = $this->buildPagesTree($pages, $sub);
                $tv->setSub($sub);
            }


        } else {
            $lowest = false;
            foreach ($pages as $pk => $pv) {
                $path = $pv->getPath();
                if (false === $lowest || substr_count($path, '.') < substr_count($lowest, '.')) {
                    $lowest = $path;
                }
            }


            if (false !== $lowest) {
                foreach ($pages as $k => $page) {
                    if ($lowest === $page->getPath()) {
                        unset($pages[$k]);
                        $tree[] = $page;
                    }
                }

                $tree = $this->buildPagesTree($pages, $tree);
            }
        }

        return $tree;
    }


    public function get($params)
    {
        $entity = $this->Model->getById(intval($params['id']))->asArray();
        if (empty($entity)) return json_encode(array('status' => '0'));

        return json_encode(array(
            'status' => '1',
            'data' => $entity,
        ));
    }


    /**
     *
     */
    public function save($params)
    {
        if (!empty($params['id'])) {
            $id = intval($params['id']);
            $entity = $this->Model->getById(intval($params['id']));
            if (empty($entity)) return json_encode(array('status' => '0'));

            $entity->setName($params['title']);
            $entity->setUrl($params['url']);
            $entity->setMeta_keywords($params['meta_keywords']);
            $entity->setMeta_description($params['meta_description']);
            $entity->setTemplate($params['template']);
            $entity->setContent($params['content']);
            $entity->save();


        } else {
            $data = array(
                'name' => $params['title'],
                'template' => $params['template'],
                'meta_keywords' => $params['meta_keywords'],
                'meta_description' => $params['meta_description'],
                'content' => $params['content'],
                'url' => $params['url'],
                'template' => $params['template'],
            );
            $id = $this->Model->add($data);

            if (empty($id)) return json_encode(array('status' => '0'));
            return json_encode(array('status' => '1', 'id' => $id));
        }


        return json_encode(array('status' => '1', 'id' => $id));
    }
}



$jstree = new PagesAdminController;
if(!empty($_REQUEST['operation']) && strpos($_REQUEST['operation'], '_') !== 0 && method_exists($jstree, $_REQUEST['operation'])) {
    header("HTTP/1.0 200 OK");
    header('Content-type: application/json; charset=utf-8');
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Pragma: no-cache");
    echo $jstree->{$_REQUEST["operation"]}($_REQUEST);
    die();
}



$Register = \Register::getInstance();
$pageTitle = __('Pages editor');
$pageNav = $pageTitle;
$pageNavr = '';


$page = array(
    'name' => '',
    'id' => '',
    'url' => '',
    'parent_id' => '',
    'meta_keywords' => '',
    'meta_description' => '',
    'content' => '',
    'template' => '',
);


include_once R.'admin/template/header.php';
?>





<!-- Main title -->
		<hgroup id="main-title" class="thin">
			<h1><?php echo $pageTitle; ?></h1>
			<h2><?php echo date('M');?> <strong><?php echo date('j');?></strong></h2>
		</hgroup>

<div class="with-padding">





















































<link type="text/css" rel="StyleSheet" href="<?php echo WWW_ROOT ?>/modules/pages/admin/redactor/css/redactor.css" />


<div class="divider b15bm"></div>

<div class="row">
    <div class="col s12 m3">
        <div class="wrapper">
            <div class="tree-wrapper">
                <div id="pageTree"></div>
            </div>
        </div>
    </div>



    
    <form id="dsForm" style="opacity:1;" method="POST" action="">
    <div class="col s12 m9">
        
        <div class="progress">
            <div class="indeterminate"></div>
        </div>
        
        
        <div class="input-field col s12">
            <input type="hidden" name="id" value="">
            <input id="title" type="text" name="title">
            <label for="title"><?php echo __('Title') ?></label>
        </div>
        <div class="input-field col s12">
            <input for="url" type="text" name="url" value="">
            <label for="url"><?php echo __('URL') ?></label>
        </div>
        <div class="input-field col s6">
            <input id="meta_keywords" type="text" name="meta_keywords" value="">
            <label for="meta_keywords"><?php echo __('Meta-Keywords') ?></label>
        </div>
        <div class="input-field col s6">
            <input id="meta_description" type="text" name="meta_description" value="">
            <label for="meta_description"><?php echo __('Meta-Description') ?></label>
        </div>
        <div class="input-field col s6">
            <input id="template" type="text" name="template" value="">
            <label for="template"><?php echo __('Template') ?></label>
        </div>
        <div class="input-field col s6">
            <input id="dinamic_tag" type="text" name="dinamic_tag" value="" disabled>
            <label for="dinamic_tag" class="truncate"><?php echo __('Dinamic page tag') ?></label>
        </div>
        <div class="input-field col s12">
            <textarea id="mainTextarea" name="content"></textarea>
        </div>
        <div class="input-field col s12">
            <input class="btn" type="submit" name="send" value="<?php echo __('Save') ?>" />
        </div>
        
    </div>
    </form>
    <div class="clear"></div>

</div>












</div>






<script type="text/javascript" src="<?php echo WWW_ROOT ?>/modules/pages/admin/redactor/redactor.min.js"></script>
<script type="text/javascript" src="<?php echo WWW_ROOT ?>111/admin/js/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo WWW_ROOT ?>111/admin/js/jquery.validate.js"></script>

<script type="text/javascript" src="<?php echo WWW_ROOT ?>111/admin/js/jquery.hotkeys.js"></script>
<script type="text/javascript" src="<?php echo WWW_ROOT ?>/modules/pages/admin/jstree/jstree.min.js?v=<?php echo rand();?>"></script>

<script type="text/javascript">

$(document).ready(function(){
    redactor = $('#mainTextarea').redactor({
        css: '<?php echo WWW_ROOT ?>/modules/pages/admin/redactor/css/redactor.css',
        cleanup: false,
        autoclear: false,
        autoformat: false,
        convert_links: false,
        convertLinks: false,
        convertDivs: false,
        init_clear: false,
        remove_styles: false,
        remove_classes: false,
        imageGetJson: '<?php echo WWW_ROOT ?>/modules/pages/admin/scripts/img_uploader.php',
        imageUpload: '<?php echo WWW_ROOT ?>/modules/pages/admin/scripts/img_uploader.php',
        fileUpload: '<?php echo WWW_ROOT ?>/modules/pages/admin/scripts/img_uploader.php',
        autoresize: true,
        deniedTags: [],
        removeEmptyTags: true,
        phpTags: true,
        uploadCrossDomain: true
    });













$('#dsForm').on("submit", function(){
	submitForm();
	return false;
});





//    $.validator.addMethod('chars', function(value, element){
//        return value.match(/[ \da-z\-_]*/i);
//    }, "Don't use special chars");
/*    $("#dsForm").validate({
        submitHandler: function(){
            submitForm();
        },
        rules: {
            title: {
                required: true,
                chars: true,
                maxlength: 250,
                minlength: 1,
            },
            url: {
                chars: true,
                maxlength: 250,
                minlength: 1,
            },
            meta_keywords: {
                maxlength: 250,
                minlength: 1,
            },
            meta_description: {
                maxlength: 250,
                minlength: 1,
                chars: true
            },
            template: {
                maxlength: 50,
                minlength: 1,
                chars: true
            },
            content: {
                maxlength: 50000,
                minlength: 1
            },
        }
    });*/
});


jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};









$(function () {
$("#pageTree")
    // Write log
    .bind("before.jstree", function (e, data) {
        $("#alog").append(data.func + "<br />");
    })
    .jstree({
        // List of active plugins
        "themes" : {
            "theme" : "classic",
            "dots" : true,
            "icons" : true,
            "url" : "<?php echo WWW_ROOT ?>/modules/pages/admin/jstree/themes/classic/style.css"
        },
        "plugins" : [
            "themes","json_data","ui","crrm","cookies","dnd","search","types","contextmenu"
        ],

        // I usually configure the plugin that handles the data first
        // This example uses JSON as it is most common
        "json_data" : {
            // This tree is ajax enabled - as this is most common, and maybe a bit more complex
            // All the options are almost the same as jQuery's AJAX (read the docs)
            "ajax" : {
                // the URL to fetch the data
                "url" : "page.php",
                // the `data` function is executed in the instance's scope
                // the parameter is the node being loaded
                // (may be -1, 0, or undefined when loading the root nodes)
                "data" : function (n) {
                    // the result is fed to the AJAX request `data` option
                    return {
                        "operation" : "get_children",
                        "id" : n.attr ? n.attr("id").replace("node_","") : 0
                    };
                }
            }
        },
        // Configuring the search plugin
        "search" : {
            // As this has been a common question - async search
            // Same as above - the `ajax` config option is actually jQuery's AJAX object
            "ajax" : {
                "url" : "page.php",
                // You get the search string as a parameter
                "data" : function (str) {
                    return {
                        "operation" : "search",
                        "search_str" : str
                    };
                }
            }
        },
        // Using types - most of the time this is an overkill
        // read the docs carefully to decide whether you need types
        "types" : {
            // I set both options to -2, as I do not need depth and children count checking
            // Those two checks may slow jstree a lot, so use only when needed
            "max_depth" : -2,
            "max_children" : -2,
            // I want only `drive` nodes to be root nodes
            // This will prevent moving or creating any other type as a root node
            "valid_children" : [ "drive","default" ],
            "types" : {
                // The default type
                "default" : {
                    // I want this type to have no children (so only leaf nodes)
                    // In my case - those are files
                    //"valid_children" : "none",
                    // If we specify an icon for the default type it WILL OVERRIDE the theme icons
                    "icon" : {
                        "image" : "<?php echo WWW_ROOT ?>/modules/pages/admin/jstree/img/file.png"
                    }
                },
                // The `folder` type
                "folder" : {
                    // can have files and other folders inside of it, but NOT `drive` nodes
                    "valid_children" : [ "default", "folder" ],
                    "icon" : {
                        "image" : "<?php echo WWW_ROOT ?>/modules/pages/admin/jstree/img/folder.png"
                    }
                },
                // The `drive` nodes
                "drive" : {
                    // can have files and folders inside, but NOT other `drive` nodes
                    "valid_children" : [ "default", "folder" ],
                    "icon" : {
                        "image" : "<?php echo WWW_ROOT ?>/modules/pages/admin/jstree/img/root.png"
                    },
                    // those prevent the functions with the same name to be used on `drive` nodes
                    // internally the `before` event is used
                    "start_drag" : false,
                    "move_node" : false,
                    "delete_node" : false,
                    "remove" : false
                }
            }
        },
        // UI & core - the nodes to initially select and open will be overwritten by the cookie plugin

        // the UI plugin - it handles selecting/deselecting/hovering nodes
        "ui" : {
            // this makes the node with ID node_4 selected onload
            "initially_select" : [ "node_4" ]
        },
        // the core plugin - not many options here
        "core" : {
            // just open those two nodes up
            // as this is an AJAX enabled tree, both will be downloaded from the server
            "initially_open" : [ "node_2" , "node_3" ]
        }
    })
    .bind("create.jstree", function (e, data) {
        $.post(
            "page.php",
            {
                "operation" : "create_node",
                "id" : data.rslt.parent.attr("id").replace("node_",""),
                "position" : data.rslt.position,
                "title" : data.rslt.name,
                "type" : data.rslt.obj.attr("rel")
            },
            function (r) {
                if(r.status) {
                    $(data.rslt.obj).attr("id", "node_" + r.id);
                }
                else {
                    $.jstree.rollback(data.rlbk);
                }
            }
        );
    })
    .bind("remove.jstree", function (e, data) {
        data.rslt.obj.each(function () {
            $.ajax({
                async : false,
                type: 'POST',
                url: "page.php",
                data : {
                    "operation" : "remove_node",
                    "id" : this.id.replace("node_","")
                },
                success : function (r) {
                    if(!r.status) {
                        data.inst.refresh();
                    }
                }
            });
        });
    })
    .bind("rename.jstree", function (e, data) {
        $.post(
            "page.php",
            {
                "operation" : "rename_node",
                "id" : data.rslt.obj.attr("id").replace("node_",""),
                "title" : data.rslt.new_name
            },
            function (r) {
                if(!r.status) {
                    $.jstree.rollback(data.rlbk);
                }
            }
        );
    })
    .bind("move_node.jstree", function (e, data) {
        data.rslt.o.each(function (i) {
            $.ajax({
                async : false,
                type: 'POST',
                url: "page.php",
                data : {
                    "operation" : "move_node",
                    "id" : $(this).attr("id").replace("node_",""),
                    "ref" : data.rslt.cr === -1 ? 1 : data.rslt.np.attr("id").replace("node_",""),
                    "position" : data.rslt.cp + i,
                    "title" : data.rslt.name,
                    "copy" : data.rslt.cy ? 1 : 0
                },
                success : function (r) {
                    if(!r.status) {
                        $.jstree.rollback(data.rlbk);
                    }
                    else {
                        $(data.rslt.oc).attr("id", "node_" + r.id);
                        if(data.rslt.cy && $(data.rslt.oc).children("UL").length) {
                            data.inst.refresh(data.inst._get_parent(data.rslt.oc));
                        }
                    }
                    //$("#analyze").click();
                }
            });
        });
    })
    .bind("select_node.jstree", function (event, data) {
        // `data.rslt.obj` is the jquery extended node that was clicked
        var id = data.rslt.obj.attr("id").replace("node_","");
        fillForm(id);
    });

});




/**
 * Get entity and fill form fields
 */
function fillForm(id){
    $(".progress > div").removeClass("hide");
    var form = $('#dsForm');
    // Clear form fields. After it, we can create new page
    if (id < 2) {
        $(form).find('input[type="text"], input[type="hidden"]').each(function(){
            $(this).val('');
        });
        $(form).find('textarea[name="content"]').val('');
        $('div.redactor_editor').html('');
        
        Materialize.updateTextFields();
        $(".progress > div").addClass("hide");
        return;
    }




    $.get('page.php?operation=get&id='+id, function(data){
        var status = data.status; data = data.data;
        $(form).find('input[name="title"]').val(data.name);
        $(form).find('input[name="id"]').val(data.id);
        $(form).find('input[name="url"]').val(data.url);
        $(form).find('input[name="meta_keywords"]').val(data.meta_keywords);
        $(form).find('input[name="meta_description"]').val(data.meta_description);
        $(form).find('input[name="template"]').val(data.template);
        $(form).find('input[name="dinamic_tag"]').val('[~ '+data.id+' ~]');


        if (data.content.match(/<script[^>]*>[\S\s]*<\/script>/gi)) {
            //$('#mainTextarea').data("redactor").opts;
            if ($('#mainTextarea').data("redactor").opts.visual)
                $('#mainTextarea').data("redactor").toggle();
            $(form).find('textarea[name="content"]').val(data.content);
        } else {
            $(form).find('textarea[name="content"]').val(data.content);
            $('div.redactor_editor').html(data.content);
        }

        Materialize.updateTextFields();
        $(".progress > div").addClass("hide");
    });
}

/**
 * Save changes or create page
 */
function submitForm(){
    $(".progress > div").removeClass("hide");
    var form = $('#dsForm');
    var id = $(form).find('input[name="id"]').val();
    
    $.post('page.php?operation=save&id='+id,
        $(form).serialize(),
        function(data){
            $("#pageTree").jstree('refresh', -1);
            if (typeof data.id != 'undefined')
                setTimeout('fillForm('+data.id+')', 1000);

            $(".progress > div").addClass("hide");
        }
    );
}


</script>


<div class="row">
    <div class="col s12">
        <h5 class="light"><?php echo __('Global markers') ?></h5>
        <ul>
            <li><b>{{ content }}</b> - <?php echo __('Base content page') ?></li>
            <li><b>{{ title }}</b> - <?php echo __('Title for page') ?></li>
            <li><b>{{ description }}</b> - <?php echo __('Meta-Description') ?></li>
            <li><b>{{ drs_wday }}</b> - <?php echo __('Day mini') ?></li>
            <li><b>{{ drs_date }}</b> - <?php echo __('Date') ?></li>
            <li><b>{{ drs_time }}</b> - <?php echo __('Time') ?></li>
            <li><b>{{ user.name }}</b> - <?php echo __('Nick of current user') ?></li>
            <li><b>{{ user.group }}</b> - <?php echo __('Group of current user') ?></li>
            <li><b>{{ categories }}</b> - <?php echo __('categories list in section') ?></li>
            <li><b>{{ counter }}</b> - <?php echo __('counter') ?></li>
            <li><b>{{ drs_year }}</b> - <?php echo __('Year') ?></li>
            <li><b>{{ powered_by }}</b> - <?php echo __('About powered by') ?></li>
            <li><b>{{ comments }}</b> - <?php echo __('About comments marker') ?></li>
            <li><b>{{ personal_page_link }}</b> - <?php echo __('About personal_page_link marker') ?></li>
        </ul>
    </div>
</div>



<?php
include_once R.'admin/template/footer.php';


