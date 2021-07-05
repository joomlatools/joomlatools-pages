<?php

/**
 * Joomlatools Pages
 *
 * @copyright  Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net).
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Server script for the built-in PHP web server.
 *
 * The built-in web server should only be used for development and testing as it has a number of limitations that makes
 * running Pages on it somewhat limited. Since PHP 7.4 the server can fork multiple workers in order to test code that
 * requires multiple concurrent requests to the built-in webserver. (This is not supported on Windows)
 *
 * Usage: PHP_CLI_SERVER_WORKERS=10 PAGES_SITE=[name] php -S [domain]:[port] server.php
 *
 * Example:
 *   - PHP_CLI_SERVER_WORKERS=10 PAGES_SITE=foo.com php -S foo.test:8080 server.php
 *
 * @see http://php.net/manual/en/features.commandline.webserver.php
 */

$cache = $_SERVER['HTTP_CACHE_CONTROL'] ?? null;

$url   = $_SERVER['REQUEST_URI'];
$path  = parse_url($url, PHP_URL_PATH );
$query = parse_url($url, PHP_URL_QUERY);


/**
 * Find site
 */

$site = getenv('PAGES_SITE') ?? 'default';

if(!is_dir(dirname(getcwd()).'/sites/'.$site))
{
    http_response_code('404');
    return false;
}

/**
 * Set environment
 */
$_SERVER['PAGES_PATH'] = '/';
putenv('PAGES_IMAGES_ROOT', dirname(getcwd()).'/sites/'.$site.'/images');
putenv('PAGES_STATIC_ROOT', getcwd().'/'.$site);

/**
 * Prevent direct access to /web
 */
$paths = [
    '^/$'
];

if(file_exists(getcwd().$path))
{
    $allowed = false;

    foreach($paths as $regex)
    {
        if(preg_match('#'.$regex.'#', $url) == 1)
        {
            $allowed = true;
            break;
        };
    }

    if(!$allowed)
    {
        http_response_code('403');
        return true;
    }
}

/**
 * Aliases for assets
 */
$aliases = [
    //Generated images
    '^/images/(.*)\.(jpe?g|png|gif|svg)\?(.*)$' => getcwd().'/'.$site.'/images/$1.webp_$3',
    '^/images/(.*)\.(jpe?g|png|gif|svg)\?(.*)$$' => getcwd().'/'.$site.'/images/$1.$2_$3',

    //Content images (and videos)
    '^/images/(.*)$' => dirname(getcwd()).'/sites/'.$site.'/images/$1',
    '^/videos/(.*)$' => dirname(getcwd()).'/sites/'.$site.'/videos/$1',

    //Theme assets
    '^/theme/([^\?]+)(?:\?(.*))?$'  => dirname(getcwd()).'/sites/'.$site.'/theme/$1',
];

foreach($aliases as $regex => $replace)
{
    $result = preg_replace('#'.$regex.'#', $replace, $url);
    $file   = realpath($result);

    if(is_file($file))
    {
        switch(pathinfo($file, PATHINFO_EXTENSION))
        {
            case 'css' : $mime = 'text/css'; break;
            case 'js'  : $mime = 'application/javascript'; break;
            case 'ico' : $mime = 'image/x-icon'; break;
            case 'svg' : $mime = 'image/svg+xml'; break;
            default    : $mime = mime_content_type($file);
        }

        header('Content-Type: '.$mime);
        header('Content-Length: '.filesize($file));
        header('Cache-Control: public,max-age=31536000,immutable');
        header('Last-Modified:'.date(DATE_RFC7231, filemtime($file)));

        readfile($file);
        return true;
    }
}

/**
 * Static cache
 */
if(!preg_match('/(must-revalidate|max-age|no-cache|no-store)/', $cache))
{
    $rewrites = [
        '^/$'     => getcwd().'/'.$site.'/index.html',
        '^/(.*)$' => getcwd().'/'.$site.'/$1',
    ];

    foreach($rewrites as $regex => $replace)
    {
        $result = preg_replace('#'.$regex.'#', $replace, $result);
        $file   = realpath($result);

        if(is_file($file))
        {
            header('Content-Type: '. mime_content_type($file));
            header('Content-Length: '.filesize($file));
            header('Cache-Status: HIT, STATIC');
            header('Cache-Control: max-age=86400,s-maxage=604800');
            header('Last-Modified:'.date(DATE_RFC7231, filemtime($file)));

            readfile($file);
            return true;
        }
    }
}

/**
 * Generate image
 */
if(file_exists(getcwd().'/image.php') && preg_match('#^/images/([^\?]+)\.(jpe?g|png|gif|svg)$#', $path) == 1)
{
    putenv('IMAGE=1');

    $image_path = str_replace('/images/', '', $path);
    $_SERVER['QUERY_STRING'] .= '&dest_path=images&src_path='.$image_path;

    header('Cache-Control: public,max-age=31536000,immutable');
    require getcwd().'/image.php';

    return true;
}

/**
 * Generate page
 */
require getcwd().'/index.php';