<?php

//Load server environment
if(isset($_SERVER['HOME']) && file_exists($_SERVER['HOME'].'/private_html/.env.'.getenv('SITE')))
{
  $dotenv = new Symfony\Component\Dotenv\Dotenv();
  $dotenv->usePutenv()->load($_SERVER['HOME'].'/private_html/.env.'.getenv('SITE'));
}

return array(

    // Site
    'site' => [
        'name'              => 'Joomlatools Pages & Joomla',
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
            'fb:admins'     => '',
        ],

        'visible'   => true,
        'published' => true,
    ],

    'aliases' => [
        'theme://'                  => getenv('SITE')  ? '/theme/' : '/joomlatools-pages/theme/',
        'images://'                 => getenv('SITE') ? '/images/'    : '/joomlatools-pages/images/',
    ],

    // Google Analytics
    'ga_code' => getenv('SITE') ? '' : '',

);
