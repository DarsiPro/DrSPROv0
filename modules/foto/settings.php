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


    __('Restrictions'),

    'max_file_size' => array(
        'type' => 'number',
        'title' => __('Max size image'),
        'help' => __('Byte'),
    ),
    'per_page' => array(
        'type' => 'number',
        'title' => __('Materials per page'),
    ),
    'description_lenght' => array(
        'type' => 'number',
        'title' => __('Max length of description'),
        'help' => __('Symbols'),
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

    __('Required fields'),

    'category_field' => array(
        'type' => 'checkbox',
        'title' => __('Category'),
        'attr' => array(
            'disabled' => 'disabled',
            'checked' => 'checked',
        ),
    ),
    'title_field' => array(
        'type' => 'checkbox',
        'title' => __('Title'),
        'attr' => array(
            'disabled' => 'disabled',
            'checked' => 'checked',
        ),
    ),
    'file_field' => array(
        'type' => 'checkbox',
        'title' => __('Image'),
        'attr' => array(
            'disabled' => 'disabled',
            'checked' => 'checked',
        ),
    ),
    'sub_description' => array(
        'type' => 'checkbox',
        'title' => __('Description'),
        'value' => 'description',
        'fields' => 'fields',
    ),


    __('Comments'),

    'comment_active' => array(
        'type' => 'checkbox',
        'title' => __('Allow using comments'),
    ),
    'comment_lenght' => array(
        'type' => 'number',
        'title' => __('Max length of comment'),
        'help' => __('Symbols'),
    ),
    'comments_order' => array(
        'type' => 'checkbox',
        'title' => __('New comments on top'),
    ),


    __('Other'),

    'calc_count' => array(
        'type' => 'checkbox',
        'title' => __('Show materials count in list of sections'),
    ),
    'active' => array(
        'type' => 'checkbox',
        'title' => __('Module status'),
        'description' => __('Module status: info'),
    ),
);
