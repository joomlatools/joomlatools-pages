<?php
return array(
    //Http Caching
    'http_cache'            => JDEBUG ? false : true,
    'http_cache_path'       => JPATH_ROOT.'/joomlatools-pages/cache',
    'http_cache_time'       => 900, // 15 minutes
    'http_cache_time_proxy' => 60*60*24, // 1 day 

    //Page caching
    'page_cache'            => JDEBUG ? false : true,
    'page_cache_time'       => 60*60*24, // 1 day
    'page_cache_path'       => JPATH_ROOT.'/joomlatools-pages/cache',

     //Data caching
    'data_cache'            => JDEBUG ? false : true,
    'data_cache_time'       => 60*60*24, // 1 day
    'data_cache_path'       => JPATH_ROOT.'/joomlatools-pages/cache',

    //Remote caching
    'remote_cache'          => JDEBUG ? false : true,
    'remote_cache_time'     => 60*60*24, // 1 day
    'remote_cache_path'     => JPATH_ROOT.'/joomlatools-pages/cache',

    //Template caching
    'template_cache'        => JDEBUG ? false : true,
    'template_cache_path'   => JPATH_ADMINISTRATOR.'/cache/koowa.templates',

    //Sites
    /*
    'sites' => [
        '[*]' => JPATH_ROOT.'/sites/default'  
     ],
    */

    // Site
    'site' => [
        'body_class'        => 'bg-gray-100',
        'copyright_notice'  => 'Joomlatools | Any Rights You Want',
        'logo'              => 'theme://images/logo/joomlatools.png',
        'main_color'        => '#0089D6',
        'name'              => 'Joomlatools Pages',
    ],

    // Page
    'page' => [

        'metadata' => [
            'og:site_name'  => 'Joomlatools Pages - the easy to use page generator for Joomla',
            'og:image'      => '/joomlatools-pages/theme/images/logo/joomlatools.png',
            'twitter:site'  => '@joomlatools',
            'twitter:card'  => 'summary_large_image',
            //'fb:pages'     => '111111111111111'
        ],

        'visible'   => true,
        'published' => true,
    ],

    // Google Analytics
    'ga_code' => 'O-gAbCcD1E2FGHIJKlmnO3PqRst4Uv5wXzz-O1xx_xx'
);