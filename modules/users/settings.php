<?php

// Генерация списка шаблонов для почтовых писем
$sourse = glob(ROOT . '/data/mail/*', GLOB_ONLYDIR);
if (!empty($sourse) && is_array($sourse)) {
    $mailtmps = array();
    foreach ($sourse as $dir) {
        if (preg_match('#.*/(\w+)$#', $dir, $match)) {
            $mailtmps[] = $match[1];
        }
    }
}
$mailtmpSelect = array();
if (!empty($mailtmps)) {
    foreach ($mailtmps as $value) {
        $mailtmpSelect[$value] = ucfirst($value);
    }
}


$settingsInfo = array(
    'title' => array(
        'title' => __('Title'),
        'description' => __('Title: info'),
    ),
    'description' => array(
        'title' => __('Meta-Description'),
        'description' => __('Meta-Description: info'),
    ),

    __('Restrictions'),

    'open_reg' => array(
        'type' => 'checkbox',
        'title' => __('Registartion mode'),
        'description' => __('Registartion mode: info'),
    ),
    'only_latin' => array(
        'type' => 'checkbox',
        'title' => __('Use only latin nicknames'),
    ),
    'min_password_lenght' => array(
        'type' => 'number',
        'title' => __('Min password length'),
        'help' => __('Symbols'),
    ),
    'email_activate' => array(
        'type' => 'checkbox',
        'title' => __('Use activation via e-mail'),
    ),
    'max_avatar_size' => array(
        'type' => 'number',
        'title' => __('Max size avatar'),
        'description' => __('Max size avatar: info'),
        'help' => __('Kb'),
        'onview' => array(
            'division' => 1024,
        ),
        'onsave' => array(
            'multiply' => 1024,
        ),
    ),
    'users_per_page' => array(
        'type' => 'number',
        'title' => __('Users per page'),
        'description' => __('Users per page: info'),
    ),
    'max_mail_lenght' => array(
        'type' => 'number',
        'title' => __('Max length mail'),
        'description' => __('Max length mail: info'),
        'help' => __('Symbols'),
    ),
    'max_count_mess' => array(
        'type' => 'number',
        'title' => __('Max count pms'),
        'description' => __('Max count pms: info'),
    ),
    'max_message_lenght' => array(
        'type' => 'number',
        'title' => __('Max length pm'),
        'help' => __('Symbols'),
    ),
    'rating_comment_lenght' => array(
        'type' => 'number',
        'title' => __('Max length comment for vote'),
        'help' => __('Symbols'),
    ),
    'warnings_by_ban' => array(
        'type' => 'number',
        'title' => __('Warnings by ban'),
    ),
    'autoban_interval' => array(
        'type' => 'number',
        'title' => __('Autoban interval'),
        'help' => __('hour.'),
        'onview' => array(
            'division' => 360,
        ),
        'onsave' => array(
            'multiply' => 360,
        ),
    ),

    __('Required fields'),

    'fields_login' => array(
        'type' => 'checkbox',
        'title' => __('login'),
        'attr' => array(
            'disabled' => 'disabled',
            'checked' => 'checked',
        ),
    ),
    'fields_email' => array(
        'type' => 'checkbox',
        'title' => __('email'),
        'attr' => array(
            'disabled' => 'disabled',
            'checked' => 'checked',
        ),
    ),
    'fields_password' => array(
        'type' => 'checkbox',
        'title' => __('password'),
        'attr' => array(
            'disabled' => 'disabled',
            'checked' => 'checked',
        ),
    ),
    'sub_keystring' => array(
        'type' => 'checkbox',
        'title' => __('Captcha'),
        'fields' => 'fields',
        'value' => 'keystring',
    ),
    'sub_pol' => array(
        'type' => 'checkbox',
        'title' => __('pol'),
        'fields' => 'fields',
        'value' => 'pol',
    ),
    'sub_byear' => array(
        'type' => 'checkbox',
        'title' => __('byear'),
        'fields' => 'fields',
        'value' => 'byear',
    ),
    'sub_bmonth' => array(
        'type' => 'checkbox',
        'title' => __('bmonth'),
        'fields' => 'fields',
        'value' => 'bmonth',
    ),
    'sub_bday' => array(
        'type' => 'checkbox',
        'title' => __('bday'),
        'fields' => 'fields',
        'value' => 'bday',
    ),
    'sub_url' => array(
        'type' => 'checkbox',
        'title' => __('url'),
        'fields' => 'fields',
        'value' => 'url',
    ),
    'sub_about' => array(
        'type' => 'checkbox',
        'title' => __('about'),
        'fields' => 'fields',
        'value' => 'about',
    ),
    'sub_signature' => array(
        'type' => 'checkbox',
        'title' => __('signature'),
        'fields' => 'fields',
        'value' => 'signature',
    ),

    __('Other'),

    'use_gravatar' => array(
        'type' => 'checkbox',
        'title' => __('Using Gravatar'),
        'description' => __('Using Gravatar: info'),
    ),
    'use_md5_salt' => array(
        'type' => 'checkbox',
        'title' => __('Using passwords MD5 + salt'),
        'description' => __('Using passwords MD5 + salt: info'),
    ),
    'new_pm_mail' => array(
        'type' => 'checkbox',
        'title' => __('Notify having unread private messages'),
        'description' => __('Notify having unread private messages: info'),
    ),
    'active' => array(
        'type' => 'checkbox',
        'title' => __('Module status'),
        'description' => __('Module status: info'),
    ),
);