<?php
return array(
    'icon_class' => 'icon-new',
    'pages'   => array(
        WWW_ROOT.'/admin/settings.php?m=news'                  => __('Settings'),
        WWW_ROOT.'/admin/design.php?m=news'                    => __('Design'),
        WWW_ROOT.'/admin/category.php?m=news'                => __('Sections editor'),
        WWW_ROOT.'/admin/additional_fields.php?m=news'         => __('Additional fields'),
        WWW_ROOT.'/admin/materials_list.php?m=news&premoder=1' => __('Materials premoderation'),
        WWW_ROOT.'/admin/materials_list.php?m=news'            => __('Materials list'),
        WWW_ROOT.'/admin/comments_list.php?m=news&premoder=1'  => __('Comments premoderation'),
        WWW_ROOT.'/admin/comments_list.php?m=news'             => __('Comments list'),
    ),
);