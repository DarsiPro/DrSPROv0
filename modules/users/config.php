<?php
return array (
    // system
    'active' => 1,
    'std_admin_pages' => array(
        // 'settings' => only true(need on/off)
        // 'design' => only auto(configure template_parts.php)
        'comments_list' => false,
        'category' => false,
        'additional_fields' => true,
        'materials_list' => false
    ),

    // non-system
    'open_reg' => 1,
    'max_avatar_size' => 2048000,
    'users_per_page' => '30',
    'max_message_lenght' => '2000',
    'max_count_mess' => '100',
    'title' => 'Пользователи',
    'description' => 'Юзвери',
    'max_mail_lenght' => '20000',
    'rating_comment_lenght' => '100',
    'warnings_by_ban' => '5',
    'autoban_interval' => 8640,
    'use_gravatar' => 1,
    'use_md5_salt' => 1,
    'new_pm_mail' => 0,
    'pm_type' => '1',
    'email_activate' => 1,
    'min_password_lenght' => '6',
    'only_latin' => 0,
    'fields' => array (
        'keystring' => 'keystring',
    ),
    'stars' => array(
        'rat0' => 'Прохожий',
        'cond1' => 2,
        'rat1' => 'Новичок',
        'cond2' => 10,
        'rat2' => 'Участник',
        'cond3' => 30,
        'rat3' => 'Местный',
        'cond4' => 60,
        'rat4' => 'Опытный',
        'cond5' => 90,
        'rat5' => 'Знаток',
        'cond6' => 150,
        'rat6' => 'Бывалый',
        'cond7' => 200,
        'rat7' => 'Мастер',
        'cond8' => 300,
        'rat8' => 'Профи',
        'cond9' => 400,
        'rat9' => 'Эсксперт',
        'cond10' => 600,
        'rat10' => 'VIP'
    )
)
?>