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
    'title' => 'Файлы',
    'description' => 'Каталог файлов. Все файлы тут.',
    'min_lenght' => '10',
    'max_lenght' => '4500',
    'announce_lenght' => '300',
    'per_page' => '50',
    'max_file_size' => '105000000',
    'comment_active' => 1,
    'comment_per_page' => '50',
    'comment_lenght' => '500',
    'max_attaches' => '10',
    'max_attaches_size' => 5000192,
    'fields' => array(),
    'require_file' => 0,
    'filename_from_title' => 0,
    'filename_postfix' => '',
    'use_local_preview' => 1,
    'use_preview' => 1,
    'img_size_x' => '250',
    'img_size_y' => '600',
    'comments_order' => 0,
    'calc_count' => 0,
    'locked_attaches' => 0,
    'video_size_x' => '',
    'video_size_y' => '',
)
?>