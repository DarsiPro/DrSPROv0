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
    'title' => 'Фото',
    'description' => 'Каталог Фотографий',
    'description_lenght' => '300',
    'description_requred' => 0,
    'comment_active' => 1,
    'comment_per_page' => '50',
    'comment_lenght' => '500',
    'per_page' => '20',
    'max_file_size' => '5000000',
    'acl' => array (1,2,3),
    'comments_order' => 0,
    'calc_count' => 0,
    'video_size_x' => '',
    'video_size_y' => '',
)
?>