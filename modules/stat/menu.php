<?php
return array(
    'icon_class' => 'icon-read',
    'pages'   => array(
        WWW_ROOT.'/admin/settings.php?m=stat'                  => __('Settings'),
        WWW_ROOT.'/admin/design.php?m=stat'                    => __('Design'),
        WWW_ROOT.'/admin/category.php?m=stat'                => __('Sections editor'),
        WWW_ROOT.'/admin/additional_fields.php?m=stat'         => __('Additional fields'),
        WWW_ROOT.'/admin/materials_list.php?m=stat&premoder=1' => __('Materials premoderation'),
        WWW_ROOT.'/admin/materials_list.php?m=stat'            => __('Materials list'),
        WWW_ROOT.'/admin/comments_list.php?m=stat&premoder=1'  => __('Comments premoderation'),
        WWW_ROOT.'/admin/comments_list.php?m=stat'             => __('Comments list'),
    ),
);