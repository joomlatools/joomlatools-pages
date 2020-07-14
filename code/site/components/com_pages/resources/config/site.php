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
            'cache'         => $config['template_cache'],
            'cache_path'    => $config['template_cache_path'],
            'cache_reload'  => $config['template_cache_validation'],
        ],
        'com://site/pages.dispatcher.behavior.cacheable' => [
            'cache'             => $config['http_cache'],
            'cache_path'        => $config['http_cache_path'],
            'cache_time'        => $config['http_cache_time'],
            'cache_time_shared' => $config['http_cache_time_proxy'],
            'cache_validation'  => $config['http_cache_validation'],
            'cache_control'     => $config['http_cache_control'],
        ],
        'com://site/pages.http.client' => [
            'cache'       => $config['http_resource_cache'],
            'cache_time'  => $config['http_resource_cache_time'],
            'cache_path'  => $config['http_resource_cache_path'],
            'cache_force' => $config['http_resource_cache_force'],
            'debug'       => $config['http_resource_cache_debug'],
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
        'com://site/pages.template.filter.highlight' => [
            'highlighter' => function($source, $language) {
                //See: https://github.com/scrivo/highlight.php
                return (new \Highlight\Highlighter())->highlight($language, $source, false)->value;
            }
        ],
        'com://site/pages.event.subscriber.staticcache' => [
            'enable'     => $config['http_static_cache'],
            'cache_path' => $config['http_static_cache_path'],
        ],
    ],
    'extensions' => $config['extensions'] ?? array(),
];