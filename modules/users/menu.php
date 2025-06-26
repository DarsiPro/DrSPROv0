<?php
return array(
    'icon_class' => 'icon-card',
    'pages'   => array(
        WWW_ROOT.'/admin/settings.php?m=users'            => __('Settings'),
        WWW_ROOT.'/admin/design.php?m=users'              => __('Design'),
        WWW_ROOT.'/admin/additional_fields.php?m=users'   => __('Additional fields'),
        WWW_ROOT.'/admin/users/groups.php'                => __('Group editor',false,'users'),
        WWW_ROOT.'/admin/users/rating.php'                => __('Users rank',false,'users'),
        WWW_ROOT.'/admin/users/sendmail.php'              => __('Mass mailing',false,'users'),
        WWW_ROOT.'/admin/users/mail_tmp.php'              => __('Editing mail templates',false,'users'),
        WWW_ROOT.'/admin/users/reg_rules.php'             => __('Registration rules',false,'users'),
        WWW_ROOT.'/admin/users/list.php'                  => __('Users list',false,'users'),
    ),
);