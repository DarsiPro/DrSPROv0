<?php

$settingsInfo = array(

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