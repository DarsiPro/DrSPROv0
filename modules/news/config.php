<?php 
return array (
    // system
    'active' => 1,
    'std_admin_pages' => array(
        // 'settings' => only true(need on/off)
        // 'design' => only auto(configure template_parts.php)
        'comments_list' => true,
        'category' => true,
        'additional_fields' => true,
        'materials_list' => true
    ),

    // non-system
    'title' => 'Новости',
    'description' => 'Самые свежие новости',
    'max_lenght' => '999000',
    'announce_lenght' => '700',
    'per_page' => '10',
    'comment_active' => 1,
    'comment_per_page' => '50',
    'comment_lenght' => '500',
    'max_attaches' => '10',
    'max_attaches_size' => 102400000,
    'fields' => array (),
    'comments_order' => 0,
    'use_local_preview' => 0,
    'use_preview' => 0,
    'img_size_x' => '',
    'img_size_y' => '',
    'calc_count' => 0,
    'video_size_x' => '',
    'video_size_y' => '',
    'locked_attaches' => 0,
    'onlyimg_attaches' => 0,
)
?>