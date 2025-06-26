<?php
return array (
    // system
    'active' => 1,
    'std_admin_pages' => array(
        // 'settings' => only true(need on/off)
        // 'design' => only auto(configure template_parts.php)
        'comments_list' => false,
        'category' => false,
        'additional_fields' => false,
        'materials_list' => false
    ),

    // non-system
    'title' => 'Форум',
    'description' => 'CMS форум',
    'not_reg_user' => 'Гостелло',
    'max_post_lenght' => '999999999',
    'themes_per_page' => '20',
    'posts_per_page' => '30',
    'max_attaches' => '5',
    'max_attaches_size' => 2097152,
    'use_preview' => 1,
    'img_size_x' => '400',
    'img_size_y' => '300',
    'video_size_x' => '',
    'video_size_y' => '',
    'locked_attaches' => 0,
    'onlyimg_attaches' => 0,
);