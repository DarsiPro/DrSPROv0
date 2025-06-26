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

    'max_lenght' => array(
        'type' => 'number',
        'title' => __('Max length of material'),
        'description' => __('Max length of material: info'),
        'help' => __('Symbols'),
    ),
    'announce_lenght' => array(
        'type' => 'number',
        'title' => __('length of announce'),
        'description' => __('length of announce: info'),
        'help' => __('Symbols'),
    ),
    'per_page' => array(
        'type' => 'number',
        'title' => __('Materials per page'),
    ),

    __('Images'),

    'use_local_preview' => array(
        'type' => 'checkbox',
        'title' => __('Use local preview'),
        'description' => __('Use local preview: info'),
    ),
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

    __('Required fields'),

    'fields_cat' => array(
        'type' => 'checkbox',
        'title' => __('Category'),
        'attr' => array(
            'disabled' => 'disabled',
            'checked' => 'checked',
        ),
    ),
    'fields_title' => array(
        'type' => 'checkbox',
        'title' => __('Title'),
        'attr' => array(
            'disabled' => 'disabled',
            'checked' => 'checked',
        ),
    ),
    'fields_main' => array(
        'type' => 'checkbox',
        'title' => __('Material body'),
        'attr' => array(
            'disabled' => 'disabled',
            'checked' => 'checked',
        ),
    ),
    'sub_tags' => array(
        'type' => 'checkbox',
        'title' => __('tags'),
        'value' => 'tags',
        'fields' => 'fields',
    ),
    'sub_sourse' => array(
        'type' => 'checkbox',
        'title' => __('source'),
        'value' => 'sourse',
        'fields' => 'fields',
    ),
    'sub_sourse_email' => array(
        'type' => 'checkbox',
        'title' => __('sourse_email'),
        'value' => 'sourse_email',
        'fields' => 'fields',
    ),
    'sub_sourse_site' => array(
        'type' => 'checkbox',
        'title' => __('sourse_site'),
        'value' => 'sourse_site',
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