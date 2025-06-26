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

    'index_interval' => array(
        'type' => 'number',
        'title' => __('Periodicity update'),
        'description' => __('Periodicity update: info'),
        'help' => __('Days'),
    ),
    'min_lenght' => array(
        'type' => 'number',
        'title' => __('Min length query'),
        'description' => __('Min length query: info'),
        'help' => __('Symbols'),
    ),
    'per_page' => array(
        'type' => 'number',
        'title' => __('Materials per page'),
    ),

    __('Other'),

    'active' => array(
        'type' => 'checkbox',
        'title' => __('Module status'),
        'description' => __('Module status: info'),
    ),
);