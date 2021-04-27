<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return [

    'composer_path' => $config['composer_path'],
    'identifiers'   => [
        'com://site/pages.template.filter.asset' => [
            'schemes' =>  $config['aliases'] ?? array()
        ],
        'page.registry' => [
            'cache'            => $config['page_cache'],
            'cache_path'       => $config['page_cache_path'],
            'cache_validation' => $config['page_cache_validation'],
            'collections' => $config['collections'],
            'redirects'   => array_flip($config['redirects']),
            'properties'  => $config['page'],
        ],
        'data.registry' => [
            'namespaces'       => $config['data_namespaces'],
            'cache'            => $config['data_cache'],
            'cache_path'       => $config['data_cache_path'],
            'cache_validation' => $config['data_cache_validation'],
        ],
        'template.engine.factory' => [
            'debug'         => $config['template_debug'],
            'cache'         => $config['template_cache'],
            'cache_path'    => $config['template_cache_path'],
            'cache_reload'  => $config['template_cache_validation'],
        ],
        'com://site/pages.dispatcher.behavior.cacheable' => [
            'cache'       => $config['http_cache'],
            'cache_path'  => $config['http_cache_path'],
            'cache_time'         => !is_null($config['http_cache_time_browser']) ? $config['http_cache_time_browser'] : $config['http_cache_time'],
            'cache_time_shared'  => $config['http_cache_time'],
            'cache_control'         => $config['http_cache_control'],
            'cache_control_private' => $config['http_cache_control_private'],

        ],
        'com://site/pages.http.cache' => [
            'cache'       => $config['http_client_cache'],
            'cache_path'  => $config['http_client_cache_path'],
            'debug'       => $config['http_client_cache_debug'],
        ],
        'com://site/pages.model.cache' => [
            'cache_path' => $config['http_cache_path'],
        ],
        'lib:template.engine.markdown' => [
            'compiler' => function($text) {
                //See: https://michelf.ca/projects/php-markdown/extra/
                return \Michelf\MarkdownExtra::defaultTransform($text);
            }
        ],
        'com://site/pages.event.subscriber.staticcache' => [
            'enabled'    => $config['http_static_cache'] && $config['http_cache'],
            'cache_path' => $config['http_static_cache_path'],
        ],
    ],
    'extensions' => $config['extensions'] ?? array(),
];