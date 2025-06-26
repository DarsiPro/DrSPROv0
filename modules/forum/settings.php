<?php

$settingsInfo = array(
    'title' => array(
        'title' => __('Title'),
        'description' => __('Title: info'),
    ),
    'description' => array(
        'title' => __('Meta-Description'),
        'description' => __('Meta-Description: info'),
    ),
    'not_reg_user' => array(
        'title' => __('Guest mask'),
        'description' => __('Guest mask: info'),
    ),

    __('Restrictions'),

    'max_post_lenght' => array(
        'type' => 'number',
        'title' => __('Max length of text'),
        'description' => __('Max length of text: info'),
        'help' => __('Symbols'),
    ),
    'posts_per_page' => array(
        'type' => 'number',
        'title' => __('Posts on page'),
    ),
    'themes_per_page' => array(
        'type' => 'number',
        'title' => __('Themes on page'),
    ),

    __('Images'),

    'use_preview' => array(
        'type' => 'checkbox',
        'title' => __('Use preview'),
        'description' => __('Use preview: info'),
    ),
    'img_size_x' => array(
        'type' => 'number',
        'title' => __('Width preview'),
        'description' => __('Width preview: info'),
        'help' => 'px',
        'grid-width' => 's6',
    ),
    'img_size_y' => array(
        'type' => 'number',
        'title' => __('Height preview'),
        'description' => __('Height preview: info'),
        'help' => 'px',
        'grid-width' => 's6',
    ),

    __('Attaches'),

    'locked_attaches' => array(
        'type' => 'checkbox',
        'title' => __('Deny attaches'),
        'description' => __('Deny attaches: info'),
    ),
    'onlyimg_attaches' => array(
        'type' => 'checkbox',
        'title' => __('Allow upload only image'),
        'description' => __('Allow upload only image: info'),
    ),
    'max_attaches' => array(
        'type' => 'number',
        'title' => __('Max count attaches'),
        'description' => __('Max count attaches: info'),
    ),
    'max_attaches_size' => array(
        'type' => 'number',
        'title' => __('Max size attach'),
        'description' => __('Max size attach: info'),
        'help' => __('Kb'),
        'onview' => array(
            'division' => 1024,
        ),
        'onsave' => array(
            'multiply' => 1024,
        )
    ),

    __('Video'),

    'video_size_x' => array(
        'type' => 'number',
        'title' => __('Width video'),
        'description' => __('Width video: info'),
        'help' => 'px',
        'grid-width' => 's6',
    ),
    'video_size_y' => array(
        'type' => 'number',
        'title' => __('Height video'),
        'description' => __('Height video: info'),
        'help' => 'px',
        'grid-width' => 's6',
    ),

    __('Other'),

    'active' => array(
        'type' => 'checkbox',
        'title' => __('Module status'),
        'description' => __('Module status: info'),
    ),
);
