<?php

return array(

    //'http_cache' => true,
    //'http_cache_time_browser' => 0,

    'page_cache' => false,

    // Site
    'site' => [
        'name'  => 'Joomlatools Pages - A blog site',
    ],

    // Page
    'page' => [

        'metadata' => [
            'summary'       => '',
            'og:site_name'  => '',
            'og:url'        => '',
            'og:title'      => '',
            'og:description'=> '',
            'og:image'      => '',
            'twitter:site'  => '',
            'twitter:card'  => '',
        ],

        'visible'   => true,
        'published' => true,
    ],

    // Aliases
    'aliases' => [
        'theme://'  => '/joomlatools-pages/theme/',
        'images://' => '/joomlatools-pages/images/',
    ],

    // Google Analytics
    'ga_code' => '',

    // Extensions
    'extensions' => [

        'ext:joomla.model.articles'  => [
            'aliases' => [
                'about-us' => 1
            ]
        ]
    ]
);
