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
    'title' => 'Статьи',
    'description' => 'Только интересные статьи',
    'max_lenght' => '10000',
    'per_page' => '7',
    'announce_lenght' => '1000',
    'active' => 1,
    'comment_active' => 1,
    'comment_per_page' => '30',
    'comment_lenght' => '500',
    'max_attaches' => '10',
    'max_attaches_size' => 1023998976,
    'fields' => array (),
    'use_local_preview' => 0,
    'use_preview' => 0,
    'img_size_x' => '',
    'img_size_y' => '',
    'comments_order' => 0,
    'calc_count' => 0,
    'locked_attaches' => 0,
    'onlyimg_attaches' => 0,
    'video_size_x' => '',
    'video_size_y' => '',
)
?>